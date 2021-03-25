<?php

use Illuminate\Database\Seeder;
use App\Tag;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tags = [
            [
                'name' => 'Retraso de Pago',
                'shortened' => 'Mora',
            ], [
                'name' => 'Pagando Cuotas',
                'shortened' => 'Amortizando',
            ],[
                'name' => 'Préstamo refinanciado',
                'shortened' => 'Refinanciamiento',
            ],[
                'name' => 'Préstamo reprogramado',
                'shortened' => 'Reprogramación',
            ],[
                'name' => 'Préstamo Sismu',
                'shortened' => 'Sismu',
            ]
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate([
                'name' => $tag['name'],
                'shortened' => $tag['shortened'],
                'slug' => Str::slug($tag['shortened'], '-'),
            ]);
        }
    }
}
