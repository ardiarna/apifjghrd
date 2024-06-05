<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TarifEfektif;

class TarifEfektifSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['ter' => 'A', 'penghasilan' => 0, 'persen' => 0],
            ['ter' => 'A', 'penghasilan' => 5400001, 'persen' => 0.25],
            ['ter' => 'A', 'penghasilan' => 5650001, 'persen' => 0.5],
            ['ter' => 'A', 'penghasilan' => 5950001, 'persen' => 0.75],
            ['ter' => 'A', 'penghasilan' => 6300001, 'persen' => 1],
            ['ter' => 'A', 'penghasilan' => 6750001, 'persen' => 1.25],
            ['ter' => 'A', 'penghasilan' => 7500001, 'persen' => 1.5],
            ['ter' => 'A', 'penghasilan' => 8550001, 'persen' => 1.75],
            ['ter' => 'A', 'penghasilan' => 9650001, 'persen' => 2],
            ['ter' => 'A', 'penghasilan' => 10050001, 'persen' => 2.25],
            ['ter' => 'A', 'penghasilan' => 10350001, 'persen' => 2.5],
            ['ter' => 'A', 'penghasilan' => 10700001, 'persen' => 3],
            ['ter' => 'A', 'penghasilan' => 11050001, 'persen' => 3.5],
            ['ter' => 'A', 'penghasilan' => 11600001, 'persen' => 4],
            ['ter' => 'A', 'penghasilan' => 12500001, 'persen' => 5],
            ['ter' => 'A', 'penghasilan' => 13750001, 'persen' => 6],
            ['ter' => 'A', 'penghasilan' => 15100001, 'persen' => 7],
            ['ter' => 'A', 'penghasilan' => 16950001, 'persen' => 8],
            ['ter' => 'A', 'penghasilan' => 19750001, 'persen' => 9],
            ['ter' => 'A', 'penghasilan' => 24150001, 'persen' => 10],
            ['ter' => 'A', 'penghasilan' => 26450001, 'persen' => 11],
            ['ter' => 'A', 'penghasilan' => 28000001, 'persen' => 12],
            ['ter' => 'A', 'penghasilan' => 30050001, 'persen' => 13],
            ['ter' => 'A', 'penghasilan' => 32400001, 'persen' => 14],
            ['ter' => 'A', 'penghasilan' => 35400001, 'persen' => 15],
            ['ter' => 'A', 'penghasilan' => 39100001, 'persen' => 16],
            ['ter' => 'A', 'penghasilan' => 43850001, 'persen' => 17],
            ['ter' => 'A', 'penghasilan' => 47800001, 'persen' => 18],
            ['ter' => 'A', 'penghasilan' => 51400001, 'persen' => 19],
            ['ter' => 'A', 'penghasilan' => 56300001, 'persen' => 20],
            ['ter' => 'A', 'penghasilan' => 62200001, 'persen' => 21],
            ['ter' => 'A', 'penghasilan' => 68600001, 'persen' => 22],
            ['ter' => 'A', 'penghasilan' => 77500001, 'persen' => 23],
            ['ter' => 'A', 'penghasilan' => 89000001, 'persen' => 24],
            ['ter' => 'A', 'penghasilan' => 103000001, 'persen' => 25],
            ['ter' => 'A', 'penghasilan' => 125000001, 'persen' => 26],
            ['ter' => 'A', 'penghasilan' => 157000001, 'persen' => 27],
            ['ter' => 'A', 'penghasilan' => 206000001, 'persen' => 28],
            ['ter' => 'A', 'penghasilan' => 337000001, 'persen' => 29],
            ['ter' => 'A', 'penghasilan' => 454000001, 'persen' => 30],
            ['ter' => 'A', 'penghasilan' => 550000001, 'persen' => 31],
            ['ter' => 'A', 'penghasilan' => 695000001, 'persen' => 32],
            ['ter' => 'A', 'penghasilan' => 910000001, 'persen' => 33],
            ['ter' => 'A', 'penghasilan' => 1400000001, 'persen' => 34],
            ['ter' => 'B', 'penghasilan' => 0, 'persen' => 0],
            ['ter' => 'B', 'penghasilan' => 6200001, 'persen' => 0.25],
            ['ter' => 'B', 'penghasilan' => 6500001, 'persen' => 0.5],
            ['ter' => 'B', 'penghasilan' => 6850001, 'persen' => 0.75],
            ['ter' => 'B', 'penghasilan' => 7300001, 'persen' => 1],
            ['ter' => 'B', 'penghasilan' => 9200001, 'persen' => 1.5],
            ['ter' => 'B', 'penghasilan' => 10750001, 'persen' => 2],
            ['ter' => 'B', 'penghasilan' => 11250001, 'persen' => 2.5],
            ['ter' => 'B', 'penghasilan' => 11600001, 'persen' => 3],
            ['ter' => 'B', 'penghasilan' => 12600001, 'persen' => 4],
            ['ter' => 'B', 'penghasilan' => 13600001, 'persen' => 5],
            ['ter' => 'B', 'penghasilan' => 14950001, 'persen' => 6],
            ['ter' => 'B', 'penghasilan' => 16400001, 'persen' => 7],
            ['ter' => 'B', 'penghasilan' => 18450001, 'persen' => 8],
            ['ter' => 'B', 'penghasilan' => 21850001, 'persen' => 9],
            ['ter' => 'B', 'penghasilan' => 26000001, 'persen' => 10],
            ['ter' => 'B', 'penghasilan' => 27700001, 'persen' => 11],
            ['ter' => 'B', 'penghasilan' => 29350001, 'persen' => 12],
            ['ter' => 'B', 'penghasilan' => 31450001, 'persen' => 13],
            ['ter' => 'B', 'penghasilan' => 33950001, 'persen' => 14],
            ['ter' => 'B', 'penghasilan' => 37100001, 'persen' => 15],
            ['ter' => 'B', 'penghasilan' => 41100001, 'persen' => 16],
            ['ter' => 'B', 'penghasilan' => 45800001, 'persen' => 17],
            ['ter' => 'B', 'penghasilan' => 49500001, 'persen' => 18],
            ['ter' => 'B', 'penghasilan' => 53800001, 'persen' => 19],
            ['ter' => 'B', 'penghasilan' => 58500001, 'persen' => 20],
            ['ter' => 'B', 'penghasilan' => 64000001, 'persen' => 21],
            ['ter' => 'B', 'penghasilan' => 71000001, 'persen' => 22],
            ['ter' => 'B', 'penghasilan' => 80000001, 'persen' => 23],
            ['ter' => 'B', 'penghasilan' => 93000001, 'persen' => 24],
            ['ter' => 'B', 'penghasilan' => 109000001, 'persen' => 25],
            ['ter' => 'B', 'penghasilan' => 129000001, 'persen' => 26],
            ['ter' => 'B', 'penghasilan' => 163000001, 'persen' => 27],
            ['ter' => 'B', 'penghasilan' => 211000001, 'persen' => 28],
            ['ter' => 'B', 'penghasilan' => 374000001, 'persen' => 29],
            ['ter' => 'B', 'penghasilan' => 459000001, 'persen' => 30],
            ['ter' => 'B', 'penghasilan' => 555000001, 'persen' => 31],
            ['ter' => 'B', 'penghasilan' => 704000001, 'persen' => 32],
            ['ter' => 'B', 'penghasilan' => 957000001, 'persen' => 33],
            ['ter' => 'B', 'penghasilan' => 1405000001, 'persen' => 34],
            ['ter' => 'C', 'penghasilan' => 0, 'persen' => 0],
            ['ter' => 'C', 'penghasilan' => 6600001, 'persen' => 0.25],
            ['ter' => 'C', 'penghasilan' => 6950001, 'persen' => 0.5],
            ['ter' => 'C', 'penghasilan' => 7350001, 'persen' => 0.75],
            ['ter' => 'C', 'penghasilan' => 7800001, 'persen' => 1],
            ['ter' => 'C', 'penghasilan' => 8850001, 'persen' => 1.25],
            ['ter' => 'C', 'penghasilan' => 9800001, 'persen' => 1.5],
            ['ter' => 'C', 'penghasilan' => 10950001, 'persen' => 1.75],
            ['ter' => 'C', 'penghasilan' => 11200001, 'persen' => 2],
            ['ter' => 'C', 'penghasilan' => 12050001, 'persen' => 3],
            ['ter' => 'C', 'penghasilan' => 12950001, 'persen' => 4],
            ['ter' => 'C', 'penghasilan' => 14150001, 'persen' => 5],
            ['ter' => 'C', 'penghasilan' => 15550001, 'persen' => 6],
            ['ter' => 'C', 'penghasilan' => 17050001, 'persen' => 7],
            ['ter' => 'C', 'penghasilan' => 19500001, 'persen' => 8],
            ['ter' => 'C', 'penghasilan' => 22700001, 'persen' => 9],
            ['ter' => 'C', 'penghasilan' => 26600001, 'persen' => 10],
            ['ter' => 'C', 'penghasilan' => 28100001, 'persen' => 11],
            ['ter' => 'C', 'penghasilan' => 30100001, 'persen' => 12],
            ['ter' => 'C', 'penghasilan' => 32600001, 'persen' => 13],
            ['ter' => 'C', 'penghasilan' => 35400001, 'persen' => 14],
            ['ter' => 'C', 'penghasilan' => 38900001, 'persen' => 15],
            ['ter' => 'C', 'penghasilan' => 43000001, 'persen' => 16],
            ['ter' => 'C', 'penghasilan' => 47400001, 'persen' => 17],
            ['ter' => 'C', 'penghasilan' => 51200001, 'persen' => 18],
            ['ter' => 'C', 'penghasilan' => 55800001, 'persen' => 19],
            ['ter' => 'C', 'penghasilan' => 60400001, 'persen' => 20],
            ['ter' => 'C', 'penghasilan' => 66700001, 'persen' => 21],
            ['ter' => 'C', 'penghasilan' => 74500001, 'persen' => 22],
            ['ter' => 'C', 'penghasilan' => 83200001, 'persen' => 23],
            ['ter' => 'C', 'penghasilan' => 95600001, 'persen' => 24],
            ['ter' => 'C', 'penghasilan' => 110000001, 'persen' => 25],
            ['ter' => 'C', 'penghasilan' => 134000001, 'persen' => 26],
            ['ter' => 'C', 'penghasilan' => 169000001, 'persen' => 27],
            ['ter' => 'C', 'penghasilan' => 221000001, 'persen' => 28],
            ['ter' => 'C', 'penghasilan' => 390000001, 'persen' => 29],
            ['ter' => 'C', 'penghasilan' => 463000001, 'persen' => 30],
            ['ter' => 'C', 'penghasilan' => 561000001, 'persen' => 31],
            ['ter' => 'C', 'penghasilan' => 709000001, 'persen' => 32],
            ['ter' => 'C', 'penghasilan' => 965000001, 'persen' => 33],
            ['ter' => 'C', 'penghasilan' => 1419000001, 'persen' => 34],
        ];

        foreach ($data as $row) {
            TarifEfektif::create($row);
        }
    }
}
