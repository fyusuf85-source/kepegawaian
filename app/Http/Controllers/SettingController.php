<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('setting.view'), 403);

        $settings = Setting::query()->orderBy('group')->orderBy('key')->get()->groupBy('group');

        return view('settings.index', [
            'settings' => $settings,
            'canManage' => $request->user()->hasPermission('setting.manage'),
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('setting.manage'), 403);

        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:65000'],
        ]);

        foreach ($data['settings'] as $id => $value) {
            $setting = Setting::find($id);
            if (! $setting) {
                continue;
            }

            $setting->update([
                'value' => $value,
                'updated_by' => $request->user()->id,
            ]);
        }

        $audit->recordRaw('update', Setting::class, null, null, [
            'keys' => Setting::query()->whereIn('id', array_keys($data['settings']))->pluck('key')->all(),
        ]);

        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil disimpan.');
    }
}
