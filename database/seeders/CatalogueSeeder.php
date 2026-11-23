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
            'Chips & Crackers' => 10,
            'Biscuits' => 20,
            'Drinks' => 30,
            'Instant Noodles' => 40,
            'Candies' => 50,
            'Bread & Bakery' => 60,
            'Others' => 70,
        ];

        foreach ($categories as $name => $order) {
            Category::query()->firstOrCreate(
                ['name' => $name],
                ['sort_order' => $order, 'is_active' => true],
            );
        }

        $categoryIds = Category::query()->pluck('id', 'name');

        $products = [
            // name, category, sku, barcode, cost, price, stock, unit
            ['Piattos Cheese 40g', 'Chips & Crackers', 'PIATTOS-CHS', '4800016641231', 22.00, 30.00, 48, 'pc'],
            ['Nova Country Cheddar 40g', 'Chips & Crackers', 'NOVA-CHED', '4800016641232', 20.50, 28.00, 36, 'pc'],
            ['Chippy BBQ 110g', 'Chips & Crackers', 'CHIPPY-BBQ', '4800016641233', 24.00, 32.00, 30, 'pc'],
            ['Boy Bawang Cornick 100g', 'Chips & Crackers', 'BAWANG-GRL', '4800016641234', 18.00, 25.00, 42, 'pc'],
            ['Roller Coaster 90g', 'Chips & Crackers', 'ROLLER-CHS', '4800016641235', 19.00, 26.00, 24, 'pc'],
            ['Skyflakes Crackers 25g', 'Biscuits', 'SKYFLAKES-25', '4800016641236', 8.00, 12.00, 80, 'pc'],
            ['Fita Crackers 300g', 'Biscuits', 'FITA-300', '4800016641237', 30.00, 38.00, 22, 'pc'],
            ['Rebisco Sandwich 10s', 'Biscuits', 'REBISCO-SW', '4800016641238', 36.00, 45.00, 18, 'pack'],
            ['Cream-O Vanilla 10s', 'Biscuits', 'CREAMO-VAN', '4800016641239', 44.00, 55.00, 15, 'pack'],
            ['Choco Mucho 10s', 'Biscuits', 'CHOCOMUCHO', '4800016641240', 38.00, 48.00, 16, 'pack'],
            ['Jack n Jill Creams 10s', 'Biscuits', 'JJC-10', '4800016641241', 32.00, 40.00, 12, 'pack'],
            ['Coke Mismo 300ml', 'Drinks', 'COKE-300', '4800016641242', 17.50, 22.00, 60, 'bottle'],
            ['Royal Tru-Orange 300ml', 'Drinks', 'ROYAL-300', '4800016641243', 17.50, 22.00, 48, 'bottle'],
            ['Sprite 300ml', 'Drinks', 'SPRITE-300', '4800016641244', 17.50, 22.00, 42, 'bottle'],
            ['C2 Green Tea 230ml', 'Drinks', 'C2-GREEN', '4800016641245', 20.00, 25.00, 36, 'bottle'],
            ['Chuckie Chocolate 250ml', 'Drinks', 'CHUCKIE-250', '4800016641246', 32.00, 40.00, 20, 'pack'],
            ['Del Monte Pineapple 240ml', 'Drinks', 'DELMONTE-PA', '4800016641247', 20.00, 25.00, 24, 'can'],
            ['Kopiko 3-in-1', 'Drinks', 'KOPIKO-3IN1', '4800016641248', 7.00, 10.00, 100, 'sachet'],
            ['Lucky Me Pancit Canton Original', 'Instant Noodles', 'LM-CANTON', '4800016641249', 14.00, 18.00, 90, 'pack'],
            ['Lucky Me Beef Noodles', 'Instant Noodles', 'LM-BEEF', '4800016641250', 11.00, 15.00, 80, 'pack'],
            ['Payless Xtra Big', 'Instant Noodles', 'PAYLESS-XB', '4800016641251', 15.00, 20.00, 50, 'pack'],
            ['Maxx Candy 10s', 'Candies', 'MAXX-10', '4800016641252', 11.00, 15.00, 40, 'pack'],
            ['White Rabbit 10s', 'Candies', 'WRABBIT-10', '4800016641253', 22.00, 30.00, 25, 'pack'],
            ['Stik-O Chocolate 100g', 'Candies', 'STIKO-CHO', '4800016641254', 18.00, 25.00, 30, 'pack'],
            ['Gardenia Classic 400g', 'Bread & Bakery', 'GARDENIA-400', '4800016641255', 68.00, 85.00, 10, 'loaf'],
            ['Pandesal 6s', 'Bread & Bakery', 'PANDESAL-6', '4800016641256', 22.00, 30.00, 12, 'pack'],
            ['Ice Candy (assorted)', 'Others', 'ICECANDY', null, 4.00, 10.00, 40, 'pc'],
            ['Bottled Water 500ml', 'Others', 'WATER-500', '4800016641257', 12.00, 20.00, 60, 'bottle'],
        ];

        foreach ($products as [$name, $category, $sku, $barcode, $cost, $price, $stock, $unit]) {
            Product::query()->firstOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'category_id' => $categoryIds[$category] ?? null,
                    'barcode' => $barcode,
                    'cost_price' => $cost,
                    'selling_price' => $price,
                    'stock_qty' => $stock,
                    'low_stock_threshold' => 10,
                    'unit' => $unit,
                    'track_stock' => true,
                    'is_active' => true,
                ],
            );
        }
    }
}
