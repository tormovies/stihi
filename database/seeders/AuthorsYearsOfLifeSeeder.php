<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Seeder;

/**
 * Заполнение поля «Годы жизни» у авторов по проверенным данным (Википедия и др.).
 * Галина Галина: в части источников год рождения 1873 — внесено 1870 (чаще встречается).
 */
class AuthorsYearsOfLifeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'agnivcev' => '1888–1932',
            'ahmatova' => '1889–1966',
            'bagrickiy-v' => '1922–1942',
            'bagrickiy' => '1895–1934',
            'balmont' => '1867–1942',
            'baratynskiy' => '1800–1844',
            'belyy-andrey' => '1880–1934',
            'bestuzhev' => '1797–1837',
            'blok' => '1880–1921',
            'bryusov' => '1873–1924',
            'vyazemskiy' => '1792–1878',
            'galina' => '1870–1942',
            'griboedov' => '1795–1829',
            'grigorev' => '1822–1864',
            'gumilev' => '1886–1921',
            'delvig' => '1798–1831',
            'derzhavin' => '1743–1816',
            'esenin' => '1895–1925',
            'zhukovskiy' => '1783–1852',
            'kapnist' => '1758–1823',
            'katenin' => '1792–1853',
            'kolcov' => '1809–1842',
            'krylov' => '1769–1844',
            'lermontov' => '1814–1841',
            'maykov' => '1821–1897',
            'mayakovskiy' => '1893–1930',
            'nadson' => '1862–1887',
            'nekrasov' => '1821–1877',
            'nikitin' => '1824–1861',
            'ogarev' => '1813–1877',
            'odoevskiy' => '1802–1839',
            'pasternak' => '1890–1960',
            'polonskiy' => '1819–1898',
            'pushkin' => '1799–1837',
            'radischev' => '1749–1802',
            'severyanin' => '1887–1941',
            'solovev' => '1853–1900',
            'surikov' => '1841–1880',
            'tyutchev' => '1803–1873',
            'fet' => '1820–1892',
            'hlebnikov' => '1885–1922',
            'cvetaeva' => '1892–1941',
            'cherniy-sasha' => '1880–1932',
            'chukovskiy' => '1882–1969',
        ];

        foreach ($data as $slug => $years) {
            Author::where('slug', $slug)->update(['years_of_life' => $years]);
        }
    }
}
