<?php

namespace Database\Seeders;

use App\Models\NewsTopic;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed some admin/test user if needed
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $topics = [
            ['name' => 'FIFA World Cup 2026', 'keyword' => 'fifa world cup 2026'],
            ['name' => 'World Cup 2026 News', 'keyword' => 'world cup 2026 news'],
            ['name' => 'Argentina World Cup', 'keyword' => 'Argentina world cup 2026'],
            ['name' => 'Brazil World Cup', 'keyword' => 'Brazil world cup 2026'],
            ['name' => 'Portugal World Cup', 'keyword' => 'Portugal world cup 2026'],
            ['name' => 'USA World Cup Host', 'keyword' => 'USA world cup 2026'],
            ['name' => 'Mexico World Cup Host', 'keyword' => 'Mexico world cup 2026'],
            ['name' => 'Canada World Cup Host', 'keyword' => 'Canada world cup 2026'],
            ['name' => 'World Cup Qualifiers', 'keyword' => 'world cup 2026 qualifiers'],
            ['name' => 'World Cup Stadiums', 'keyword' => 'world cup 2026 stadiums'],
            ['name' => 'Lionel Messi 2026', 'keyword' => 'Lionel Messi world cup 2026'],
            ['name' => 'Cristiano Ronaldo 2026', 'keyword' => 'Cristiano Ronaldo world cup 2026'],
            ['name' => 'Mbappe World Cup', 'keyword' => 'Mbappe world cup 2026'],
            ['name' => 'Neymar World Cup', 'keyword' => 'Neymar world cup 2026'],
            ['name' => 'England World Cup', 'keyword' => 'England world cup 2026'],
            ['name' => 'France World Cup', 'keyword' => 'France world cup 2026'],
            ['name' => 'Germany World Cup', 'keyword' => 'Germany world cup 2026'],
            ['name' => 'Spain World Cup', 'keyword' => 'Spain world cup 2026'],
            ['name' => 'World Cup Schedule', 'keyword' => 'world cup 2026 schedule'],
            ['name' => 'World Cup Tickets', 'keyword' => 'world cup 2026 tickets'],
        ];

        foreach ($topics as $topic) {
            NewsTopic::firstOrCreate(
                ['keyword' => $topic['keyword']],
                [
                    'name' => $topic['name'],
                    'language' => 'en',
                    'country' => 'US',
                    'is_active' => true,
                ]
            );
        }
    }
}

