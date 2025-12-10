<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pool\TaskTemplate;

class TaskTemplateSeeder extends Seeder
{
    public function run()
    {
        // Default Daily Template
        TaskTemplate::create([
            'name' => 'Check-list Quotidienne Standard',
            'type' => 'daily',
            'is_active' => true,
            'items' => [
                [
                    'label' => 'Skimmers Nettoyés',
                    'type' => 'checkbox',
                    'key' => 'skimmer_cleaned'
                ],
                [
                    'label' => 'Fond Aspiré',
                    'type' => 'checkbox',
                    'key' => 'vacuum_done'
                ],
                [
                    'label' => 'Débris Retirés',
                    'type' => 'checkbox',
                    'key' => 'debris_removed'
                ],
                [
                    'label' => 'Bondes Vérifiées',
                    'type' => 'checkbox',
                    'key' => 'drains_checked'
                ],
                [
                    'label' => 'Grilles Anti-Noyade',
                    'type' => 'checkbox',
                    'key' => 'drain_covers_inspected'
                ],
                [
                    'label' => 'Éclairage OK',
                    'type' => 'checkbox',
                    'key' => 'lighting_checked'
                ],
                [
                    'label' => 'Test Clarté Visuelle',
                    'type' => 'checkbox',
                    'key' => 'clarity_test_passed'
                ],
                [
                    'label' => 'Pression (bar)',
                    'type' => 'number',
                    'key' => 'pressure_reading',
                    'step' => '0.1'
                ],
                [
                    'label' => 'État Pompe',
                    'type' => 'select',
                    'key' => 'pump_status',
                    'options' => [
                        'ok' => 'OK',
                        'noisy' => 'Bruyante',
                        'off' => 'Arrêt'
                    ]
                ],
                [
                    'label' => 'Anomalies / Notes',
                    'type' => 'textarea',
                    'key' => 'anomalies_comment'
                ]
            ]
        ]);

        // Default Weekly Template
        TaskTemplate::create([
            'name' => 'Check-list Hebdomadaire Standard',
            'type' => 'weekly',
            'is_active' => true,
            'items' => [
                [
                    'label' => 'Brossage Complet',
                    'type' => 'checkbox',
                    'key' => 'brushing_done'
                ],
                [
                    'label' => 'Lavage Filtre (Backwash)',
                    'type' => 'checkbox',
                    'key' => 'backwash_done'
                ],
                [
                    'label' => 'Nettoyage Préfiltre',
                    'type' => 'checkbox',
                    'key' => 'filter_cleaned'
                ],
                [
                    'label' => 'Resserrage Raccords',
                    'type' => 'checkbox',
                    'key' => 'fittings_retightened'
                ],
                [
                    'label' => 'Test Chauffage',
                    'type' => 'checkbox',
                    'key' => 'heater_tested'
                ],
                [
                    'label' => 'Contrôle Doseuse',
                    'type' => 'checkbox',
                    'key' => 'chemical_doser_checked'
                ],
                [
                    'label' => 'Inspection Générale',
                    'type' => 'textarea',
                    'key' => 'general_inspection_comment'
                ]
            ]
        ]);

        // Default Monthly Template
        TaskTemplate::create([
            'name' => 'Check-list Mensuelle Standard',
            'type' => 'monthly',
            'is_active' => true,
            'items' => [
                [
                    'label' => 'Remplacement Eau (Partiel)',
                    'type' => 'checkbox',
                    'key' => 'water_replacement_partial'
                ],
                [
                    'label' => 'Inspection Complète Système',
                    'type' => 'checkbox',
                    'key' => 'full_system_inspection'
                ],
                [
                    'label' => 'Calibration Dosage Chimique',
                    'type' => 'checkbox',
                    'key' => 'chemical_dosing_calibration'
                ],
                [
                    'label' => 'Notes / Observations',
                    'type' => 'textarea',
                    'key' => 'notes'
                ]
            ]
        ]);
    }
}
