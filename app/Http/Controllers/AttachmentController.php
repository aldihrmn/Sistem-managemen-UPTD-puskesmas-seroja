<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\ScheduleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function __invoke(Request $request, string $type, int $id)
    {
        $item = match ($type) {
            'schedule' => ScheduleRequest::findOrFail($id),
            'leave' => LeaveRequest::findOrFail($id),
            default => abort(404),
        };
        $user = $request->user();
        abort_unless($user->isAdmin() || $item->employee_id === $user->employee?->id, 403);
        abort_unless($item->attachment && Storage::disk('local')->exists($item->attachment), 404);

        return Storage::disk('local')->download($item->attachment);
    }
}
