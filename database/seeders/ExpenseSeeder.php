<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Faker\Factory as Faker;
use App\Models\Finance\Expense;
use App\Models\Staff\Staff;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        // Récupérer quelques staff IDs pour 'created_by'
        $staffIds = Staff::pluck('staff_id')->toArray();
        if (empty($staffIds)) {
            $this->command->info("Aucun staff trouvé pour attribuer les dépenses.");
            return;
        }

        // Période : 2023 à aujourd'hui (comme les abonnements)
        $startYear = 2023;
        $endYear = 2025;
        $months = [];

        // Générer la liste de tous les mois
        $current = Carbon::createFromDate($startYear, 1, 1);
        $now = Carbon::now();

        while ($current->lte($now)) {
            $months[] = $current->copy();
            $current->addMonth();
        }

        $expenses = [];

        foreach ($months as $month) {
            $daysInMonth = $month->daysInMonth;

            // 1. SALAIRES (Fixes par mois)
            // Disons ~1.5M DA de salaires totaux par mois (varie légèrement)
            $totalSalary = $faker->numberBetween(1400000, 1600000); 
            $expenses[] = [
                'title' => 'Salaires Personnel - ' . $month->format('F Y'),
                'amount' => $totalSalary,
                'expense_date' => $month->copy()->day(28)->format('Y-m-d'), // Fin du mois
                'category' => 'salary',
                'description' => 'Paiement mensuel des salaires (Coachs, Réception, Admin, Maintenance)',
                'payment_method' => 'bank_transfer',
                'reference' => 'SAL-' . $month->format('Ym'),
                'created_by' => $faker->randomElement($staffIds),
                'created_at' => $month->copy()->day(28),
                'updated_at' => $month->copy()->day(28),
            ];

            // 2. ELECTRICITÉ (Facture bimestrielle souvent, simulons mensuel pour lisser)
            // ~200,000 DA / mois (piscine chauffe)
            $elecAmount = $month->month >= 10 || $month->month <= 3 
                ? $faker->numberBetween(250000, 350000) // Hiver (plus cher)
                : $faker->numberBetween(150000, 200000); // Été

            $expenses[] = [
                'title' => 'Facture Sonelgaz - ' . $month->format('F Y'),
                'amount' => $elecAmount,
                'expense_date' => $month->copy()->day(15)->format('Y-m-d'),
                'category' => 'electricity',
                'description' => 'Consommation électrique (Pompes, Chauffage, Éclairage)',
                'payment_method' => 'check',
                'reference' => 'ELEC-' . $month->format('Ym'),
                'created_by' => $faker->randomElement($staffIds),
                'created_at' => $month->copy()->day(15),
                'updated_at' => $month->copy()->day(15),
            ];

            // 3. EAU (Mensuel)
            $waterAmount = $faker->numberBetween(80000, 120000);
            $expenses[] = [
                'title' => 'Facture ADE - ' . $month->format('F Y'),
                'amount' => $waterAmount,
                'expense_date' => $month->copy()->day(20)->format('Y-m-d'),
                'category' => 'water', // Ou 'other' si 'water' n'est pas dans l'enum, on utilisera 'maintenance' ou 'other'
                'description' => 'Consommation eau et assainissement',
                'payment_method' => 'check',
                'reference' => 'EAU-' . $month->format('Ym'),
                'created_by' => $faker->randomElement($staffIds),
                'created_at' => $month->copy()->day(20),
                'updated_at' => $month->copy()->day(20),
            ];

            // 4. PRODUITS PISCINE (Chlore, pH, etc.) - Aléatoire 1 à 3 fois par mois
            $nbCommandes = $faker->numberBetween(1, 3);
            for ($i = 0; $i < $nbCommandes; $i++) {
                $day = $faker->numberBetween(1, $daysInMonth);
                $expenses[] = [
                    'title' => 'Achat Produits Chimiques',
                    'amount' => $faker->numberBetween(50000, 150000),
                    'expense_date' => $month->copy()->day($day)->format('Y-m-d'),
                    'category' => 'pool_products',
                    'description' => 'Chlore, pH Minus, Algicide',
                    'payment_method' => 'check',
                    'reference' => 'CHIM-' . $faker->bothify('##??'),
                    'created_by' => $faker->randomElement($staffIds),
                    'created_at' => $month->copy()->day($day),
                    'updated_at' => $month->copy()->day($day),
                ];
            }

            // 5. MAINTENANCE (Réparations diverses) - Aléatoire
            if ($faker->boolean(40)) { // 40% de chance d'avoir une maintenance ce mois-ci
                $day = $faker->numberBetween(1, $daysInMonth);
                $expenses[] = [
                    'title' => $faker->randomElement(['Réparation Pompe', 'Maintenance Filtres', 'Peinture Vestiaires', 'Plomberie Douches']),
                    'amount' => $faker->numberBetween(20000, 200000),
                    'expense_date' => $month->copy()->day($day)->format('Y-m-d'),
                    'category' => 'maintenance',
                    'description' => 'Intervention technique',
                    'payment_method' => 'cash',
                    'reference' => 'MAINT-' . $faker->bothify('##??'),
                    'created_by' => $faker->randomElement($staffIds),
                    'created_at' => $month->copy()->day($day),
                    'updated_at' => $month->copy()->day($day),
                ];
            }
        }

        // Insert in chunks to avoid memory issues
        foreach (array_chunk($expenses, 50) as $chunk) {
             Expense::insert($chunk); // Use Eloquent/Query Builder insert for speed
        }
    }
}
