<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Activity\Activity;

class ActivitySeeder extends Seeder
{
    /**
     * Seed activities with realistic French pool activities
     */
    public function run(): void
    {
        $activities = [
            [
                'name' => 'Natation Libre',
                'description' => 'Accès libre aux bassins pour nage personnelle',
                'access_type' => 'member',
                'color_code' => '#3B82F6', // Blue
                'is_active' => true,
            ],
            [
                'name' => 'Cours de Natation Débutant',
                'description' => 'Cours collectifs pour apprendre à nager, niveau débutant',
                'access_type' => 'lesson',
                'color_code' => '#10B981', // Green
                'is_active' => true,
            ],
            [
                'name' => 'Cours de Natation Intermédiaire',
                'description' => 'Cours collectifs de perfectionnement, niveau intermédiaire',
                'access_type' => 'lesson',
                'color_code' => '#059669', // Dark Green
                'is_active' => true,
            ],
            [
                'name' => 'Cours de Natation Avancé',
                'description' => 'Cours collectifs de perfectionnement, niveau avancé',
                'access_type' => 'lesson',
                'color_code' => '#047857', // Darker Green
                'is_active' => true,
            ],
            [
                'name' => 'Aquagym',
                'description' => 'Gymnastique aquatique en musique pour tonifier le corps',
                'access_type' => 'class',
                'color_code' => '#F59E0B', // Amber
                'is_active' => true,
            ],
            [
                'name' => 'Aquabike',
                'description' => 'Vélo aquatique pour renforcement musculaire et cardio',
                'access_type' => 'class',
                'color_code' => '#EF4444', // Red
                'is_active' => true,
            ],
            [
                'name' => 'Bébés Nageurs',
                'description' => 'Éveil aquatique pour bébés de 6 mois à 3 ans',
                'access_type' => 'lesson',
                'color_code' => '#EC4899', // Pink
                'is_active' => true,
            ],
            [
                'name' => 'Natation Enfants (4-6 ans)',
                'description' => 'Cours d\'apprentissage pour enfants de 4 à 6 ans',
                'access_type' => 'lesson',
                'color_code' => '#8B5CF6', // Purple
                'is_active' => true,
            ],
            [
                'name' => 'Natation Enfants (7-12 ans)',
                'description' => 'Cours de natation pour enfants de 7 à 12 ans',
                'access_type' => 'lesson',
                'color_code' => '#7C3AED', // Violet
                'is_active' => true,
            ],
            [
                'name' => 'Aqua Fitness',
                'description' => 'Fitness aquatique haute intensité',
                'access_type' => 'class',
                'color_code' => '#DC2626', // Red-600
                'is_active' => true,
            ],
            [
                'name' => 'Natation Thérapeutique',
                'description' => 'Séances de rééducation et natation thérapeutique',
                'access_type' => 'therapy',
                'color_code' => '#0891B2', // Cyan
                'is_active' => true,
            ],
            [
                'name' => 'Club Compétition',
                'description' => 'Entraînement pour les nageurs du club de compétition',
                'access_type' => 'club',
                'color_code' => '#1D4ED8', // Blue-700
                'is_active' => true,
            ],
            [
                'name' => 'Réservation Groupe/École',
                'description' => 'Créneaux réservés pour groupes scolaires ou partenaires',
                'access_type' => 'group',
                'color_code' => '#6366F1', // Indigo
                'is_active' => true,
            ],
            [
                'name' => 'Séance Privée',
                'description' => 'Cours particulier de natation',
                'access_type' => 'private',
                'color_code' => '#F97316', // Orange
                'is_active' => true,
            ],
            [
                'name' => 'Aqua Zen',
                'description' => 'Relaxation et stretching aquatique',
                'access_type' => 'class',
                'color_code' => '#14B8A6', // Teal
                'is_active' => true,
            ],
        ];

        foreach ($activities as $activity) {
            Activity::updateOrCreate(
                ['name' => $activity['name']],
                $activity
            );
        }

        $this->command->info('Created ' . count($activities) . ' activities.');
    }
}
