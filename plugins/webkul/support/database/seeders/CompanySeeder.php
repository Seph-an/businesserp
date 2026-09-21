<?php

namespace Webkul\Support\Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $this->seedDefaultCompany();
        } catch (Throwable $e) {
            $this->command?->warn('Skipping default company seeding: '.$e->getMessage());
        }
    }

    /**
     * Seed the default company and its partner.
     */
    protected function seedDefaultCompany(): void
    {
        DB::transaction(function () {
            if (
                ! Schema::hasTable('users')
                || ! Schema::hasTable('companies')
                || ! Schema::hasTable('partners_partners')
            ) {
                throw new Exception('Required tables are missing.');
            }

            if (DB::table('companies')->exists()) {
                return;
            }

            $currency = Currency::resolveDefault();

            if (! $currency) {
                throw new Exception('No currency is available to assign to the default company.');
            }

            Company::create([
                'sort'                => 1,
                'name'                => 'Gap Recruitment Services Limited',
                'tax_id'              => 'GAP123456',
                'registration_number' => 'GAPREG789',
                'company_id'          => 'GAPCOMP001',
                'email'               => 'info@gaprecruitment.co.ke',
                'phone'               => '254123456789',
                'mobile'              => '254123456789',
                'color'               => '#004A99',
                'is_active'           => true,
                'founded_date'        => '2010-01-01',
                'currency_id'         => $currency->id,
                'website'             => 'https://gaprecruitment.co.ke',
            ]);
        });
    }
}
