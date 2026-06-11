<?php

namespace Webkul\Support\Database\Seeders;

use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Currency;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (DB::table('companies')->exists()) {
            return;
        }

        DB::beginTransaction();

        try {
            $user = User::first();

            $partnerId = DB::table('partners_partners')->insertGetId([
                'sub_type'         => 'company',
                'company_registry' => 'GAPREG780',
                'name'             => 'Gap Recruitment Services Limited',
                'email'            => 'info@gaprecruitment.co.ke',
                'website'          => 'https://gaprecruitment.co.ke',
                'tax_id'           => 'GAP123456',
                'phone'            => '254123456789',
                'mobile'           => '254123456789',
                'creator_id'       => $user?->id,
                'color'            => '#004A99',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            $currency = Currency::where('code', 'KES')->first() ?? Currency::first();

            if (! $currency) {
                throw new Exception('No currencies found in the database. Please run CurrencySeeder first.');
            }

            DB::table('companies')->insert([
                'sort'                => 1,
                'name'                => 'Gap Recruitment Services Limited',
                'tax_id'              => 'GAP123456',
                'registration_number' => 'GAPREG789',
                'company_id'          => 'GAPCOMP001',
                'creator_id'          => $user?->id,
                'email'               => 'info@gaprecruitment.co.ke',
                'phone'               => '254123456789',
                'mobile'              => '254123456789',
                'color'               => '#004A99',
                'is_active'           => true,
                'founded_date'        => '2010-01-01',
                'currency_id'         => $currency->id,
                'website'             => 'https://gaprecruitment.co.ke',
                'partner_id'          => $partnerId,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
