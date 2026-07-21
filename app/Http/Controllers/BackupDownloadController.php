<?php

namespace App\Http\Controllers;

use App\Models\BackupRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupDownloadController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, BackupRecord $backup): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('manager') || $user->hasPermission('backups', 'view'), 403);
        abort_unless($backup->status === 'completed' && Storage::disk($backup->disk)->exists($backup->path), 404);

        return Storage::disk($backup->disk)->download($backup->path, $backup->filename);
    }
}
