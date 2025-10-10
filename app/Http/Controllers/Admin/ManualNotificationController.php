<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ManualNotificationController extends Controller
{
    /**
     * Display a listing of notifications
     */
    public function index()
    {
        $notifications = ManualNotification::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.manual_notifications.index', compact('notifications'));
    }

    /**
     * Show the form for creating a new notification
     */
    public function create()
    {
        return view('admin.manual_notifications.create');
    }

    /**
     * Store a newly created notification
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean'
        ]);

        $data = $request->only(['title', 'message', 'is_active']);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = Str::random(20) . '.' . $image->getClientOriginalExtension();
            $image->storeAs('notifications', $filename, 'public');
            $data['image'] = $filename;
        }

        ManualNotification::create($data);

        return redirect()->route('admin.manual_notifications.index')
            ->with('success_message', __('admin.notification_created_successfully'));
    }

    /**
     * Display the specified notification
     */
    public function show(ManualNotification $manualNotification)
    {
        return view('admin.manual_notifications.show', compact('manualNotification'));
    }

    /**
     * Show the form for editing the notification
     */
    public function edit(ManualNotification $manualNotification)
    {
        return view('admin.manual_notifications.edit', compact('manualNotification'));
    }

    /**
     * Update the specified notification
     */
    public function update(Request $request, ManualNotification $manualNotification)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean'
        ]);

        $data = $request->only(['title', 'message', 'is_active']);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($manualNotification->image) {
                Storage::disk('public')->delete('notifications/' . $manualNotification->image);
            }

            $image = $request->file('image');
            $filename = Str::random(20) . '.' . $image->getClientOriginalExtension();
            $image->storeAs('notifications', $filename, 'public');
            $data['image'] = $filename;
        }

        $manualNotification->update($data);

        return redirect()->route('admin.manual_notifications.index')
            ->with('success_message', __('admin.notification_updated_successfully'));
    }

    /**
     * Remove the specified notification
     */
    public function destroy(ManualNotification $manualNotification)
    {
        // Delete image if exists
        if ($manualNotification->image) {
            Storage::disk('public')->delete('notifications/' . $manualNotification->image);
        }

        $manualNotification->delete();

        return redirect()->route('admin.manual_notifications.index')
            ->with('success_message', __('admin.notification_deleted_successfully'));
    }

    /**
     * Toggle notification status
     */
    public function toggleStatus(ManualNotification $manualNotification)
    {
        $manualNotification->update(['is_active' => !$manualNotification->is_active]);

        $status = $manualNotification->is_active ? 'activated' : 'deactivated';
        return redirect()->back()
            ->with('success_message', __('admin.notification_' . $status . '_successfully'));
    }
}