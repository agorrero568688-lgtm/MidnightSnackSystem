<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Lomi' => 10,
            'Pancit' => 20,
            'Food Platter' => 30,
            'Barkada Combo' => 40,
            'Combo Burger' => 50,
            'Combo Meal' => 60,
            'Combo Snack' => 70,
            'Silog' => 80,
            'Burger (Buy 1 Take 1)' => 90,
            'Burger (Regular)' => 100,
        ];

        foreach ($categories as $name => $order) {
            Category::query()->updateOrCreate(
                ['name' => $name],
                ['sort_order' => $order, 'is_active' => true],
            );
        }

        $categoryIds = Category::query()->pluck('id', 'name');

        $defaultImages = [
            'LOMI-CHICKEN' => 'images/products/chicken-lomi.jpg',
        ];

        $products = [
            // name, category, sku, price, menu order
            ['Chicken Lomi', 'Lomi', 'LOMI-CHICKEN', 180.00, 10],
            ['Pork Lomi', 'Lomi', 'LOMI-PORK', 180.00, 20],
            ['Beef Lomi', 'Lomi', 'LOMI-BEEF', 200.00, 30],
            ['Seafood Lomi', 'Lomi', 'LOMI-SEAFOOD', 200.00, 40],
            ['Egg Soup', 'Lomi', 'LOMI-EGGSOUP', 120.00, 50],

            ['Chicken Bam-e', 'Pancit', 'PANCIT-CHICKEN', 180.00, 10],
            ['Beef Bam-e', 'Pancit', 'PANCIT-BEEF', 200.00, 20],
            ['Pork Bam-e', 'Pancit', 'PANCIT-PORK', 180.00, 30],
            ['Sotanghon', 'Pancit', 'PANCIT-SOTANGHON', 180.00, 40],
            ['Behon', 'Pancit', 'PANCIT-BEHON', 180.00, 50],

            ['Sizzling Sisig', 'Food Platter', 'PLATTER-SISIG', 200.00, 10],
            ['Sizzling Squid', 'Food Platter', 'PLATTER-SQUID', 200.00, 20],
            ['Letchon Kawali', 'Food Platter', 'PLATTER-LECHON', 200.00, 30],
            ['Halang-Halang', 'Food Platter', 'PLATTER-HALANG', 250.00, 40],
            ['Calamares', 'Food Platter', 'PLATTER-CALAMARES', 250.00, 50],

            ['Fries Cheesy Overload', 'Barkada Combo', 'BARKADA-FRIES-CHEESY', 89.00, 10],
            ['Fries Egg Pansit Canton', 'Barkada Combo', 'BARKADA-FRIES-CANTON', 99.00, 20],
            ['Regular Fries', 'Barkada Combo', 'BARKADA-FRIES', 50.00, 30],

            ['Hamburger with Fries & Coke', 'Combo Burger', 'COMBO-HAMBURGER', 105.00, 10],
            ['Burger with Fries & Coke', 'Combo Burger', 'COMBO-BURGER', 105.00, 20],
            ['Cheesy Foot Long / Fries with Coke', 'Combo Burger', 'COMBO-FOOTLONG', 125.00, 30],

            ['Sisig with Rice and Coke', 'Combo Meal', 'MEAL-SISIG', 129.00, 10],
            ['Chicken with Rice and Coke', 'Combo Meal', 'MEAL-CHICKEN', 109.00, 20],
            ['Burger Patty with Rice and Coke', 'Combo Meal', 'MEAL-BURGER', 99.00, 30],

            ['Fries, Hotdog, Siomai with Coke', 'Combo Snack', 'SNACK-FRIES-HOTDOG-SIOMAI', 99.00, 10],
            ['Fries, Burger with Coke', 'Combo Snack', 'SNACK-FRIES-BURGER', 109.00, 20],

            ['TapSilog', 'Silog', 'SILOG-TAPA', 149.00, 10],
            ['Tocilog', 'Silog', 'SILOG-TOCINO', 89.00, 20],
            ['BangusSilog', 'Silog', 'SILOG-BANGUS', 149.00, 30],
            ['LumpSilog', 'Silog', 'SILOG-LUMPIA', 89.00, 40],
            ['HotSilog', 'Silog', 'SILOG-HOTDOG', 89.00, 50],
            ['PorkSilog', 'Silog', 'SILOG-PORK', 109.00, 60],
            ['BaconSilog', 'Silog', 'SILOG-BACON', 149.00, 70],
            ['LongSilog', 'Silog', 'SILOG-LONGGANISA', 89.00, 80],
            ['SpamSilog', 'Silog', 'SILOG-SPAM', 89.00, 90],
            ['ChickSilog', 'Silog', 'SILOG-CHICKEN', 109.00, 100],
            ['BurgerSilog', 'Silog', 'SILOG-BURGER', 89.00, 110],
            ['HamSilog', 'Silog', 'SILOG-HAM', 89.00, 120],
            ['SiomaiSilog', 'Silog', 'SILOG-SIOMAI', 89.00, 130],
            ['Sisilog', 'Silog', 'SILOG-SISIG', 135.00, 140],

            ['Plain Burger (Buy 1 Take 1)', 'Burger (Buy 1 Take 1)', 'BURGER-B1T1-PLAIN', 75.00, 10],
            ['Egg Burger (Buy 1 Take 1)', 'Burger (Buy 1 Take 1)', 'BURGER-B1T1-EGG', 95.00, 20],
            ['Cheese Burger (Buy 1 Take 1)', 'Burger (Buy 1 Take 1)', 'BURGER-B1T1-CHEESE', 95.00, 30],
            ['Egg/Cheese Burger (Buy 1 Take 1)', 'Burger (Buy 1 Take 1)', 'BURGER-B1T1-EGGCHEESE', 105.00, 40],

            ['Plain Burger', 'Burger (Regular)', 'BURGER-PLAIN', 45.00, 10],
            ['Egg Burger', 'Burger (Regular)', 'BURGER-EGG', 55.00, 20],
            ['Cheese Burger', 'Burger (Regular)', 'BURGER-CHEESE', 55.00, 30],
            ['Egg/Cheese Burger', 'Burger (Regular)', 'BURGER-EGGCHEESE', 65.00, 40],
            ['Cheesy Footlong Hotdog', 'Burger (Regular)', 'BURGER-FOOTLONG', 75.00, 50],
        ];

        foreach ($products as [$name, $category, $sku, $price, $sortOrder]) {
            $product = Product::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'category_id' => $categoryIds[$category] ?? null,
                    'barcode' => null,
                    'cost_price' => 0.00,
                    'selling_price' => $price,
                    'unit' => 'serving',
                    'is_available' => true,
                    'is_active' => true,
                    'is_online' => true,
                    'sort_order' => $sortOrder,
                ],
            );

            // Committed menu art ships with the app, so it survives deploys on
            // hosts with a read-only filesystem. Never overwrite a photo that
            // staff uploaded and that still resolves.
            $defaultImage = $defaultImages[$sku] ?? null;

            if ($defaultImage && $product->image_src === null) {
                $product->update(['image_path' => $defaultImage]);
            }
        }
    }
}
