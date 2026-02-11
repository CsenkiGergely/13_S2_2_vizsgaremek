<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Location;
use App\Models\Camping;
use App\Models\CampingTag;
use App\Models\CampingPhoto;
use App\Models\CampingSpot;
use App\Models\Comment;

class CampingDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Létrehozunk egy teszt felhasználót a kempingekhez
        $user = User::firstOrCreate(
            ['email' => 'kemping@teszt.hu'],
            [
                'name' => 'Kemping Tulajdonos',
                'password' => bcrypt('password123'),
            ]
        );

        // Létrehozunk egy másik felhasználót a kommentekhez
        $reviewer = User::firstOrCreate(
            ['email' => 'turista@teszt.hu'],
            [
                'name' => 'Turista Péter',
                'password' => bcrypt('password123'),
            ]
        );

        $campingsData = [
            [
                'camping_name' => 'Balatoni Napfény Kemping',
                'city' => 'Siófok',
                'zip_code' => '8600',
                'street_address' => 'Strand utca 25',
                'description' => 'Csodálatos balatoni kemping közvetlen vízparti hellyel. Tökéletes családoknak és természetszeretőknek.',
                'tags' => ['WiFi', 'Parkoló', 'Strand', 'Játszótér'],
                'spots' => [
                    ['name' => 'A1', 'type' => 'Sátorhelyek', 'capacity' => 4, 'price' => 8500],
                    ['name' => 'A2', 'type' => 'Lakókocsi', 'capacity' => 6, 'price' => 12000],
                    ['name' => 'A3', 'type' => 'Bungaló', 'capacity' => 4, 'price' => 18000],
                ],
                'photos' => [
                    'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=800',
                    'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?w=800',
                ],
                'comments' => [
                    ['rating' => 5, 'comment' => 'Fantasztikus hely, minden tökéletes volt!'],
                    ['rating' => 4, 'comment' => 'Szép hely, de kicsit zajos volt.'],
                ]
            ],
            [
                'camping_name' => 'Erdőszéli Pihenő',
                'city' => 'Kékestető',
                'zip_code' => '3221',
                'street_address' => 'Hegyi út 12',
                'description' => 'Nyugodt, erdei környezetben található kemping túrázók számára.',
                'tags' => ['WiFi', 'Parkoló', 'Túra útvonalak', 'BBQ'],
                'spots' => [
                    ['name' => 'B1', 'type' => 'Sátorhelyek', 'capacity' => 3, 'price' => 6500],
                    ['name' => 'B2', 'type' => 'Faház', 'capacity' => 5, 'price' => 15000],
                ],
                'photos' => [
                    'https://images.unsplash.com/photo-1487730116645-74489c95b41b?w=800',
                ],
                'comments' => [
                    ['rating' => 5, 'comment' => 'Csodálatos természet, nagyon nyugodt!'],
                ]
            ],
            [
                'camping_name' => 'Tiszaparti Relax',
                'city' => 'Tokaj',
                'zip_code' => '3910',
                'street_address' => 'Tisza part 8',
                'description' => 'Folyóparti kemping horgászoknak és vízi sportok kedvelőinek.',
                'tags' => ['WiFi', 'Horgászat', 'Kajak', 'Étterem'],
                'spots' => [
                    ['name' => 'C1', 'type' => 'Sátorhelyek', 'capacity' => 4, 'price' => 7000],
                    ['name' => 'C2', 'type' => 'Lakókocsi', 'capacity' => 4, 'price' => 10000],
                    ['name' => 'C3', 'type' => 'Sátorhelyek', 'capacity' => 2, 'price' => 5500],
                ],
                'photos' => [
                    'https://images.unsplash.com/photo-1510312305653-8ed496efae75?w=800',
                ],
                'comments' => [
                    ['rating' => 4, 'comment' => 'Jó hely horgászáshoz!'],
                    ['rating' => 4, 'comment' => 'Barátságos személyzet.'],
                ]
            ],
            [
                'camping_name' => 'Velencei-tavi Öböl',
                'city' => 'Velence',
                'zip_code' => '2481',
                'street_address' => 'Tó utca 34',
                'description' => 'Családbarát kemping a Velencei-tó partján.',
                'tags' => ['WiFi', 'Parkoló', 'Strand', 'Játszótér', 'Kerékpár'],
                'spots' => [
                    ['name' => 'D1', 'type' => 'Sátorhelyek', 'capacity' => 5, 'price' => 9000],
                    ['name' => 'D2', 'type' => 'Bungaló', 'capacity' => 6, 'price' => 20000],
                ],
                'photos' => [
                    'https://images.unsplash.com/photo-1476041800959-2f6bb412c8ce?w=800',
                ],
                'comments' => [
                    ['rating' => 5, 'comment' => 'Gyerekbarát, ajánlom mindenkinek!'],
                ]
            ],
            [
                'camping_name' => 'Mátra Csúcs Kemping',
                'city' => 'Mátraháza',
                'zip_code' => '3232',
                'street_address' => 'Panoráma út 5',
                'description' => 'Hegyi kemping lélegzetelállító kilátással.',
                'tags' => ['WiFi', 'Túra útvonalak', 'BBQ', 'Étterem'],
                'spots' => [
                    ['name' => 'E1', 'type' => 'Sátorhelyek', 'capacity' => 3, 'price' => 7500],
                    ['name' => 'E2', 'type' => 'Faház', 'capacity' => 4, 'price' => 16000],
                ],
                'photos' => [
                    'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?w=800',
                ],
                'comments' => [
                    ['rating' => 5, 'comment' => 'Gyönyörű kilátás!'],
                ]
            ],
        ];

        foreach ($campingsData as $data) {
            // Létrehozzuk a location-t
            $location = Location::create([
                'city' => $data['city'],
                'zip_code' => $data['zip_code'],
                'street_address' => $data['street_address'],
                'latitude' => rand(4600, 4800) / 100,
                'longitude' => rand(1600, 2200) / 100,
            ]);

            // Létrehozzuk a camping-et
            $camping = Camping::create([
                'user_id' => $user->id,
                'camping_name' => $data['camping_name'],
                'owner_first_name' => 'Teszt',
                'owner_last_name' => 'Tulajdonos',
                'location_id' => $location->id,
                'description' => $data['description'],
                'company_name' => $data['camping_name'] . ' Kft.',
                'tax_id' => '12345678-1-23',
                'billing_address' => $data['street_address'],
            ]);

            // Hozzáadjuk a tag-eket
            foreach ($data['tags'] as $tag) {
                CampingTag::create([
                    'camping_id' => $camping->id,
                    'tag' => $tag,
                ]);
            }

            // Hozzáadjuk a fotókat
            foreach ($data['photos'] as $photoUrl) {
                CampingPhoto::create([
                    'camping_id' => $camping->id,
                    'photo_url' => $photoUrl,
                    'caption' => $camping->camping_name . ' - fotó',
                    'uploaded_at' => now(),
                ]);
            }

            // Hozzáadjuk a spot-okat
            foreach ($data['spots'] as $spotData) {
                CampingSpot::create([
                    'camping_id' => $camping->id,
                    'name' => $spotData['name'],
                    'type' => $spotData['type'],
                    'capacity' => $spotData['capacity'],
                    'price_per_night' => $spotData['price'],
                    'is_available' => true,
                    'description' => $spotData['type'] . ' ' . $spotData['capacity'] . ' fő részére',
                ]);
            }

            // Hozzáadjuk a kommenteket
            foreach ($data['comments'] as $commentData) {
                Comment::create([
                    'camping_id' => $camping->id,
                    'user_id' => $reviewer->id,
                    'parent_id' => null,
                    'rating' => $commentData['rating'],
                    'comment' => $commentData['comment'],
                    'upload_date' => now()->subDays(rand(1, 30)),
                ]);
            }
        }

        $this->command->info('5 kemping létrehozva tesztadatokkal!');
    }
}
