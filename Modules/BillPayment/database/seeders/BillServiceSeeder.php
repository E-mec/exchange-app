<?php

namespace Modules\BillPayment\database\seeders;

use Illuminate\Database\Seeder;
use Modules\BillPayment\Enums\BillProviderEnum;
use Modules\BillPayment\Enums\BillTypeEnum;

class BillServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [

            // ─────────────────────────────────────────
            // AIRTIME
            // ─────────────────────────────────────────
            [
                'slug'                => 'mtn-airtime',
                'name'               => 'MTN Airtime',
                'type'               => BillTypeEnum::AIRTIME,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'mtn',
                'country_code'       => 'NG',
                'min_amount'         => 50,
                'max_amount'         => 50000,
                'requires_validation'=> false,
                'is_active'          => true,
            ],
            [
                'slug'                => 'airtel-airtime',
                'name'               => 'Airtel Airtime',
                'type'               => BillTypeEnum::AIRTIME,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'airtel',
                'country_code'       => 'NG',
                'min_amount'         => 50,
                'max_amount'         => 50000,
                'requires_validation'=> false,
                'is_active'          => true,
            ],
            [
                'slug'                => 'glo-airtime',
                'name'               => 'Glo Airtime',
                'type'               => BillTypeEnum::AIRTIME,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'glo',
                'country_code'       => 'NG',
                'min_amount'         => 50,
                'max_amount'         => 50000,
                'requires_validation'=> false,
                'is_active'          => true,
            ],
            [
                'slug'                => '9mobile-airtime',
                'name'               => '9mobile Airtime',
                'type'               => BillTypeEnum::AIRTIME,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'etisalat',
                'country_code'       => 'NG',
                'min_amount'         => 50,
                'max_amount'         => 50000,
                'requires_validation'=> false,
                'is_active'          => true,
            ],

            // ─────────────────────────────────────────
            // DATA
            // ─────────────────────────────────────────
            [
                'slug'                => 'mtn-data',
                'name'               => 'MTN Data',
                'type'               => BillTypeEnum::DATA,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'mtn-data',
                'country_code'       => 'NG',
                'min_amount'         => 100,
                'max_amount'         => 100000,
                'requires_validation'=> false,
                'is_active'          => true,
            ],
            [
                'slug'                => 'airtel-data',
                'name'               => 'Airtel Data',
                'type'               => BillTypeEnum::DATA,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'airtel-data',
                'country_code'       => 'NG',
                'min_amount'         => 100,
                'max_amount'         => 100000,
                'requires_validation'=> false,
                'is_active'          => true,
            ],
            [
                'slug'                => 'glo-data',
                'name'               => 'Glo Data',
                'type'               => BillTypeEnum::DATA,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'glo-data',
                'country_code'       => 'NG',
                'min_amount'         => 100,
                'max_amount'         => 100000,
                'requires_validation'=> false,
                'is_active'          => true,
            ],
            [
                'slug'                => '9mobile-data',
                'name'               => '9mobile Data',
                'type'               => BillTypeEnum::DATA,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'etisalat-data',
                'country_code'       => 'NG',
                'min_amount'         => 100,
                'max_amount'         => 100000,
                'requires_validation'=> false,
                'is_active'          => true,
            ],
            [
                'slug'                => 'smile-data',
                'name'               => 'Smile Data',
                'type'               => BillTypeEnum::DATA,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'smile-direct',
                'country_code'       => 'NG',
                'min_amount'         => 500,
                'max_amount'         => 50000,
                'requires_validation'=> true,
                'is_active'          => true,
            ],
            [
                'slug'                => 'spectranet-data',
                'name'               => 'Spectranet Data',
                'type'               => BillTypeEnum::DATA,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'spectranet',
                'country_code'       => 'NG',
                'min_amount'         => 500,
                'max_amount'         => 50000,
                'requires_validation'=> true,
                'is_active'          => true,
            ],

            // ─────────────────────────────────────────
            // ELECTRICITY
            // ─────────────────────────────────────────
            [
                'slug'                => 'ikedc',
                'name'               => 'Ikeja Electric (IKEDC)',
                'type'               => BillTypeEnum::ELECTRICITY,
                'provider'           => BillProviderEnum::BUYPOWER,
                'provider_service_id'=> 'IKEDC',
                'country_code'       => 'NG',
                'min_amount'         => 500,
                'max_amount'         => 500000,
                'requires_validation'=> true,
                'is_active'          => true,
                'meta'               => json_encode(['prepaid' => true, 'postpaid' => true]),
            ],
            [
                'slug'                => 'ekedc',
                'name'               => 'Eko Electric (EKEDC)',
                'type'               => BillTypeEnum::ELECTRICITY,
                'provider'           => BillProviderEnum::BUYPOWER,
                'provider_service_id'=> 'EKEDC',
                'country_code'       => 'NG',
                'min_amount'         => 500,
                'max_amount'         => 500000,
                'requires_validation'=> true,
                'is_active'          => true,
                'meta'               => json_encode(['prepaid' => true, 'postpaid' => true]),
            ],
            [
                'slug'                => 'aedc',
                'name'               => 'Abuja Electric (AEDC)',
                'type'               => BillTypeEnum::ELECTRICITY,
                'provider'           => BillProviderEnum::BUYPOWER,
                'provider_service_id'=> 'AEDC',
                'country_code'       => 'NG',
                'min_amount'         => 500,
                'max_amount'         => 500000,
                'requires_validation'=> true,
                'is_active'          => true,
                'meta'               => json_encode(['prepaid' => true, 'postpaid' => true]),
            ],
            [
                'slug'                => 'phed',
                'name'               => 'Port Harcourt Electric (PHED)',
                'type'               => BillTypeEnum::ELECTRICITY,
                'provider'           => BillProviderEnum::BUYPOWER,
                'provider_service_id'=> 'PHED',
                'country_code'       => 'NG',
                'min_amount'         => 500,
                'max_amount'         => 500000,
                'requires_validation'=> true,
                'is_active'          => true,
                'meta'               => json_encode(['prepaid' => true, 'postpaid' => true]),
            ],
            [
                'slug'                => 'eedc',
                'name'               => 'Enugu Electric (EEDC)',
                'type'               => BillTypeEnum::ELECTRICITY,
                'provider'           => BillProviderEnum::BUYPOWER,
                'provider_service_id'=> 'EEDC',
                'country_code'       => 'NG',
                'min_amount'         => 500,
                'max_amount'         => 500000,
                'requires_validation'=> true,
                'is_active'          => true,
                'meta'               => json_encode(['prepaid' => true, 'postpaid' => true]),
            ],
            [
                'slug'                => 'kedco',
                'name'               => 'Kano Electric (KEDCO)',
                'type'               => BillTypeEnum::ELECTRICITY,
                'provider'           => BillProviderEnum::BUYPOWER,
                'provider_service_id'=> 'KEDCO',
                'country_code'       => 'NG',
                'min_amount'         => 500,
                'max_amount'         => 500000,
                'requires_validation'=> true,
                'is_active'          => true,
                'meta'               => json_encode(['prepaid' => true, 'postpaid' => true]),
            ],
            [
                'slug'                => 'ibedc',
                'name'               => 'Ibadan Electric (IBEDC)',
                'type'               => BillTypeEnum::ELECTRICITY,
                'provider'           => BillProviderEnum::BUYPOWER,
                'provider_service_id'=> 'IBEDC',
                'country_code'       => 'NG',
                'min_amount'         => 500,
                'max_amount'         => 500000,
                'requires_validation'=> true,
                'is_active'          => true,
                'meta'               => json_encode(['prepaid' => true, 'postpaid' => true]),
            ],
            [
                'slug'                => 'jed',
                'name'               => 'Jos Electric (JED)',
                'type'               => BillTypeEnum::ELECTRICITY,
                'provider'           => BillProviderEnum::BUYPOWER,
                'provider_service_id'=> 'JED',
                'country_code'       => 'NG',
                'min_amount'         => 500,
                'max_amount'         => 500000,
                'requires_validation'=> true,
                'is_active'          => true,
                'meta'               => json_encode(['prepaid' => true, 'postpaid' => false]),
            ],
            [
                'slug'                => 'bedc',
                'name'               => 'Benin Electric (BEDC)',
                'type'               => BillTypeEnum::ELECTRICITY,
                'provider'           => BillProviderEnum::BUYPOWER,
                'provider_service_id'=> 'BEDC',
                'country_code'       => 'NG',
                'min_amount'         => 500,
                'max_amount'         => 500000,
                'requires_validation'=> true,
                'is_active'          => true,
                'meta'               => json_encode(['prepaid' => true, 'postpaid' => true]),
            ],

            // ─────────────────────────────────────────
            // TV
            // ─────────────────────────────────────────
            [
                'slug'                => 'dstv',
                'name'               => 'DStv',
                'type'               => BillTypeEnum::TV,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'dstv',
                'country_code'       => 'NG',
                'min_amount'         => 2000,
                'max_amount'         => 50000,
                'requires_validation'=> true,
                'is_active'          => true,
            ],
            [
                'slug'                => 'gotv',
                'name'               => 'GOtv',
                'type'               => BillTypeEnum::TV,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'gotv',
                'country_code'       => 'NG',
                'min_amount'         => 1000,
                'max_amount'         => 20000,
                'requires_validation'=> true,
                'is_active'          => true,
            ],
            [
                'slug'                => 'startimes',
                'name'               => 'StarTimes',
                'type'               => BillTypeEnum::TV,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'startimes',
                'country_code'       => 'NG',
                'min_amount'         => 900,
                'max_amount'         => 15000,
                'requires_validation'=> true,
                'is_active'          => true,
            ],
            [
                'slug'                => 'showmax',
                'name'               => 'Showmax',
                'type'               => BillTypeEnum::TV,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'showmax',
                'country_code'       => 'NG',
                'min_amount'         => 1200,
                'max_amount'         => 10000,
                'requires_validation'=> false,
                'is_active'          => true,
            ],

            // ─────────────────────────────────────────
            // INTERNET
            // ─────────────────────────────────────────
            [
                'slug'                => 'swift-internet',
                'name'               => 'Swift Networks',
                'type'               => BillTypeEnum::INTERNET,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'swift',
                'country_code'       => 'NG',
                'min_amount'         => 1000,
                'max_amount'         => 100000,
                'requires_validation'=> true,
                'is_active'          => true,
            ],
            [
                'slug'                => 'ipnx-internet',
                'name'               => 'ipNX Nigeria',
                'type'               => BillTypeEnum::INTERNET,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'ipnx',
                'country_code'       => 'NG',
                'min_amount'         => 5000,
                'max_amount'         => 200000,
                'requires_validation'=> true,
                'is_active'          => true,
            ],

            // ─────────────────────────────────────────
            // WATER
            // ─────────────────────────────────────────
            [
                'slug'                => 'lagos-water',
                'name'               => 'Lagos Water Corporation',
                'type'               => BillTypeEnum::WATER,
                'provider'           => BillProviderEnum::BAXI,
                'provider_service_id'=> 'lagos-water',
                'country_code'       => 'NG',
                'min_amount'         => 500,
                'max_amount'         => 100000,
                'requires_validation'=> true,
                'is_active'          => true,
            ],
            [
                'slug'                => 'abuja-water',
                'name'               => 'FCT Water Board',
                'type'               => BillTypeEnum::WATER,
                'provider'           => BillProviderEnum::BAXI,
                'provider_service_id'=> 'abuja-water',
                'country_code'       => 'NG',
                'min_amount'         => 500,
                'max_amount'         => 100000,
                'requires_validation'=> true,
                'is_active'          => false,
            ],

            // ─────────────────────────────────────────
            // BETTING
            // ─────────────────────────────────────────
            [
                'slug'                => 'bet9ja',
                'name'               => 'Bet9ja',
                'type'               => BillTypeEnum::BETTING,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'bet9ja',
                'country_code'       => 'NG',
                'min_amount'         => 100,
                'max_amount'         => 500000,
                'requires_validation'=> true,
                'is_active'          => true,
            ],
            [
                'slug'                => 'sportybet',
                'name'               => 'SportyBet',
                'type'               => BillTypeEnum::BETTING,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'sportybet',
                'country_code'       => 'NG',
                'min_amount'         => 100,
                'max_amount'         => 500000,
                'requires_validation'=> true,
                'is_active'          => true,
            ],
            [
                'slug'                => 'betway',
                'name'               => 'Betway',
                'type'               => BillTypeEnum::BETTING,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> 'betway',
                'country_code'       => 'NG',
                'min_amount'         => 100,
                'max_amount'         => 500000,
                'requires_validation'=> true,
                'is_active'          => true,
            ],
            [
                'slug'                => '1xbet',
                'name'               => '1xBet',
                'type'               => BillTypeEnum::BETTING,
                'provider'           => BillProviderEnum::VTPASS,
                'provider_service_id'=> '1xbet',
                'country_code'       => 'NG',
                'min_amount'         => 100,
                'max_amount'         => 500000,
                'requires_validation'=> true,
                'is_active'          => true,
            ],
        ];

        foreach ($services as &$service) {
            // Eloquent upsert() bypasses model casting, so resolve enum
            // values to their backing string before hitting the DB
            $service['type']       = $service['type']->value;
            $service['provider']   = $service['provider']->value;
            $service['meta']       ??= null;
            $service['created_at'] = now();
            $service['updated_at'] = now();
        }

        BillService::upsert(
            $services,
            uniqueBy: ['slug'],
            update: ['name', 'provider_service_id', 'min_amount', 'max_amount', 'is_active', 'updated_at']
        );
    }
}
