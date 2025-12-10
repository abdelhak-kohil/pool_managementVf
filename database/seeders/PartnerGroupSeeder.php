<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member\PartnerGroup;
use Faker\Factory as Faker;

class PartnerGroupSeeder extends Seeder
{
    /**
     * Seed partner groups (Schools, Clubs, Associations)
     */
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        $groups = [
            ['name' => 'Club de Natation Les Dauphins', 'type' => 'Club Sportif'],
            ['name' => 'École Primaire Victor Hugo', 'type' => 'Scolaire'],
            ['name' => 'Collège Jean Moulin', 'type' => 'Scolaire'],
            ['name' => 'Lycée Pasteur', 'type' => 'Scolaire'],
            ['name' => 'Association Seniors Actifs', 'type' => 'Association'],
            ['name' => 'Comité d\'Entreprise Sonatrach', 'type' => 'Entreprise'],
            ['name' => 'Protection Civile', 'type' => 'Institution'],
            ['name' => 'Club de Plongée Sous-marine', 'type' => 'Club Sportif'],
            ['name' => 'Association Handisport', 'type' => 'Association'],
            ['name' => 'Université d\'Alger', 'type' => 'Universitaire'],
        ];

        foreach ($groups as $group) {
            PartnerGroup::create([
                'name' => $group['name'],
                'contact_name' => $faker->name,
                'contact_phone' => $faker->phoneNumber,
                'email' => $faker->companyEmail,
                'notes' => 'Partenaire type: ' . $group['type'],
                'is_active' => true,
            ]);
        }

        $this->command->info('Created ' . count($groups) . ' partner groups.');
    }
}
