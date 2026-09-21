<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Language;
use Illuminate\Database\Seeder;

class CountryLanguageSeeder extends Seeder
{
    public function run(): void
    {
        foreach([
            ['BR','Brasil'],['PY','Paraguai'],['AR','Argentina'],['UY','Uruguai'],
            ['US','Estados Unidos'],['GB','Reino Unido'],['ES','Espanha'],['PT','Portugal'],
            ['FR','França'],['DE','Alemanha'],['IT','Itália'],['MX','México'],['CL','Chile'],
            ['CO','Colômbia'],['PE','Peru'],['BO','Bolívia']
        ] as [$iso2,$name]){
            Country::firstOrCreate(['iso2'=>$iso2],['name'=>$name]);
        }

        foreach([
            ['pt','Português'],['es','Español'],['en','English'],['fr','Français'],
            ['de','Deutsch'],['it','Italiano']
        ] as [$code,$name]){
            Language::firstOrCreate(['code'=>$code],['name'=>$name]);
        }
    }
}
