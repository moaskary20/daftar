<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAdjustment;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function generate(Payroll $payroll): void
    {
        if ($payroll->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'لا يمكن إعادة احتساب مسير مرحّل.']);
        }

        [$year, $month] = array_map('intval', explode('-', $payroll->period_month));
        $from = Carbon::create($year, $month)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        DB::transaction(function () use ($payroll, $from, $to): void {
            $payroll->items()->delete();

            foreach (Employee::query()->where('is_active', true)->get() as $employee) {
                $adjustments = $employee->adjustments()
                    ->whereIn('status', ['pending', 'partial'])
                    ->where(function ($query) use ($from, $to): void {
                        $query->where(function ($query) use ($to): void {
                            $query->where('type', EmployeeAdjustment::TYPE_ADVANCE)
                                ->whereDate('adjustment_date', '<=', $to);
                        })->orWhere(function ($query) use ($from, $to): void {
                            $query->where('type', '!=', EmployeeAdjustment::TYPE_ADVANCE)
                                ->whereBetween('adjustment_date', [$from, $to]);
                        });
                    })
                    ->get();
                $incentives = (float) $adjustments->where('type', EmployeeAdjustment::TYPE_INCENTIVE)->sum('amount');
                $penalties = (float) $adjustments->where('type', EmployeeAdjustment::TYPE_PENALTY)->sum('amount');
                $advances = (float) $adjustments
                    ->where('type', EmployeeAdjustment::TYPE_ADVANCE)
                    ->sum(fn (EmployeeAdjustment $adjustment): float => min(
                        (float) $adjustment->amount - (float) $adjustment->settled_amount,
                        (float) $adjustment->amount / max(1, $adjustment->installments),
                    ));
                $absentDays = $employee->attendances()
                    ->whereBetween('attendance_date', [$from, $to])
                    ->where('status', 'absent')
                    ->count();
                $overtimeHours = (float) $employee->attendances()
                    ->whereBetween('attendance_date', [$from, $to])
                    ->sum('overtime_hours');
                $absence = ((float) $employee->basic_salary / 30) * $absentDays;
                $overtime = ((float) $employee->basic_salary / 240) * 1.5 * $overtimeHours;
                $earnings = (float) $employee->basic_salary + (float) $employee->fixed_allowances + $incentives + $overtime;
                $deductions = $advances + $penalties + $absence;

                $payroll->items()->create([
                    'employee_id' => $employee->id,
                    'basic_salary' => $employee->basic_salary,
                    'allowances' => $employee->fixed_allowances,
                    'incentives' => round($incentives, 4),
                    'overtime_amount' => round($overtime, 4),
                    'advances' => round($advances, 4),
                    'penalties' => round($penalties, 4),
                    'absence_deductions' => round($absence, 4),
                    'net_salary' => round($earnings - $deductions, 4),
                ]);
            }

            $this->recalculate($payroll);
        });
    }

    public function recalculate(Payroll $payroll): void
    {
        $items = $payroll->items()->get();
        $earnings = $items->sum(fn ($item): float => (float) $item->basic_salary
            + (float) $item->allowances
            + (float) $item->incentives
            + (float) $item->overtime_amount);
        $deductions = $items->sum(fn ($item): float => (float) $item->advances
            + (float) $item->penalties
            + (float) $item->absence_deductions
            + (float) $item->other_deductions);

        $payroll->update([
            'total_earnings' => round($earnings, 4),
            'total_deductions' => round($deductions, 4),
            'net_total' => round($earnings - $deductions, 4),
        ]);
    }

    public function post(Payroll $payroll): void
    {
        if ($payroll->status === 'posted') {
            throw ValidationException::withMessages(['status' => 'تم ترحيل مسير الرواتب مسبقاً.']);
        }

        if (! $payroll->payment_fund_id) {
            throw ValidationException::withMessages(['payment_fund_id' => 'حدد حساب دفع الرواتب.']);
        }

        DB::transaction(function () use ($payroll): void {
            $this->recalculate($payroll);
            $payroll->refresh();
            $accounting = app(AccountingService::class);
            $fund = $accounting->fund($payroll->payment_fund_type, $payroll->payment_fund_id, true);

            if ((float) $fund->current_balance < (float) $payroll->net_total) {
                throw ValidationException::withMessages(['net_total' => 'رصيد حساب دفع الرواتب غير كافٍ.']);
            }

            $lines = [
                ['account' => $accounting->systemAccount('5200'), 'debit' => (float) $payroll->total_earnings],
                ['account' => $fund->chart_account_id, 'credit' => (float) $payroll->net_total],
            ];

            if ((float) $payroll->total_deductions > 0) {
                $lines[] = ['account' => $accounting->systemAccount('1250'), 'credit' => (float) $payroll->total_deductions];
            }

            $entry = $accounting->createAndPost(
                'مسير رواتب '.$payroll->period_month,
                $lines,
                $payroll,
                $payroll->payment_date ?? today(),
                $payroll->number,
            );

            $fund->decrement('current_balance', (float) $payroll->net_total);

            [$year, $month] = array_map('intval', explode('-', $payroll->period_month));
            $from = Carbon::create($year, $month)->startOfMonth();
            $to = $from->copy()->endOfMonth();

            foreach ($payroll->items as $item) {
                $adjustments = $item->employee->adjustments()
                    ->whereIn('status', ['pending', 'partial'])
                    ->whereDate('adjustment_date', '<=', $to)
                    ->get();

                foreach ($adjustments as $adjustment) {
                    if ($adjustment->type !== EmployeeAdjustment::TYPE_ADVANCE
                        && ! $adjustment->adjustment_date->between($from, $to)) {
                        continue;
                    }

                    $settlement = $adjustment->type === EmployeeAdjustment::TYPE_ADVANCE
                        ? min(
                            (float) $adjustment->amount - (float) $adjustment->settled_amount,
                            (float) $adjustment->amount / max(1, $adjustment->installments),
                        )
                        : (float) $adjustment->amount;
                    $newSettled = min((float) $adjustment->amount, (float) $adjustment->settled_amount + $settlement);
                    $adjustment->update([
                        'settled_amount' => $newSettled,
                        'status' => $newSettled >= (float) $adjustment->amount ? 'settled' : 'partial',
                    ]);
                }
            }

            $payroll->update([
                'journal_entry_id' => $entry->id,
                'status' => 'posted',
                'posted_at' => now(),
                'payment_date' => $payroll->payment_date ?? today(),
            ]);
        });
    }
}
