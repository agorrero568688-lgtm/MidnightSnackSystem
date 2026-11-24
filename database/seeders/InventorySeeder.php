<?php

namespace Database\Seeders;

use App\Models\Inventory;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = [
            // name, category, quantity, unit, reorder level, cost per unit
            ['Pork', 'Meat & Seafood', 8.000, 'kg', 3.000, 280.00],
            ['Chicken', 'Meat & Seafood', 8.000, 'kg', 3.000, 190.00],
            ['Beef', 'Meat & Seafood', 6.000, 'kg', 2.000, 380.00],
            ['Squid', 'Meat & Seafood', 4.000, 'kg', 1.500, 320.00],
            ['Bangus', 'Meat & Seafood', 3.000, 'kg', 1.000, 220.00],
            ['Regular Hotdogs', 'Meat & Seafood', 6.000, 'packs', 2.000, 95.00],
            ['Footlong Hotdogs', 'Meat & Seafood', 4.000, 'packs', 1.000, 150.00],
            ['Siomai', 'Meat & Seafood', 6.000, 'packs', 2.000, 150.00],
            ['Lumpia', 'Meat & Seafood', 4.000, 'packs', 1.000, 110.00],
            ['Bacon', 'Meat & Seafood', 5.000, 'packs', 2.000, 180.00],
            ['Spam', 'Meat & Seafood', 4.000, 'packs', 1.000, 210.00],
            ['Ham', 'Meat & Seafood', 4.000, 'packs', 1.000, 120.00],
            ['Longganisa', 'Meat & Seafood', 5.000, 'packs', 2.000, 140.00],
            ['Tocino', 'Meat & Seafood', 5.000, 'packs', 2.000, 130.00],
            ['Tapa', 'Meat & Seafood', 4.000, 'packs', 1.000, 160.00],
            ['Burger Patties', 'Meat & Seafood', 6.000, 'packs', 2.000, 180.00],

            ['Lomi Noodles', 'Noodles & Rice', 5.000, 'kg', 2.000, 120.00],
            ['Pancit / Bam-e Noodles', 'Noodles & Rice', 5.000, 'kg', 2.000, 110.00],
            ['Sotanghon', 'Noodles & Rice', 3.000, 'kg', 1.000, 140.00],
            ['Bihon', 'Noodles & Rice', 3.000, 'kg', 1.000, 110.00],
            ['Rice', 'Noodles & Rice', 25.000, 'kg', 10.000, 55.00],

            ['Burger Buns', 'Bread & Frozen', 10.000, 'packs', 4.000, 75.00],
            ['Hotdog Buns', 'Bread & Frozen', 4.000, 'packs', 1.000, 60.00],
            ['French Fries', 'Bread & Frozen', 10.000, 'kg', 4.000, 120.00],

            ['Eggs', 'Dairy & Eggs', 6.000, 'trays', 2.000, 240.00],
            ['Cheese', 'Dairy & Eggs', 3.000, 'kg', 1.000, 480.00],
            ['Cheese Sauce', 'Dairy & Eggs', 2.000, 'kg', 1.000, 220.00],
            ['Mayonnaise', 'Dairy & Eggs', 3.000, 'bottles', 1.000, 120.00],

            ['Cooking Oil', 'Condiments & Oil', 10.000, 'liters', 4.000, 95.00],
            ['Soy Sauce', 'Condiments & Oil', 4.000, 'liters', 1.500, 75.00],
            ['Vinegar', 'Condiments & Oil', 3.000, 'liters', 1.000, 45.00],
            ['Ketchup', 'Condiments & Oil', 4.000, 'bottles', 1.500, 85.00],
            ['Salt', 'Condiments & Oil', 3.000, 'kg', 1.000, 25.00],
            ['Black Pepper', 'Condiments & Oil', 1.000, 'kg', 0.300, 400.00],
            ['Garlic', 'Condiments & Oil', 2.000, 'kg', 0.500, 120.00],
            ['Onion', 'Condiments & Oil', 3.000, 'kg', 1.000, 90.00],
            ['Calamansi', 'Condiments & Oil', 2.000, 'kg', 0.500, 80.00],
            ['Spring Onions', 'Condiments & Oil', 1.000, 'kg', 0.300, 150.00],
            ['Chili', 'Condiments & Oil', 1.000, 'kg', 0.300, 180.00],

            ['Coke (300ml)', 'Beverages', 48.000, 'bottles', 12.000, 18.00],
            ['Bottled Water', 'Beverages', 24.000, 'bottles', 8.000, 12.00],

            ['Meal Boxes', 'Packaging', 10.000, 'packs', 4.000, 180.00],
            ['Paper Bags', 'Packaging', 8.000, 'packs', 3.000, 60.00],
            ['Plastic Utensils', 'Packaging', 6.000, 'packs', 2.000, 90.00],
            ['Aluminum Foil', 'Packaging', 3.000, 'rolls', 1.000, 120.00],
            ['Receipt Paper', 'Packaging', 10.000, 'rolls', 3.000, 25.00],
            ['Sauce Cups', 'Packaging', 5.000, 'packs', 2.000, 75.00],
        ];

        foreach ($ingredients as [$name, $category, $quantity, $unit, $reorderLevel, $costPerUnit]) {
            Inventory::query()->updateOrCreate(
                ['name' => $name],
                [
                    'category' => $category,
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'reorder_level' => $reorderLevel,
                    'cost_per_unit' => $costPerUnit,
                    'is_active' => true,
                ],
            );
        }
    }
}
