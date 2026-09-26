<?php

namespace App\Http\Controllers;

use App\Models\RoleDashboardSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleDashboardSettingsController extends Controller
{
    public function index(): View
    {
        $settingsByRole = [];

        foreach (RoleDashboardSetting::ROLE_LABELS as $role => $label) {
            $setting = RoleDashboardSetting::query()->firstOrCreate(
                ['role' => $role],
                ['features' => RoleDashboardSetting::defaultFeaturesFor($role)],
            );
            $setting->features = array_merge(
                RoleDashboardSetting::defaultFeaturesFor($role),
                $setting->features ?? [],
            );
            $settingsByRole[$role] = $setting;
        }

        return view('settings.roles', [
            'settingsByRole' => $settingsByRole,
            'roleLabels' => RoleDashboardSetting::ROLE_LABELS,
            'features' => RoleDashboardSetting::FEATURES,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [
            'settings' => ['required', 'array:'.implode(',', array_keys(RoleDashboardSetting::ROLE_LABELS))],
        ];

        $featureKeys = array_keys(RoleDashboardSetting::FEATURES);

        foreach (RoleDashboardSetting::ROLE_LABELS as $role => $label) {
            $rules["settings.{$role}"] = ['required', 'array:_present,'.implode(',', $featureKeys)];
            $rules["settings.{$role}._present"] = ['required', 'accepted'];

            foreach ($featureKeys as $feature) {
                $rules["settings.{$role}.{$feature}"] = ['sometimes', 'boolean'];
            }
        }

        $validated = $request->validate($rules);

        foreach (RoleDashboardSetting::ROLE_LABELS as $role => $label) {
            $features = array_fill_keys(array_keys(RoleDashboardSetting::FEATURES), false);

            foreach (array_keys(RoleDashboardSetting::FEATURES) as $feature) {
                $features[$feature] = (bool) ($validated['settings'][$role][$feature] ?? false);
            }

            RoleDashboardSetting::query()->updateOrCreate(
                ['role' => $role],
                ['features' => $features],
            );
        }

        return redirect()->route('settings.roles.edit')->with('success', 'Pengaturan tampilan berhasil disimpan.');
    }
}
