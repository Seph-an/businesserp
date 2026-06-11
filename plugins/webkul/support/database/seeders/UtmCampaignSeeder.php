<?php

namespace Webkul\Support\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\UtmStage;

class UtmCampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $stage = UtmStage::first();
        $company = Company::first();

        $now = now();

        $utmCampaigns = [
            [
                'user_id'          => $user?->id,
                'stage_id'         => $stage?->id,
                'color'            => null,
                'creator_id'       => $user?->id,
                'name'             => 'Sale',
                'title'            => 'Sale',
                'is_active'        => true,
                'is_auto_campaign' => true,
                'created_at'       => $now,
                'updated_at'       => $now,
                'company_id'       => $company?->id,
            ],
            [
                'user_id'          => $user?->id,
                'stage_id'         => $stage?->id,
                'color'            => null,
                'creator_id'       => $user?->id,
                'name'             => 'Christmas Special',
                'title'            => 'Christmas Special',
                'is_active'        => true,
                'is_auto_campaign' => true,
                'created_at'       => $now,
                'updated_at'       => $now,
                'company_id'       => $company?->id,
            ],
            [
                'user_id'          => $user?->id,
                'stage_id'         => $stage?->id,
                'color'            => null,
                'creator_id'       => $user?->id,
                'name'             => 'Email Campaign - Services',
                'title'            => 'Email Campaign - Services',
                'is_active'        => true,
                'is_auto_campaign' => true,
                'created_at'       => $now,
                'updated_at'       => $now,
                'company_id'       => $company?->id,
            ],
            [
                'user_id'          => $user?->id,
                'stage_id'         => $stage?->id,
                'color'            => null,
                'creator_id'       => $user?->id,
                'name'             => 'Email Campaign - Products',
                'title'            => 'Email Campaign - Products',
                'is_active'        => true,
                'is_auto_campaign' => true,
                'created_at'       => $now,
                'updated_at'       => $now,
                'company_id'       => $company?->id,
            ],
        ];

        foreach ($utmCampaigns as $campaign) {
            DB::table('utm_campaigns')->updateOrInsert(
                ['name' => $campaign['name']],
                $campaign
            );
        }
    }
}
