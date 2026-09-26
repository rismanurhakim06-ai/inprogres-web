<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleDashboardSetting extends Model
{
    public const ROLE_LABELS = [
        'superadmin' => 'Superadmin',
        'admin' => 'Admin',
        'user' => 'User',
        'supervisor' => 'Supervisor',
        'owner' => 'Owner (kompatibilitas)',
    ];

    public const FEATURES = [
        'ticket_number' => 'Nomor tiket',
        'created_at' => 'Tanggal ajuan',
        'requester_name' => 'Nama pengaju',
        'whatsapp_number' => 'No. WhatsApp',
        'description' => 'Ajuan',
        'target' => 'Target',
        'status' => 'Status',
        'comment_tools' => 'Komentar',
        'completed_at' => 'Tanggal selesai',
        'edit_submission' => 'EDIT',
        'actions' => 'Aksi',
    ];

    public const ROLE_FEATURES = [
        'admin' => ['ticket_number', 'created_at', 'requester_name', 'whatsapp_number', 'description', 'target', 'status', 'comment_tools', 'completed_at', 'actions'],
        'user' => ['ticket_number', 'created_at', 'requester_name', 'whatsapp_number', 'description', 'target', 'status', 'comment_tools', 'completed_at', 'edit_submission'],
        'supervisor' => ['ticket_number', 'created_at', 'requester_name', 'whatsapp_number', 'description', 'target', 'status', 'completed_at', 'actions'],
        'owner' => ['ticket_number', 'created_at', 'requester_name', 'whatsapp_number', 'description', 'target', 'status', 'comment_tools', 'completed_at', 'actions'],
    ];

    public const CREATABLE_ROLES = [
        'user' => 'User',
        'admin' => 'Admin',
        'supervisor' => 'Supervisor',
    ];

    protected $fillable = ['role', 'features'];

    protected function casts(): array
    {
        return [
            'features' => 'array',
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function defaultFeaturesFor(string $role): array
    {
        $features = array_fill_keys(array_keys(self::FEATURES), false);

        $defaultFeatures = $role === 'superadmin'
            ? array_keys(self::FEATURES)
            : (self::ROLE_FEATURES[$role] ?? []);

        foreach ($defaultFeatures as $feature) {
            $features[$feature] = true;
        }

        return $features;
    }
}
