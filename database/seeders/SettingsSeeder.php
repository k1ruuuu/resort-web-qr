<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'delivery.automatic_enabled' => '1',
            'delivery.scheduled_enabled' => '0',
            'delivery.default_time' => '08:00',
            'delivery.timezone' => 'Asia/Jakarta',
            'delivery.whatsapp_provider' => 'Fonnte',
            'delivery.fonnte_token' => 'GpMC1EMdd5nHp9EWboyy',
            'delivery.message_template' => "Halo {guest_name},\n\nVoucher Digital Anda telah aktif.\n\nRoom:\n{room_code}\n\nTotal Pax:\n{total_pax}\n\nSilakan tunjukkan QR berikut saat menggunakan fasilitas resort.\n\nTerima kasih.",
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
