<?php

namespace Database\Seeders;

use App;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Schoolterm;

class TermTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $term1 = Schoolterm::create([
            'term' => 'First Term',
        ]);
        $term2 = Schoolterm::create([
            'term' => 'Second Term',
        ]);
        $term3 = Schoolterm::create([
            'term' => 'Third Term',
        ]);
    }
}
