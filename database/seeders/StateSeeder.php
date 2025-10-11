<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    /**
     * Seed the US states with timezone and coordinates.
     */
    public function run(): void
    {
        $states = [
            ['code' => 'AL', 'name' => 'Alabama', 'timezone' => 'America/Chicago', 'latitude' => 32.806671, 'longitude' => -86.791130],
            ['code' => 'AK', 'name' => 'Alaska', 'timezone' => 'America/Anchorage', 'latitude' => 61.370716, 'longitude' => -152.404419],
            ['code' => 'AZ', 'name' => 'Arizona', 'timezone' => 'America/Phoenix', 'latitude' => 33.729759, 'longitude' => -111.431221],
            ['code' => 'AR', 'name' => 'Arkansas', 'timezone' => 'America/Chicago', 'latitude' => 34.969704, 'longitude' => -92.373123],
            ['code' => 'CA', 'name' => 'California', 'timezone' => 'America/Los_Angeles', 'latitude' => 36.116203, 'longitude' => -119.681564],
            ['code' => 'CO', 'name' => 'Colorado', 'timezone' => 'America/Denver', 'latitude' => 39.059811, 'longitude' => -105.311104],
            ['code' => 'CT', 'name' => 'Connecticut', 'timezone' => 'America/New_York', 'latitude' => 41.597782, 'longitude' => -72.755371],
            ['code' => 'DE', 'name' => 'Delaware', 'timezone' => 'America/New_York', 'latitude' => 39.318523, 'longitude' => -75.507141],
            ['code' => 'FL', 'name' => 'Florida', 'timezone' => 'America/New_York', 'latitude' => 27.766279, 'longitude' => -81.686783],
            ['code' => 'GA', 'name' => 'Georgia', 'timezone' => 'America/New_York', 'latitude' => 33.040619, 'longitude' => -83.643074],
            ['code' => 'HI', 'name' => 'Hawaii', 'timezone' => 'Pacific/Honolulu', 'latitude' => 21.094318, 'longitude' => -157.498337],
            ['code' => 'ID', 'name' => 'Idaho', 'timezone' => 'America/Boise', 'latitude' => 44.240459, 'longitude' => -114.478828],
            ['code' => 'IL', 'name' => 'Illinois', 'timezone' => 'America/Chicago', 'latitude' => 40.349457, 'longitude' => -88.986137],
            ['code' => 'IN', 'name' => 'Indiana', 'timezone' => 'America/Indiana/Indianapolis', 'latitude' => 39.849426, 'longitude' => -86.258278],
            ['code' => 'IA', 'name' => 'Iowa', 'timezone' => 'America/Chicago', 'latitude' => 42.011539, 'longitude' => -93.210526],
            ['code' => 'KS', 'name' => 'Kansas', 'timezone' => 'America/Chicago', 'latitude' => 38.526600, 'longitude' => -96.726486],
            ['code' => 'KY', 'name' => 'Kentucky', 'timezone' => 'America/New_York', 'latitude' => 37.668140, 'longitude' => -84.670067],
            ['code' => 'LA', 'name' => 'Louisiana', 'timezone' => 'America/Chicago', 'latitude' => 31.169546, 'longitude' => -91.867805],
            ['code' => 'ME', 'name' => 'Maine', 'timezone' => 'America/New_York', 'latitude' => 44.693947, 'longitude' => -69.381927],
            ['code' => 'MD', 'name' => 'Maryland', 'timezone' => 'America/New_York', 'latitude' => 39.063946, 'longitude' => -76.802101],
            ['code' => 'MA', 'name' => 'Massachusetts', 'timezone' => 'America/New_York', 'latitude' => 42.230171, 'longitude' => -71.530106],
            ['code' => 'MI', 'name' => 'Michigan', 'timezone' => 'America/Detroit', 'latitude' => 43.326618, 'longitude' => -84.536095],
            ['code' => 'MN', 'name' => 'Minnesota', 'timezone' => 'America/Chicago', 'latitude' => 45.694454, 'longitude' => -93.900192],
            ['code' => 'MS', 'name' => 'Mississippi', 'timezone' => 'America/Chicago', 'latitude' => 32.741646, 'longitude' => -89.678696],
            ['code' => 'MO', 'name' => 'Missouri', 'timezone' => 'America/Chicago', 'latitude' => 38.456085, 'longitude' => -92.288368],
            ['code' => 'MT', 'name' => 'Montana', 'timezone' => 'America/Denver', 'latitude' => 46.921925, 'longitude' => -110.454353],
            ['code' => 'NE', 'name' => 'Nebraska', 'timezone' => 'America/Chicago', 'latitude' => 41.125370, 'longitude' => -98.268082],
            ['code' => 'NV', 'name' => 'Nevada', 'timezone' => 'America/Los_Angeles', 'latitude' => 38.313515, 'longitude' => -117.055374],
            ['code' => 'NH', 'name' => 'New Hampshire', 'timezone' => 'America/New_York', 'latitude' => 43.452492, 'longitude' => -71.563896],
            ['code' => 'NJ', 'name' => 'New Jersey', 'timezone' => 'America/New_York', 'latitude' => 40.298904, 'longitude' => -74.521011],
            ['code' => 'NM', 'name' => 'New Mexico', 'timezone' => 'America/Denver', 'latitude' => 34.840515, 'longitude' => -106.248482],
            ['code' => 'NY', 'name' => 'New York', 'timezone' => 'America/New_York', 'latitude' => 42.165726, 'longitude' => -74.948051],
            ['code' => 'NC', 'name' => 'North Carolina', 'timezone' => 'America/New_York', 'latitude' => 35.630066, 'longitude' => -79.806419],
            ['code' => 'ND', 'name' => 'North Dakota', 'timezone' => 'America/Chicago', 'latitude' => 47.528912, 'longitude' => -99.784012],
            ['code' => 'OH', 'name' => 'Ohio', 'timezone' => 'America/New_York', 'latitude' => 40.388783, 'longitude' => -82.764915],
            ['code' => 'OK', 'name' => 'Oklahoma', 'timezone' => 'America/Chicago', 'latitude' => 35.565342, 'longitude' => -96.928917],
            ['code' => 'OR', 'name' => 'Oregon', 'timezone' => 'America/Los_Angeles', 'latitude' => 44.572021, 'longitude' => -122.070938],
            ['code' => 'PA', 'name' => 'Pennsylvania', 'timezone' => 'America/New_York', 'latitude' => 40.590752, 'longitude' => -77.209755],
            ['code' => 'RI', 'name' => 'Rhode Island', 'timezone' => 'America/New_York', 'latitude' => 41.680893, 'longitude' => -71.511780],
            ['code' => 'SC', 'name' => 'South Carolina', 'timezone' => 'America/New_York', 'latitude' => 33.856892, 'longitude' => -80.945007],
            ['code' => 'SD', 'name' => 'South Dakota', 'timezone' => 'America/Chicago', 'latitude' => 44.299782, 'longitude' => -99.438828],
            ['code' => 'TN', 'name' => 'Tennessee', 'timezone' => 'America/Chicago', 'latitude' => 35.747845, 'longitude' => -86.692345],
            ['code' => 'TX', 'name' => 'Texas', 'timezone' => 'America/Chicago', 'latitude' => 31.054487, 'longitude' => -97.563461],
            ['code' => 'UT', 'name' => 'Utah', 'timezone' => 'America/Denver', 'latitude' => 40.150032, 'longitude' => -111.862434],
            ['code' => 'VT', 'name' => 'Vermont', 'timezone' => 'America/New_York', 'latitude' => 44.045876, 'longitude' => -72.710686],
            ['code' => 'VA', 'name' => 'Virginia', 'timezone' => 'America/New_York', 'latitude' => 37.769337, 'longitude' => -78.169968],
            ['code' => 'WA', 'name' => 'Washington', 'timezone' => 'America/Los_Angeles', 'latitude' => 47.400902, 'longitude' => -121.490494],
            ['code' => 'WV', 'name' => 'West Virginia', 'timezone' => 'America/New_York', 'latitude' => 38.491226, 'longitude' => -80.954456],
            ['code' => 'WI', 'name' => 'Wisconsin', 'timezone' => 'America/Chicago', 'latitude' => 44.268543, 'longitude' => -89.616508],
            ['code' => 'WY', 'name' => 'Wyoming', 'timezone' => 'America/Denver', 'latitude' => 42.755966, 'longitude' => -107.302490],
            ['code' => 'DC', 'name' => 'District of Columbia', 'timezone' => 'America/New_York', 'latitude' => 38.897438, 'longitude' => -77.026817],
        ];

        foreach ($states as $stateData) {
            State::create($stateData);
        }

        $this->command->info('✅ Created 51 US states successfully!');
    }
}

