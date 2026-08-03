<?php

namespace Database\Seeders;

use App\Models\ResponderHotline;
use Illuminate\Database\Seeder;

class ResponderHotlineSeeder extends Seeder
{
    public function run(): void
    {
        $hotlines = [
            [
                'type' => 'fire',
                'label' => 'Fire',
                'phone_number' => '116',
            ],
            [
                'type' => 'medical',
                'label' => 'Medical',
                'phone_number' => '911',
            ],
            [
                'type' => 'natural_disaster',
                'label' => 'Natural Disaster',
                'phone_number' => '911',
            ],
        ];

        foreach ($hotlines as $hotline) {
            ResponderHotline::updateOrCreate(
                ['type' => $hotline['type']],
                $hotline
            );
        }
    }
}
