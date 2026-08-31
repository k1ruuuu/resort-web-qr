<?php

namespace App\Services;

use Carbon\Carbon;

class FacilityScheduleService
{
    /**
     * Jadwal dan jam operasional penukaran fasilitas dalam zona waktu Asia/Jakarta (WIB).
     *
     * @var array<string, array{
     *     place: string,
     *     time_label: string,
     *     description: string,
     *     windows: array<int, array{start: string, end: string}>
     * }>
     */
    protected array $schedules = [
        'SNACK' => [
            'place' => 'Soeji Dining',
            'time_label' => '14:00 - 20:00 WIB',
            'description' => '14:00 - 20:00 WIB',
        ],
        'TEA' => [
            'place' => 'Teras Hutan Bambu',
            'time_label' => '15:00 - 17:00 WIB',
            'description' => '15:00 - 17:00 WIB',
        ],
        'DINNER-BBQ' => [
            'place' => 'Teras Hutan Bambu',
            'time_label' => '18:30 - 20:30 WIB',
            'description' => '18:30 - 20:30 WIB',
        ],
        'DINNER100K' => [
            'place' => 'Soeji or Rumpun',
            'time_label' => '18:30 - 20:30 WIB',
            'description' => '18:30 - 20:30 WIB',
        ],
        'DINNER' => [
            'place' => 'Teras Hutan Bambu / Soeji / Rumpun',
            'time_label' => '18:30 - 20:30 WIB',
            'description' => '18:30 - 20:30 WIB',
        ],
        'BREAKFAST' => [
            'place' => 'Soeji Dining',
            'time_label' => '07:00 - 10:00 WIB',
            'description' => '07:00 - 10:00 WIB',
        ],
        'JOURNAL' => [
            'place' => 'Rumah Seni',
            'time_label' => '14:00 - 17:00 WIB',
            'description' => '14:00 - 17:00 WIB',
        ],
        'FEED' => [
            'place' => 'Rumpun Area',
            'time_label' => '10:00 - 11:45 WIB & 13:15 - 16:45 WIB',
            'description' => '10:00 - 11:45 WIB & 13:15 - 16:45 WIB',
        ],
    ];

    /**
     * Dapatkan konfigurasi jadwal berdasarkan kode fasilitas.
     */
    public function getSchedule(string $facilityCode): ?array
    {
        $code = strtoupper(trim($facilityCode));

        return $this->schedules[$code] ?? null;
    }

    /**
     * Dapatkan semua jadwal terdaftar.
     */
    public function getAllSchedules(): array
    {
        return $this->schedules;
    }

    /**
     * Cek apakah waktu saat ini (atau waktu yang diberikan) berada dalam jam operasional fasilitas.
     */
    public function isWithinOperatingHours(string $facilityCode, ?Carbon $time = null, string $timezone = 'Asia/Jakarta'): bool
    {
        $schedule = $this->getSchedule($facilityCode);

        // Jika fasilitas tidak memiliki konfigurasi jadwal pembatasan, izinkan secara default
        if (!$schedule || empty($schedule['windows'])) {
            return true;
        }

        $tz = $timezone ?: 'Asia/Jakarta';
        $checkTime = ($time ? $time->copy() : Carbon::now())->setTimezone($tz);
        $currentTimeStr = $checkTime->format('H:i');

        foreach ($schedule['windows'] as $window) {
            $start = $window['start'];
            $end = $window['end'];

            // Pengecekan inklusif: jam saat ini berada di antara start dan end
            if ($currentTimeStr >= $start && $currentTimeStr <= $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dapatkan label jam operasional fasilitas yang rapi untuk pesan error atau tampilan.
     */
    public function getFormattedOperatingHours(string $facilityCode): string
    {
        $schedule = $this->getSchedule($facilityCode);

        return $schedule['time_label'] ?? 'Waktu operasional belum ditentukan';
    }
}
