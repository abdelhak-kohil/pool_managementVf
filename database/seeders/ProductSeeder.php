<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shop\Category;
use App\Models\Shop\Product;
use App\Models\Shop\ProductImage;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks to allow truncation
        DB::statement('SET session_replication_role = \'replica\';');
        ProductImage::truncate();
        Product::truncate();
        Category::truncate();
        DB::statement('SET session_replication_role = \'origin\';');

        $categories = [
            [
                'name' => 'Maillots de bain',
                'type' => 'product',
                'image' => 'swimwear.png',
                'products' => [
                    ['name' => 'Maillot de bain Homme Pro', 'price' => 4500, 'stock' => 50, 'desc' => 'Maillot de bain de compétition pour homme, résistant au chlore.', 'image' => 'swimwear_men.png'],
                    ['name' => 'Maillot de bain Femme Elite', 'price' => 5500, 'stock' => 40, 'desc' => 'Maillot une pièce haute performance pour l\'entraînement intensif.', 'image' => 'swimwear_women.png'],
                    ['name' => 'Short de bain Loisir', 'price' => 3000, 'stock' => 60, 'desc' => 'Short confortable pour la nage occasionnelle.', 'image' => 'swim_shorts.png'],
                    ['name' => 'Bonnet de bain Silicone', 'price' => 1200, 'stock' => 100, 'desc' => 'Bonnet en silicone durable et confortable.', 'image' => 'swim_cap.png'],
                    ['name' => 'Maillot Enfant Junior', 'price' => 2500, 'stock' => 30, 'desc' => 'Maillot coloré et résistant pour les jeunes nageurs.', 'image' => 'swimwear_kids.png'],
                ]
            ],
            [
                'name' => 'Équipements',
                'type' => 'product',
                'image' => 'equipment.png',
                'products' => [
                    ['name' => 'Lunettes de Natation Pro', 'price' => 2800, 'stock' => 80, 'desc' => 'Lunettes anti-buée avec protection UV.', 'image' => 'equipment.png'],
                    ['name' => 'Palmes Courtes', 'price' => 3500, 'stock' => 40, 'desc' => 'Palmes pour le renforcement musculaire des jambes.', 'image' => 'fins.png'],
                    ['name' => 'Planche de Natation', 'price' => 1500, 'stock' => 50, 'desc' => 'Accessoire indispensable pour travailler les battements.', 'image' => 'kickboard.png'],
                    ['name' => 'Pull Buoy', 'price' => 1800, 'stock' => 45, 'desc' => 'Flotteur pour isoler le travail des bras.', 'image' => 'pull_buoy.png'],
                    ['name' => 'Pince-nez', 'price' => 500, 'stock' => 120, 'desc' => 'Confort optimal pour éviter l\'eau dans le nez.', 'image' => 'nose_clip.png'],
                ]
            ],
            [
                'name' => 'Boissons',
                'type' => 'product',
                'image' => 'drink.png',
                'products' => [
                    ['name' => 'Eau Minérale 50cl', 'price' => 50, 'stock' => 200, 'desc' => 'Eau pure et rafraîchissante.', 'image' => 'water_bottle.png'],
                    ['name' => 'Boisson Isotonique Sport', 'price' => 250, 'stock' => 100, 'desc' => 'Pour une récupération optimale après l\'effort.', 'image' => 'drink.png'],
                    ['name' => 'Jus d\'Orange Frais', 'price' => 300, 'stock' => 30, 'desc' => 'Vitamines naturelles pour le tonus.', 'image' => 'orange_juice.png'],
                    ['name' => 'Soda Zéro', 'price' => 150, 'stock' => 80, 'desc' => 'Boisson gazeuse sans sucre.', 'image' => 'drink.png'],
                    ['name' => 'Eau Gazeuse', 'price' => 80, 'stock' => 150, 'desc' => 'Eau pétillante naturelle.', 'image' => 'water_bottle.png'],
                ]
            ],
            [
                'name' => 'Snacks',
                'type' => 'product',
                'image' => 'snack.png',
                'products' => [
                    ['name' => 'Barre Protéinée Choco', 'price' => 350, 'stock' => 100, 'desc' => '20g de protéines pour la récupération musculaire.', 'image' => 'snack.png'],
                    ['name' => 'Banane', 'price' => 100, 'stock' => 50, 'desc' => 'Fruit frais riche en potassium.', 'image' => 'banana.png'],
                    ['name' => 'Mélange de Noix', 'price' => 400, 'stock' => 60, 'desc' => 'Sachet de noix et fruits secs énergétiques.', 'image' => 'nuts_mix.png'],
                    ['name' => 'Yaourt à boire', 'price' => 120, 'stock' => 40, 'desc' => 'En-cas laitier frais.', 'image' => 'snack.png'],
                    ['name' => 'Sandwich Dinde', 'price' => 450, 'stock' => 20, 'desc' => 'Sandwich frais préparé le jour même.', 'image' => 'sandwich.png'],
                ]
            ],
            [
                'name' => 'Accessoires',
                'type' => 'product',
                'image' => 'accessory.png',
                'products' => [
                    ['name' => 'Serviette Microfibre', 'price' => 2000, 'stock' => 60, 'desc' => 'Séchage ultra-rapide et compacte.', 'image' => 'accessory.png'],
                    ['name' => 'Sac de Sport', 'price' => 4500, 'stock' => 25, 'desc' => 'Grand sac compartimenté pour vos affaires de piscine.', 'image' => 'gym_bag.png'],
                    ['name' => 'Shampoing Doux', 'price' => 800, 'stock' => 50, 'desc' => 'Élimine le chlore et protège les cheveux.', 'image' => 'shampoo.png'],
                    ['name' => 'Gel Douche Sport', 'price' => 700, 'stock' => 55, 'desc' => 'Rafraîchissant et énergisant.', 'image' => 'shampoo.png'],
                    ['name' => 'Cadenas Casier', 'price' => 600, 'stock' => 100, 'desc' => 'Pour sécuriser vos effets personnels.', 'image' => 'accessory.png'],
                ]
            ],
        ];

        foreach ($categories as $catData) {
            $category = Category::create([
                'name' => $catData['name'],
                'type' => $catData['type'],
            ]);

            foreach ($catData['products'] as $prodData) {
                $product = Product::create([
                    'category_id' => $category->id,
                    'name' => $prodData['name'],
                    'description' => $prodData['desc'],
                    'price' => $prodData['price'],
                    'purchase_price' => $prodData['price'] * 0.6, // 40% margin
                    'stock_quantity' => $prodData['stock'],
                    'alert_threshold' => 10,
                    'image_path' => 'images/products/' . $prodData['image'],
                ]);

                // Create primary image
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => 'images/products/' . $prodData['image'],
                    'is_primary' => true,
                ]);
            }
        }
    }
}
