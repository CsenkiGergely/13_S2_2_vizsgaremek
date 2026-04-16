<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Camping;
use App\Models\CampingPhoto;
use App\Models\CampingSpot;
use App\Models\CampingTag;
use App\Models\Comment;
use App\Models\EntranceGate;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $today = Carbon::today();

            $owner = User::firstOrCreate(
                ['email' => 'owner.demo@campsite.local'],
                [
                    'owner_first_name' => 'Andras',
                    'owner_last_name' => 'Kovacs',
                    'password' => Hash::make('password123'),
                    'phone_number' => '+36301112222',
                ]
            );
            $owner->role = true;
            $owner->email_verified_at = $owner->email_verified_at ?? $today->copy()->subMonths(8);
            $owner->save();

            $guestUsers = [
                [
                    'email' => 'anna.demo@campsite.local',
                    'owner_first_name' => 'Anna',
                    'owner_last_name' => 'Farkas',
                    'phone_number' => '+36301223344',
                    'guest_profile' => [
                        'first_name' => 'Anna',
                        'last_name' => 'Farkas',
                        'birth_date' => '1994-07-11',
                        'place_of_birth' => 'Budapest',
                        'gender' => 'female',
                        'citizenship' => 'magyar',
                        'mothers_birth_name' => 'Szabina Horvath',
                        'id_card_number' => 'AA123456',
                        'passport_number' => 'P1234567',
                        'visa' => null,
                        'resident_permit_number' => null,
                        'date_of_entry' => null,
                        'place_of_entry' => null,
                    ],
                ],
                [
                    'email' => 'mate.demo@campsite.local',
                    'owner_first_name' => 'Mate',
                    'owner_last_name' => 'Nagy',
                    'phone_number' => '+36305556666',
                    'guest_profile' => [
                        'first_name' => 'Mate',
                        'last_name' => 'Nagy',
                        'birth_date' => '1991-02-18',
                        'place_of_birth' => 'Gyor',
                        'gender' => 'male',
                        'citizenship' => 'magyar',
                        'mothers_birth_name' => 'Judit Toth',
                        'id_card_number' => 'BB654321',
                        'passport_number' => 'P7654321',
                        'visa' => null,
                        'resident_permit_number' => null,
                        'date_of_entry' => null,
                        'place_of_entry' => null,
                    ],
                ],
                [
                    'email' => 'luca.demo@campsite.local',
                    'owner_first_name' => 'Luca',
                    'owner_last_name' => 'Varga',
                    'phone_number' => '+36307778899',
                    'guest_profile' => [
                        'first_name' => 'Luca',
                        'last_name' => 'Varga',
                        'birth_date' => '1998-10-03',
                        'place_of_birth' => 'Pecs',
                        'gender' => 'female',
                        'citizenship' => 'magyar',
                        'mothers_birth_name' => 'Eva Kiss',
                        'id_card_number' => 'CC998877',
                        'passport_number' => 'P3344556',
                        'visa' => null,
                        'resident_permit_number' => null,
                        'date_of_entry' => null,
                        'place_of_entry' => null,
                    ],
                ],
            ];

            $usersByEmail = [];
            foreach ($guestUsers as $data) {
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'owner_first_name' => $data['owner_first_name'],
                        'owner_last_name' => $data['owner_last_name'],
                        'password' => Hash::make('password123'),
                        'phone_number' => $data['phone_number'],
                    ]
                );

                $user->email_verified_at = $user->email_verified_at ?? $today->copy()->subMonths(3);
                $user->save();
                $usersByEmail[$data['email']] = $user;

                DB::table('user_guests')->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'id_card_number' => $data['guest_profile']['id_card_number'],
                    ],
                    array_merge(
                        $data['guest_profile'],
                        [
                            'user_id' => $user->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    )
                );
            }

            $campingBlueprints = [
                [
                    'camping_name' => 'Balatoni Csendes Part',
                    'description' => 'Vizparti kemping csaladoknak, sajat strandresz es nagy zoldterulet.',
                    'location' => [
                        'city' => 'Siofok',
                        'zip_code' => '8600',
                        'street_address' => 'Parti setany 12',
                        'latitude' => 46.90895,
                        'longitude' => 18.05500,
                    ],
                    'tags' => ['WiFi', 'Strand', 'Parkolo', 'Jatszoter'],
                    'photos' => [
                        'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=1200',
                        'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?w=1200',
                    ],
                    'spots' => [
                        ['name' => 'A1', 'type' => 'Satorhely', 'capacity' => 2, 'price_per_night' => 7800, 'row' => 1, 'column' => 1],
                        ['name' => 'A2', 'type' => 'Lakovocsi', 'capacity' => 4, 'price_per_night' => 11900, 'row' => 1, 'column' => 2],
                        ['name' => 'A3', 'type' => 'Bungalo', 'capacity' => 4, 'price_per_night' => 18200, 'row' => 1, 'column' => 3],
                    ],
                ],
                [
                    'camping_name' => 'Matra Panorama Kemping',
                    'description' => 'Hegyi levego, turautvonalak, panoramas helyek es esti tuzrakas.',
                    'location' => [
                        'city' => 'Matrahaza',
                        'zip_code' => '3232',
                        'street_address' => 'Panorama ut 7',
                        'latitude' => 47.87372,
                        'longitude' => 20.00922,
                    ],
                    'tags' => ['Turazas', 'BBQ', 'WiFi', 'Etterem'],
                    'photos' => [
                        'https://images.unsplash.com/photo-1487730116645-74489c95b41b?w=1200',
                        'https://images.unsplash.com/photo-1476041800959-2f6bb412c8ce?w=1200',
                    ],
                    'spots' => [
                        ['name' => 'M1', 'type' => 'Satorhely', 'capacity' => 3, 'price_per_night' => 7100, 'row' => 2, 'column' => 1],
                        ['name' => 'M2', 'type' => 'Fahaz', 'capacity' => 5, 'price_per_night' => 16900, 'row' => 2, 'column' => 2],
                    ],
                ],
                [
                    'camping_name' => 'Tisza Oxbow Camp',
                    'description' => 'Vizisport, horgaszat, csendes kornyezet a folyo mellett.',
                    'location' => [
                        'city' => 'Tokaj',
                        'zip_code' => '3910',
                        'street_address' => 'Tisza part 21',
                        'latitude' => 48.12010,
                        'longitude' => 21.40868,
                    ],
                    'tags' => ['Horgaszat', 'Kajak', 'WiFi', 'Parkolo'],
                    'photos' => [
                        'https://images.unsplash.com/photo-1510312305653-8ed496efae75?w=1200',
                    ],
                    'spots' => [
                        ['name' => 'T1', 'type' => 'Satorhely', 'capacity' => 2, 'price_per_night' => 6200, 'row' => 3, 'column' => 1],
                        ['name' => 'T2', 'type' => 'Lakovocsi', 'capacity' => 4, 'price_per_night' => 9700, 'row' => 3, 'column' => 2],
                    ],
                ],
            ];

            $campingsByName = [];
            $spotsByCampingAndName = [];

            foreach ($campingBlueprints as $blueprint) {
                $loc = $blueprint['location'];

                $location = Location::updateOrCreate(
                    [
                        'city' => $loc['city'],
                        'zip_code' => $loc['zip_code'],
                        'street_address' => $loc['street_address'],
                    ],
                    [
                        'latitude' => $loc['latitude'],
                        'longitude' => $loc['longitude'],
                    ]
                );

                $camping = Camping::updateOrCreate(
                    [
                        'camping_name' => $blueprint['camping_name'],
                        'user_id' => $owner->id,
                    ],
                    [
                        'location_id' => $location->id,
                        'description' => $blueprint['description'],
                        'company_name' => $blueprint['camping_name'] . ' Kft.',
                        'tax_id' => '12345678-2-41',
                        'billing_address' => $loc['zip_code'] . ' ' . $loc['city'] . ', ' . $loc['street_address'],
                        'required_guest_fields' => ['first_name', 'last_name', 'birth_date', 'id_card_number'],
                    ]
                );

                $campingsByName[$camping->camping_name] = $camping;
                $existingTags = collect($blueprint['tags']);

                CampingTag::where('camping_id', $camping->id)
                    ->whereNotIn('tag', $existingTags->all())
                    ->delete();

                foreach ($blueprint['tags'] as $tag) {
                    CampingTag::updateOrCreate(
                        ['camping_id' => $camping->id, 'tag' => $tag],
                        []
                    );
                }

                foreach ($blueprint['photos'] as $photoUrl) {
                    CampingPhoto::updateOrCreate(
                        ['camping_id' => $camping->id, 'photo_url' => $photoUrl],
                        ['caption' => $camping->camping_name . ' foto']
                    );
                }

                foreach ($blueprint['spots'] as $spotData) {
                    $spot = CampingSpot::updateOrCreate(
                        [
                            'camping_id' => $camping->id,
                            'name' => $spotData['name'],
                        ],
                        [
                            'type' => $spotData['type'],
                            'capacity' => $spotData['capacity'],
                            'price_per_night' => $spotData['price_per_night'],
                            'is_available' => true,
                            'description' => $spotData['type'] . ' hely ' . $spotData['capacity'] . ' fo reszere',
                            'row' => $spotData['row'],
                            'column' => $spotData['column'],
                            'rating' => null,
                        ]
                    );

                    $spotsByCampingAndName[$camping->camping_name][$spotData['name']] = $spot;
                }

                $gate = EntranceGate::firstOrNew([
                    'camping_id' => $camping->id,
                    'name' => 'Fokapu',
                ]);

                if (!$gate->exists) {
                    $gate->auth_token = strtoupper(Str::random(16));
                }

                $gate->timestamp = now();
                $gate->gate_id = 1;
                $gate->opening_time = '06:00:00';
                $gate->closing_time = '23:00:00';
                $gate->save();
            }

            $bookingsBlueprint = [
                [
                    'email' => 'anna.demo@campsite.local',
                    'camping' => 'Balatoni Csendes Part',
                    'spot' => 'A3',
                    'arrival' => $today->copy()->subDays(40),
                    'departure' => $today->copy()->subDays(36),
                    'status' => 'completed',
                ],
                [
                    'email' => 'mate.demo@campsite.local',
                    'camping' => 'Balatoni Csendes Part',
                    'spot' => 'A2',
                    'arrival' => $today->copy()->addDays(8),
                    'departure' => $today->copy()->addDays(11),
                    'status' => 'confirmed',
                ],
                [
                    'email' => 'luca.demo@campsite.local',
                    'camping' => 'Matra Panorama Kemping',
                    'spot' => 'M2',
                    'arrival' => $today->copy()->subDay(),
                    'departure' => $today->copy()->addDays(2),
                    'status' => 'checked_in',
                ],
                [
                    'email' => 'anna.demo@campsite.local',
                    'camping' => 'Tisza Oxbow Camp',
                    'spot' => 'T2',
                    'arrival' => $today->copy()->addDays(25),
                    'departure' => $today->copy()->addDays(28),
                    'status' => 'pending',
                ],
                [
                    'email' => 'mate.demo@campsite.local',
                    'camping' => 'Matra Panorama Kemping',
                    'spot' => 'M1',
                    'arrival' => $today->copy()->subDays(15),
                    'departure' => $today->copy()->subDays(13),
                    'status' => 'cancelled',
                ],
                [
                    'email' => 'luca.demo@campsite.local',
                    'camping' => 'Tisza Oxbow Camp',
                    'spot' => 'T1',
                    'arrival' => $today->copy()->subDays(28),
                    'departure' => $today->copy()->subDays(24),
                    'status' => 'completed',
                ],
            ];

            $bookingsByKey = [];

            foreach ($bookingsBlueprint as $entry) {
                $user = $usersByEmail[$entry['email']];
                $camping = $campingsByName[$entry['camping']];
                $spot = $spotsByCampingAndName[$entry['camping']][$entry['spot']];

                $booking = Booking::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'camping_id' => $camping->id,
                        'camping_spot_id' => $spot->spot_id,
                        'arrival_date' => $entry['arrival']->toDateString(),
                        'departure_date' => $entry['departure']->toDateString(),
                    ],
                    [
                        'status' => $entry['status'],
                        'guests' => min($spot->capacity, max(1, $spot->capacity - 1)),
                    ]
                );

                if (empty($booking->qr_code)) {
                    $booking->qr_code = 'USR' . $booking->user_id . '-BKG' . $booking->id . '-' . strtoupper(Str::random(8));
                }

                $booking->created_at = $entry['arrival']->copy()->subDays(18);
                $booking->updated_at = $entry['arrival']->copy()->subDays(5);
                $booking->save();

                $bookingsByKey[$entry['camping'] . '|' . $entry['spot']] = $booking;
            }

            $commentsBlueprint = [
                [
                    'camping' => 'Balatoni Csendes Part',
                    'email' => 'anna.demo@campsite.local',
                    'rating' => 5,
                    'comment' => 'Tiszta, rendezett es csaladbarat hely. Biztosan jovunk meg.',
                    'reply' => 'Koszonjuk a visszajelzest, varunk vissza benneteket!',
                    'reply_days_after' => 2,
                    'booking_key' => 'Balatoni Csendes Part|A3',
                ],
                [
                    'camping' => 'Tisza Oxbow Camp',
                    'email' => 'luca.demo@campsite.local',
                    'rating' => 4,
                    'comment' => 'Nagyon jo hangulat, a vizpart kulon plusz pont.',
                    'reply' => 'Orulunk, hogy jol erezted magad nalunk!',
                    'reply_days_after' => 1,
                    'booking_key' => 'Tisza Oxbow Camp|T1',
                ],
                [
                    'camping' => 'Matra Panorama Kemping',
                    'email' => 'mate.demo@campsite.local',
                    'rating' => 5,
                    'comment' => 'Gyonyoru panorama, remek felszereltseg es nyugodt ejszaka.',
                    'reply' => null,
                    'reply_days_after' => null,
                    'booking_key' => null,
                ],
            ];

            foreach ($commentsBlueprint as $item) {
                $camping = $campingsByName[$item['camping']];
                $author = $usersByEmail[$item['email']];
                $reviewTime = now()->subDays(14);

                if (!empty($item['booking_key']) && isset($bookingsByKey[$item['booking_key']])) {
                    $reviewTime = Carbon::parse($bookingsByKey[$item['booking_key']]->departure_date)->addDays(1);
                }

                $comment = Comment::updateOrCreate(
                    [
                        'camping_id' => $camping->id,
                        'user_id' => $author->id,
                        'parent_id' => null,
                        'comment' => $item['comment'],
                    ],
                    [
                        'rating' => $item['rating'],
                        'created_at' => $reviewTime,
                        'updated_at' => $reviewTime,
                    ]
                );

                if (!empty($item['reply'])) {
                    $replyTime = $reviewTime->copy()->addDays((int) $item['reply_days_after']);

                    Comment::updateOrCreate(
                        [
                            'camping_id' => $camping->id,
                            'user_id' => $owner->id,
                            'parent_id' => $comment->id,
                            'comment' => $item['reply'],
                        ],
                        [
                            'rating' => null,
                            'created_at' => $replyTime,
                            'updated_at' => $replyTime,
                        ]
                    );
                }
            }
        });

        $this->command->info('PostSeeder: osszefuggo demo adatok letrehozva (kemping, spot, booking, komment, ertekeles, vendeg).');
    }
}
