<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campsite;

class CampsiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campsites = [
            [
                'name' => 'Balatoni Tóparti Kemping',
                'location' => 'Balaton, Siófok',
                'rating' => 4.8,
                'reviews' => 124,
                'price' => 12000,
                'image' => 'https://picsum.photos/600/400?camp',
                'tags' => ['WiFi', 'Parkoló', 'Sátorhely', 'Étterem'],
                'location_types' => ['Tóparti'],
                'featured' => true
            ],
            [
                'name' => 'Mátra Vista Lakókocsi Park',
                'location' => 'Mátra, Gyöngyös',
                'rating' => 4.9,
                'reviews' => 89,
                'price' => 18500,
                'image' => 'https://picsum.photos/600/400?mountain',
                'tags' => ['WiFi', 'Parkoló', 'Étterem', 'Lakókocsi csatlakozó'],
                'location_types' => ['Hegyi'],
                'featured' => true
            ],
            [
                'name' => 'Őrségi Erdei Kemping',
                'location' => 'Őrség, Szalafő',
                'rating' => 4.7,
                'reviews' => 156,
                'price' => 8500,
                'image' => 'https://picsum.photos/600/400?forest',
                'tags' => ['Parkoló', 'Sátorhely', 'Túraútvonalak'],
                'location_types' => ['Erdei'],
                'featured' => true
            ],
            [
                'name' => 'Budapesti Városi Kemping',
                'location' => 'Budapest, Zugló',
                'rating' => 4.5,
                'reviews' => 98,
                'price' => 15000,
                'image' => 'https://picsum.photos/600/400?city',
                'tags' => ['WiFi', 'Parkoló'],
                'location_types' => [],
                'featured' => false
            ],
            [
                'name' => 'Adriai Tengerparti Kemping',
                'location' => 'Horvátország, Split',
                'rating' => 4.9,
                'reviews' => 201,
                'price' => 22000,
                'image' => 'https://picsum.photos/600/400?beach',
                'tags' => ['WiFi', 'Étterem', 'Parkoló'],
                'location_types' => ['Tengerparti'],
                'featured' => true
            ],
            [
                'name' => 'Tisza-tó Öko Kemping',
                'location' => 'Tisza-tó, Abádszalók',
                'rating' => 4.6,
                'reviews' => 87,
                'price' => 9500,
                'image' => 'https://picsum.photos/600/400?lake',
                'tags' => ['Sátorhely', 'Túraútvonalak', 'Parkoló'],
                'location_types' => ['Tóparti'],
                'featured' => false
            ],
            [
                'name' => 'Hortobágyi Puszta Kemping',
                'location' => 'Hortobágy, Debrecen',
                'rating' => 4.3,
                'reviews' => 54,
                'price' => 7000,
                'image' => 'https://picsum.photos/600/400?prairie',
                'tags' => ['Parkoló', 'Túraútvonalak'],
                'location_types' => ['Sivatagi'],
                'featured' => false
            ],
            [
                'name' => 'Velencei-tó Családi Kemping',
                'location' => 'Velencei-tó, Agárd',
                'rating' => 4.7,
                'reviews' => 142,
                'price' => 11000,
                'image' => 'https://picsum.photos/600/400?family',
                'tags' => ['WiFi', 'Sátorhely', 'Étterem', 'Parkoló'],
                'location_types' => ['Tóparti'],
                'featured' => true
            ],
            [
                'name' => 'Bükki Hegyi Túra Kemping',
                'location' => 'Bükk, Lillafüred',
                'rating' => 4.8,
                'reviews' => 103,
                'price' => 13500,
                'image' => 'https://picsum.photos/600/400?hiking',
                'tags' => ['Túraútvonalak', 'Parkoló', 'Sátorhely'],
                'location_types' => ['Hegyi'],
                'featured' => true
            ],
            [
                'name' => 'Zempléni Erdei Menedék',
                'location' => 'Zemplén, Tokaj',
                'rating' => 4.4,
                'reviews' => 67,
                'price' => 8000,
                'image' => 'https://picsum.photos/600/400?refuge',
                'tags' => ['Sátorhely', 'Parkoló'],
                'location_types' => ['Erdei'],
                'featured' => false
            ],
            [
                'name' => 'Bakony Panoráma Lakókocsi Park',
                'location' => 'Bakony, Veszprém',
                'rating' => 4.6,
                'reviews' => 78,
                'price' => 16000,
                'image' => 'https://picsum.photos/600/400?caravan',
                'tags' => ['WiFi', 'Lakókocsi csatlakozó', 'Parkoló', 'Étterem'],
                'location_types' => ['Hegyi'],
                'featured' => false
            ],
            [
                'name' => 'Dunakanyar Romantic Kemping',
                'location' => 'Dunakanyar, Visegrád',
                'rating' => 4.9,
                'reviews' => 189,
                'price' => 19500,
                'image' => 'https://picsum.photos/600/400?romantic',
                'tags' => ['WiFi', 'Étterem', 'Parkoló', 'Sátorhely'],
                'location_types' => ['Tóparti'],
                'featured' => true
            ],
            [
                'name' => 'Mecseki Natura Kemping',
                'location' => 'Mecsek, Pécs',
                'rating' => 4.2,
                'reviews' => 45,
                'price' => 7500,
                'image' => 'https://picsum.photos/600/400?nature',
                'tags' => ['Túraútvonalak', 'Parkoló'],
                'location_types' => ['Hegyi'],
                'featured' => false
            ],
            [
                'name' => 'Somogyóvári Csendes Kemping',
                'location' => 'Somogy, Balatonboglár',
                'rating' => 3.9,
                'reviews' => 32,
                'price' => 6500,
                'image' => 'https://picsum.photos/600/400?quiet',
                'tags' => ['Parkoló'],
                'location_types' => ['Tóparti'],
                'featured' => false
            ],
            [
                'name' => 'Mediterrán Tengerparti Luxus',
                'location' => 'Görögország, Kréta',
                'rating' => 4.95,
                'reviews' => 312,
                'price' => 28000,
                'image' => 'https://picsum.photos/600/400?luxury',
                'tags' => ['WiFi', 'Étterem', 'Parkoló', 'Lakókocsi csatlakozó'],
                'location_types' => ['Tengerparti'],
                'featured' => true
            ],
        ];

        foreach ($campsites as $campsite) {
            Campsite::create($campsite);
        }
    }
}
