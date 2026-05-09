<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SystemSettingsController extends Controller
{
    private array $allowedThemes = ['light', 'dark', 'pastel', 'classic'];

    /** Public — no auth required (for login page) */
    public function public(Request $request): JsonResponse
    {
        $hospital = \App\Models\Hospital::orderBy('id')->first();
        return response()->json([
            'system_name' => $hospital?->system_name ?: 'CK-MEMS',
            'logo_url'    => $hospital?->logo_path
                ? asset('storage/' . $hospital->logo_path)
                : null,
            'theme'       => $hospital?->theme ?: 'light',
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $hospital = $request->user()->hospital;

        return response()->json([
            'system_name'   => $hospital->system_name ?? 'CK-MEMS',
            'hospital_name' => $hospital->name_th,
            'logo_url'      => $hospital->logo_path
                ? asset('storage/' . $hospital->logo_path)
                : null,
            'theme'         => $hospital->theme ?? 'light',
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'system_name' => ['nullable', 'string', 'max:100'],
            'theme'       => ['nullable', 'string', 'in:light,dark,pastel,classic'],
        ]);

        $hospital = $request->user()->hospital;
        $hospital->update(array_filter($data, fn($v) => $v !== null));

        return response()->json([
            'message'     => 'บันทึกการตั้งค่าเรียบร้อย',
            'system_name' => $hospital->fresh()->system_name ?? 'CK-MEMS',
            'logo_url'    => $hospital->logo_path
                ? asset('storage/' . $hospital->logo_path)
                : null,
            'theme'       => $hospital->fresh()->theme ?? 'light',
        ]);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,svg,webp', 'max:2048'],
        ]);

        $hospital = $request->user()->hospital;

        // Remove old logo
        if ($hospital->logo_path && Storage::disk('public')->exists($hospital->logo_path)) {
            Storage::disk('public')->delete($hospital->logo_path);
        }

        $path = $request->file('logo')->store('logos', 'public');
        $hospital->update(['logo_path' => $path]);

        return response()->json([
            'message' => 'อัพโหลดโลโก้เรียบร้อย',
            'logo_url' => asset('storage/' . $path),
        ]);
    }
}
