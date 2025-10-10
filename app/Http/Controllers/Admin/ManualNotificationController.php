<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

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
            $data['image'] = $this->handleImageUpload($request->file('image'));
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
            if ($manualNotification->image && \File::exists('public/img/' . $manualNotification->image)) {
                \File::delete('public/img/' . $manualNotification->image);
            }
            $data['image'] = $this->handleImageUpload($request->file('image'));
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
        if ($manualNotification->image && \File::exists('public/img/' . $manualNotification->image)) {
            \File::delete('public/img/' . $manualNotification->image);
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

    /**
     * Helper method for image upload
     */
    private function handleImageUpload($file)
    {
        try {
            $temp = 'public/temp/';
            $path = 'public/img/';

            // Ensure directories exist
            if (!File::exists($temp)) {
                File::makeDirectory($temp, 0755, true);
            }
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }

            $extension = $file->getClientOriginalExtension();
            $fileName = 'notification-' . time() . '-' . uniqid() . '.' . $extension;

            // Move file to temp directory first
            if ($file->move($temp, $fileName)) {
                // Copy to final location
                if (File::copy($temp . $fileName, $path . $fileName)) {
                    // Delete temp file
                    File::delete($temp . $fileName);
                    return $fileName;
                }
            }
            return null;
        } catch (\Exception $e) {
            \Log::error('Image upload error: ' . $e->getMessage());
            return null;
        }
    }
}