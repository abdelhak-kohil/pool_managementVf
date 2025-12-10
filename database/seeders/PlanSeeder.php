<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Finance\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Seed subscription plans with realistic French pool offering
     */
    public function run(): void
    {
        $plans = [
            // Monthly plans
            [
                'plan_name' => 'Abonnement Mensuel - 1 séance/semaine',
                'description' => 'Accès à la piscine une fois par semaine pendant un mois',
                'plan_type' => 'monthly',
                'visits_per_week' => 1,
                'duration_months' => 1,
                'is_active' => true,
            ],
            [
                'plan_name' => 'Abonnement Mensuel - 2 séances/semaine',
                'description' => 'Accès à la piscine deux fois par semaine pendant un mois',
                'plan_type' => 'monthly',
                'visits_per_week' => 2,
                'duration_months' => 1,
                'is_active' => true,
            ],
            [
                'plan_name' => 'Abonnement Mensuel - 3 séances/semaine',
                'description' => 'Accès à la piscine trois fois par semaine pendant un mois',
                'plan_type' => 'monthly',
                'visits_per_week' => 3,
                'duration_months' => 1,
                'is_active' => true,
            ],
            [
                'plan_name' => 'Abonnement Mensuel Illimité',
                'description' => 'Accès illimité à la piscine pendant un mois',
                'plan_type' => 'monthly',
                'visits_per_week' => 7,
                'duration_months' => 1,
                'is_active' => true,
            ],
            // Quarterly plans
            [
                'plan_name' => 'Abonnement Trimestriel - 2 séances/semaine',
                'description' => 'Accès à la piscine deux fois par semaine pendant trois mois',
                'plan_type' => 'quarterly',
                'visits_per_week' => 2,
                'duration_months' => 3,
                'is_active' => true,
            ],
            [
                'plan_name' => 'Abonnement Trimestriel - 3 séances/semaine',
                'description' => 'Accès à la piscine trois fois par semaine pendant trois mois',
                'plan_type' => 'quarterly',
                'visits_per_week' => 3,
                'duration_months' => 3,
                'is_active' => true,
            ],
            [
                'plan_name' => 'Abonnement Trimestriel Illimité',
                'description' => 'Accès illimité à la piscine pendant trois mois',
                'plan_type' => 'quarterly',
                'visits_per_week' => 7,
                'duration_months' => 3,
                'is_active' => true,
            ],
            // Annual plans
            [
                'plan_name' => 'Abonnement Annuel - 2 séances/semaine',
                'description' => 'Accès à la piscine deux fois par semaine pendant un an',
                'plan_type' => 'annual',
                'visits_per_week' => 2,
                'duration_months' => 12,
                'is_active' => true,
            ],
            [
                'plan_name' => 'Abonnement Annuel - 3 séances/semaine',
                'description' => 'Accès à la piscine trois fois par semaine pendant un an',
                'plan_type' => 'annual',
                'visits_per_week' => 3,
                'duration_months' => 12,
                'is_active' => true,
            ],
            [
                'plan_name' => 'Abonnement Annuel Illimité',
                'description' => 'Accès illimité à la piscine pendant un an',
                'plan_type' => 'annual',
                'visits_per_week' => 7,
                'duration_months' => 12,
                'is_active' => true,
            ],
            // Special plans
            [
                'plan_name' => 'Pass Étudiant Mensuel',
                'description' => 'Abonnement mensuel à tarif réduit pour étudiants (2 séances/semaine)',
                'plan_type' => 'student',
                'visits_per_week' => 2,
                'duration_months' => 1,
                'is_active' => true,
            ],
            [
                'plan_name' => 'Pass Famille Mensuel',
                'description' => 'Abonnement familial mensuel (jusqu\'à 4 personnes)',
                'plan_type' => 'family',
                'visits_per_week' => 3,
                'duration_months' => 1,
                'is_active' => true,
            ],
            [
                'plan_name' => 'Pass Senior Mensuel',
                'description' => 'Abonnement mensuel à tarif réduit pour seniors (+60 ans)',
                'plan_type' => 'senior',
                'visits_per_week' => 2,
                'duration_months' => 1,
                'is_active' => true,
            ],
            [
                'plan_name' => 'Carte 10 Entrées',
                'description' => 'Carte prépayée de 10 entrées, validité 3 mois',
                'plan_type' => 'prepaid',
                'visits_per_week' => null,
                'duration_months' => 3,
                'is_active' => true,
            ],
            [
                'plan_name' => 'Entrée Unique',
                'description' => 'Entrée à la séance',
                'plan_type' => 'single',
                'visits_per_week' => null,
                'duration_months' => null,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['plan_name' => $plan['plan_name']],
                $plan
            );
        }

        $this->command->info('Created ' . count($plans) . ' subscription plans.');
    }
}
