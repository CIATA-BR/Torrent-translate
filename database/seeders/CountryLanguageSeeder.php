<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Language;
use Illuminate\Database\Seeder;

class CountryLanguageSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['AF', 'Afeganistão'], ['AL', 'Albânia'], ['DE', 'Alemanha'], ['AD', 'Andorra'],
            ['AO', 'Angola'], ['AI', 'Anguila'], ['AQ', 'Antártida'], ['AG', 'Antígua e Barbuda'],
            ['AR', 'Argentina'], ['DZ', 'Argélia'], ['AM', 'Armênia'], ['AW', 'Aruba'],
            ['SA', 'Arábia Saudita'], ['AU', 'Austrália'], ['AZ', 'Azerbaidjão'], ['BS', 'Bahamas'],
            ['BD', 'Bangladesh'], ['BB', 'Barbados'], ['BH', 'Barein'], ['BZ', 'Belize'],
            ['BJ', 'Benin'], ['BM', 'Bermuda'], ['BY', 'Bielo-Rússia'], ['BO', 'Bolívia, Estado Plurinacional da'],
            ['BQ', 'Bonaire, Saba e Santo Eustáquio'], ['BW', 'Botsuana'], ['BR', 'Brasil'], ['BN', 'Brunei'],
            ['BG', 'Bulgária'], ['BF', 'Burquina'], ['BI', 'Burundi'], ['BT', 'Butão'],
            ['BE', 'Bélgica'], ['BA', 'Bósnia-Herzegóvina'], ['CV', 'Cabo Verde'], ['CM', 'Camarões'],
            ['KH', 'Camboja'], ['CA', 'Canadá'], ['QA', 'Catar'], ['KZ', 'Cazaquistão'],
            ['TD', 'Chade'], ['CL', 'Chile'], ['CN', 'China'], ['CY', 'Chipre'],
            ['CZ', 'Chéquia'], ['SG', 'Cingapura'], ['CO', 'Colômbia'], ['KM', 'Comores'],
            ['CG', 'Congo'], ['CD', 'Congo, República Democrática do'], ['KR', 'Coreia, República da'], ['KP', 'Coreia, República Popular Democrática da'],
            ['CI', 'Costa do Marfim'], ['CR', 'Costa Rica'], ['HR', 'Croácia'], ['CU', 'Cuba'],
            ['CW', 'Curaçao'], ['DK', 'Dinamarca'], ['DJ', 'Djibuti'], ['DM', 'Domínica'],
            ['EG', 'Egito'], ['SV', 'El Salvador'], ['AE', 'Emirados Árabes Unidos'], ['EC', 'Equador'],
            ['ER', 'Eritréia'], ['SK', 'Eslováquia'], ['SI', 'Eslovênia'], ['ES', 'Espanha'],
            ['US', 'Estados Unidos'], ['EE', 'Estônia'], ['ET', 'Etiópia'], ['RU', 'Federação Russa'],
            ['FJ', 'Fiji'], ['PH', 'Filipinas'], ['FI', 'Finlândia'], ['FR', 'França'],
            ['GA', 'Gabão'], ['GH', 'Gana'], ['GE', 'Geórgia'], ['GS', 'Geórgia do Sul e Ilhas Sandwich do Sul'],
            ['GI', 'Gibraltar'], ['GD', 'Granada'], ['GL', 'Groenlândia'], ['GR', 'Grécia'],
            ['GP', 'Guadalupe'], ['GU', 'Guam'], ['GT', 'Guatemala'], ['GG', 'Guernsey'],
            ['GY', 'Guiana'], ['GF', 'Guiana Francesa'], ['GN', 'Guiné'], ['GQ', 'Guiné Equatorial'],
            ['GW', 'Guiné-Bissau'], ['GM', 'Gâmbia'], ['HT', 'Haiti'], ['HN', 'Honduras'],
            ['HK', 'Hong Kong'], ['HU', 'Hungria'], ['BV', 'Ilha Bouvet'], ['CX', 'Ilha Christmas'],
            ['IM', 'Ilha de Man'], ['HM', 'Ilha Heard e Ilhas McDonald'], ['NF', 'Ilha Norfolk'], ['KY', 'Ilhas Cayman'],
            ['CC', 'Ilhas Cocos'], ['CK', 'Ilhas Cook'], ['FO', 'Ilhas Faroe'], ['FK', 'Ilhas Malvinas (Falkland)'],
            ['MP', 'Ilhas Marianas do Norte'], ['MH', 'Ilhas Marshall'], ['UM', 'Ilhas Menores Distantes dos Estados Unidos'], ['SB', 'Ilhas Salomão'],
            ['TC', 'Ilhas Turks e Caicos'], ['VG', 'Ilhas Virgens Britânicas'], ['VI', 'Ilhas Virgens dos Estados Unidos'], ['AX', 'Ilhas Åland'],
            ['ID', 'Indonésia'], ['IQ', 'Iraque'], ['IE', 'Irlanda'], ['IR', 'Irã, República Islâmica do'],
            ['IS', 'Islândia'], ['IL', 'Israel'], ['IT', 'Itália'], ['YE', 'Iêmen'],
            ['JM', 'Jamaica'], ['JP', 'Japão'], ['JE', 'Jersey'], ['JO', 'Jordânia'],
            ['KI', 'Kiribati'], ['KW', 'Kuwait'], ['LS', 'Lesoto'], ['LV', 'Letônia'],
            ['LR', 'Libéria'], ['LI', 'Liechtenstein'], ['LT', 'Lituânia'], ['LU', 'Luxemburgo'],
            ['LB', 'Líbano'], ['LY', 'Líbia'], ['MO', 'Macau'], ['MK', 'Macedônia do Norte'],
            ['MG', 'Madagascar'], ['YT', 'Maiote'], ['MW', 'Malaui'], ['MV', 'Maldivas'],
            ['ML', 'Mali'], ['MT', 'Malta'], ['MY', 'Malásia'], ['MA', 'Marrocos'],
            ['MQ', 'Martinica'], ['MR', 'Mauritânia'], ['MU', 'Maurício'], ['FM', 'Micronésia, Estados Federados da'],
            ['MD', 'Moldávia, República da'], ['MN', 'Mongólia'], ['ME', 'Montenegro'], ['MS', 'Montserrat'],
            ['MZ', 'Moçambique'], ['MM', 'Myanmar'], ['MX', 'México'], ['MC', 'Mônaco'],
            ['NA', 'Namíbia'], ['NR', 'Nauru'], ['NP', 'Nepal'], ['NI', 'Nicarágua'],
            ['NG', 'Nigéria'], ['NU', 'Niue'], ['NO', 'Noruega'], ['NC', 'Nova Caledônia'],
            ['NZ', 'Nova Zelândia'], ['NE', 'Níger'], ['OM', 'Omã'], ['PW', 'Palau'],
            ['PS', 'Palestina, Estado da'], ['PA', 'Panamá'], ['PG', 'Papua-Nova Guiné'], ['PK', 'Paquistão'],
            ['PY', 'Paraguai'], ['NL', 'Países Baixos'], ['PE', 'Peru'], ['PN', 'Pitcairn'],
            ['PF', 'Polinésia Francesa'], ['PL', 'Polônia'], ['PR', 'Porto Rico'], ['PT', 'Portugal'],
            ['KG', 'Quirguistão'], ['KE', 'Quênia'], ['GB', 'Reino Unido'], ['CF', 'República Centro-Africana'],
            ['DO', 'República Dominicana'], ['LA', 'República Popular Democrática do Laos'], ['SY', 'República Árabe da Síria'], ['RE', 'Reunião'],
            ['RO', 'Romênia'], ['RW', 'Ruanda'], ['EH', 'Saara Ocidental'], ['WS', 'Samoa'],
            ['AS', 'Samoa Americana'], ['SH', 'Santa Helena, Ascensão e Tristão da Cunha'], ['LC', 'Santa Lúcia'], ['VA', 'Santa Sé (Cidade-Estado do Vaticano)'],
            ['SN', 'Senegal'], ['SL', 'Serra Leoa'], ['SC', 'Seychelles'], ['SO', 'Somália'],
            ['LK', 'Sri Lanka'], ['SZ', 'Suazilândia'], ['SD', 'Sudão'], ['SS', 'Sudão do Sul'],
            ['SR', 'Suriname'], ['SE', 'Suécia'], ['CH', 'Suíça'], ['SJ', 'Svalbard e a Ilha de Jan Mayen'],
            ['BL', 'São Bartolomeu'], ['KN', 'São Cristóvão e Névis'], ['SM', 'São Marino'], ['MF', 'São Martim (parte francesa)'],
            ['SX', 'São Martim (parte holandesa)'], ['PM', 'São Pedro e Miquelon'], ['ST', 'São Tomé e Príncipe'], ['VC', 'São Vicente e Granadinas'],
            ['RS', 'Sérvia'], ['TJ', 'Tadjiquistão'], ['TH', 'Tailândia'], ['TW', 'Taiwan, Província da China'],
            ['TZ', 'Tanzânia, República Unida da'], ['IO', 'Território Britânico do Oceano Índico'], ['TF', 'Territórios Franceses do Sul'], ['TL', 'Timor Leste'],
            ['TG', 'Togo'], ['TO', 'Tonga'], ['TK', 'Toquelau'], ['TT', 'Trinidade e Tobago'],
            ['TN', 'Tunísia'], ['TM', 'Turcomenistão'], ['TR', 'Turquia'], ['TV', 'Tuvalu'],
            ['UA', 'Ucrânia'], ['UG', 'Uganda'], ['UY', 'Uruguai'], ['UZ', 'Uzbequistão'],
            ['VU', 'Vanuatu'], ['VE', 'Venezuela, República Bolivariana da'], ['VN', 'Vietnã'], ['WF', 'Wallis e Futuna'],
            ['ZW', 'Zimbábue'], ['ZM', 'Zâmbia'], ['ZA', 'África do Sul'], ['AT', 'Áustria'],
            ['IN', 'Índia'],
        ];

        foreach ($countries as [$iso2, $name]) {
            Country::updateOrCreate(['iso2' => $iso2], ['name' => $name]);
        }

        $languages = [
            ['ab','Abcázio'],['aa','Afar'],['af','Africâner'],['ay','Aimará'],['ak','Akan'],
            ['sq','Albanês'],['de','Alemão'],['am','Amárico'],['an','Aragonês'],['hy','Armênio'],
            ['as','Assamês'],['av','Avárico'],['ae','Avéstico'],['az','Azerbaidjano'],['lu','Baluba'],
            ['bm','Bambara'],['eu','Basco'],['ba','Basquir'],['bn','Bengali'],['be','Bielorrusso'],
            ['my','Birmanês'],['bi','Bislamá'],['br','Bretão'],['dz','Dzonga'],['bs','Bósnio'],
            ['bg','Búlgaro'],['kn','Canarês'],['kr','Canúri'],['ca','Catalão'],['ks','Caxemira'],
            ['kk','Cazaque'],['ch','Chamorro'],['ce','Checheno'],['zh','Chinês'],['cu','Eslavo eclesiástico'],
            ['si','Cingalês'],['kg','Congo'],['ko','Coreano'],['co','Corso'],['cr','Cree'],
            ['hr','Croata'],['ku','Curdo'],['kw','Córnico'],['dv','Divehi'],['da','Dinamarquês'],
            ['sk','Eslovaco'],['sl','Esloveno'],['es','Espanhol'],['eo','Esperanto'],['et','Estoniano'],
            ['fo','Faroês'],['fj','Fijiano'],['fi','Finlandês'],['fr','Francês'],['fy','Frísio ocidental'],
            ['ff','Fula'],['gl','Galego'],['cy','Galês'],['ka','Georgiano'],['gn','Guarani'],
            ['gu','Guzerate'],['ht','Crioulo haitiano'],['ha','Hauçá'],['he','Hebraico'],['hz','Hereró'],
            ['ho','Hiri Motu'],['nl','Holandês'],['hi','Híndi'],['hu','Húngaro'],['ig','Igbo'],
            ['io','Ido'],['id','Indonésio'],['en','Inglês'],['ie','Interlingue'],['ia','Interlíngua'],
            ['iu','Inuktitut'],['ik','Inupiaque'],['yo','Iorubá'],['ga','Irlandês'],['is','Islandês'],
            ['it','Italiano'],['yi','Iídiche'],['ja','Japonês'],['jv','Javanês'],['ee','Ewe'],
            ['kl','Groenlandês'],['km','Khmer'],['rn','Kirundi'],['kv','Komi'],['kj','Kuanyama'],
            ['lo','Lao'],['la','Latim'],['lv','Letão'],['li','Limburguês'],['ln','Lingala'],
            ['lt','Lituano'],['lb','Luxemburguês'],['mk','Macedônio'],['ml','Malaiala'],['ms','Malaio'],
            ['mg','Malgaxe'],['mt','Maltês'],['gv','Manx'],['mi','Maori'],['mr','Marati'],
            ['mh','Marshalês'],['el','Grego moderno'],['mn','Mongol'],['na','Nauruano'],['nv','Navajo'],
            ['nr','Ndebele do Sul'],['ne','Nepalês'],['lg','Luganda'],['nd','Ndebele do Norte'],['no','Norueguês'],
            ['nn','Norueguês Nynorsk'],['nb','Norueguês Bokmål'],['ny','Nianja'],['oj','Ojíbua'],['oc','Occitano'],
            ['or','Odia'],['om','Oromo'],['os','Osseta'],['ng','Ovambo'],['pa','Punjabi'],
            ['fa','Persa'],['pl','Polonês'],['pt','Português'],['ps','Pachto'],['pi','Páli'],
            ['ki','Quicuio'],['ky','Quirguiz'],['qu','Quíchua'],['rm','Romanche'],['ro','Romeno'],
            ['rw','Quiniaruanda'],['ru','Russo'],['se','Sami do Norte'],['sm','Samoano'],['sg','Sango'],
            ['sc','Sardo'],['gd','Gaélico escocês'],['sh','Servo-croata'],['ii','Yi de Sichuan'],['so','Somali'],
            ['st','Soto do Sul'],['sv','Sueco'],['su','Sundanês'],['sw','Suaíli'],['ss','Suázi'],
            ['sa','Sânscrito'],['sr','Sérvio'],['sd','Sindi'],['tg','Tadjique'],['tl','Tagalo'],
            ['th','Tailandês'],['ty','Taitiano'],['ta','Tâmil'],['cs','Tcheco'],['cv','Tchuvache'],
            ['bo','Tibetano'],['ti','Tigrínia'],['to','Tonganês'],['ts','Tsonga'],['tn','Tswana'],
            ['tr','Turco'],['tk','Turcomeno'],['tw','Twi'],['tt','Tártaro'],['te','Télugo'],
            ['uk','Ucraniano'],['ug','Uigur'],['ur','Urdu'],['uz','Uzbeque'],['wo','Uolofe'],
            ['wa','Valão'],['ve','Venda'],['vi','Vietnamita'],['vo','Volapuque'],['xh','Xhosa'],
            ['sn','Shona'],['za','Zhuang'],['zu','Zulu'],['ar','Árabe'],
        ];

        foreach ($languages as [$code, $name]) {
            Language::updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
