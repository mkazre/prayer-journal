<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Optional starter catalogue for a fresh store:
 *
 *   php spark db:seed ShopStarterSeeder
 *
 * Creates store settings (ZAR, 15% VAT included in prices), South African
 * and international shipping, categories, a "Prayer Journal" collection,
 * and two products for the book: the print edition and the PDF eBook.
 * Prices are placeholders — review them in Studio > Products before going
 * live. Safe to re-run: existing records (matched by slug/code) are kept.
 */
class ShopStarterSeeder extends Seeder
{
    public function run()
    {
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        foreach ([
            'currency' => 'ZAR', 'store_country' => 'ZA', 'prices_include_tax' => '1', 'tax_label' => 'VAT',
            'free_shipping_over_cents' => '75000', 'shipping_promise' => 'Tracked delivery in 3–5 days', 'returns_promise' => '30-day returns',
        ] as $k => $v) {
            if (! $db->table('app_settings')->where('setting_key', $k)->countAllResults()) {
                $db->table('app_settings')->insert(['setting_key' => $k, 'setting_value' => $v]);
            }
        }

        if (! $db->table('inventory_locations')->countAllResults()) {
            $db->table('inventory_locations')->insert(['name' => 'Main warehouse']);
        }
        $locationId = (int) $db->table('inventory_locations')->orderBy('id')->get()->getRow('id');

        if (! $db->table('shipping_zones')->countAllResults()) {
            $db->table('shipping_zones')->insert(['name' => 'South Africa', 'countries' => 'ZA']);
            $za = (int) $db->insertID();
            $db->table('shipping_rates')->insertBatch([
                ['zone_id' => $za, 'name' => 'Standard courier (3–5 days)', 'price_cents' => 8500, 'min_order_cents' => null],
                ['zone_id' => $za, 'name' => 'Express (1–2 days)', 'price_cents' => 15000, 'min_order_cents' => null],
            ]);
            $db->table('shipping_zones')->insert(['name' => 'Southern Africa', 'countries' => 'BW,NA,LS,SZ,ZW,MZ,ZM,MW']);
            $sa = (int) $db->insertID();
            $db->table('shipping_rates')->insert(['zone_id' => $sa, 'name' => 'Regional courier (5–10 days)', 'price_cents' => 32000, 'min_order_cents' => null]);
            $db->table('shipping_zones')->insert(['name' => 'Rest of the world', 'countries' => '*']);
            $row = (int) $db->insertID();
            $db->table('shipping_rates')->insert(['zone_id' => $row, 'name' => 'International tracked (10–20 days)', 'price_cents' => 45000, 'min_order_cents' => null]);
        }
        if (! $db->table('tax_rules')->where('country', 'ZA')->countAllResults()) {
            $db->table('tax_rules')->insert(['country' => 'ZA', 'region' => null, 'rate_percent' => 15]);
        }

        $categories = [];
        foreach ([['books', 'Books', 'Devotionals and teaching books.'], ['journals', 'Journals', 'Guided journals for prayer and study.'], ['digital', 'Digital downloads', 'eBooks and printables, delivered instantly.']] as $i => [$slug, $name, $desc]) {
            $row = $db->table('product_categories')->where('slug', $slug)->get()->getRowArray();
            if (! $row) {
                $db->table('product_categories')->insert(['name' => $name, 'slug' => $slug, 'description' => $desc, 'position' => $i]);
                $categories[$slug] = (int) $db->insertID();
            } else {
                $categories[$slug] = (int) $row['id'];
            }
        }

        $collection = $db->table('collections')->where('slug', 'prayer-journal')->get()->getRowArray();
        if (! $collection) {
            $db->table('collections')->insert([
                'title' => 'My Prayer Journal', 'slug' => 'prayer-journal', 'is_featured' => 1, 'position' => 0,
                'description' => 'The Believer\'s Prayer Companion by Nomvula Rakolote — in print and as an eBook.',
                'image_path' => $this->copyImage('journal-title.jpg'), 'created_at' => $now, 'updated_at' => $now,
            ]);
            $collectionId = (int) $db->insertID();
        } else {
            $collectionId = (int) $collection['id'];
        }

        $description = '<p><strong>My Prayer Journal: The Believer\'s Prayer Companion</strong> by Nomvula Rakolote is a guided journal that helps you keep your connection with your heavenly "homeland" every day.</p>'
            . '<ul><li>Six prayer secrets — from doing away with religion to fasting and spiritual warfare</li>'
            . '<li>121 undated devotions, each anchored in scripture</li>'
            . '<li>Space every day for your prayer points and supporting scriptures, your meditation and your gratitude</li>'
            . '<li>Free from calendar dates — start any day and go at your own pace</li></ul>'
            . '<p>Pair it with the free online course for the full experience: the same devotions with the whole Bible built in and a private digital journal.</p>';

        $this->product([
            'slug' => 'my-prayer-journal-print', 'title' => 'My Prayer Journal — Print Edition', 'brand' => 'Nomvula Rakolote',
            'short_description' => 'The Believer\'s Prayer Companion. 283 pages of teaching, devotions and journaling space.',
            'description' => $description, 'product_type' => 'physical', 'category_id' => $categories['journals'],
            'tags' => 'prayer, journal, devotional, gift', 'is_featured' => 1,
            'variants' => [['sku' => 'PJ-PRINT', 'price' => 25000, 'weight' => 520, 'stock' => 50]],
            'images' => ['journal-cover.jpg', 'journal-title.jpg', 'journal-spread.jpg'],
        ], $collectionId, $locationId);

        $this->product([
            'slug' => 'my-prayer-journal-ebook', 'title' => 'My Prayer Journal — eBook (PDF)', 'brand' => 'Nomvula Rakolote',
            'short_description' => 'Instant download. Print the pages you need, or read on any device.',
            'description' => $description, 'product_type' => 'digital', 'category_id' => $categories['digital'],
            'tags' => 'prayer, journal, ebook, pdf', 'is_featured' => 1,
            'variants' => [['sku' => 'PJ-EBOOK', 'price' => 12000, 'weight' => null, 'stock' => 0, 'file' => $this->copyDigital()]],
            'images' => ['journal-ebook.jpg', 'journal-spread.jpg'],
        ], $collectionId, $locationId);

        site_setting('__flush__');
    }

    private function product(array $p, int $collectionId, int $locationId): void
    {
        $db = $this->db;
        if ($db->table('products')->where('slug', $p['slug'])->countAllResults()) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $db->table('products')->insert([
            'title' => $p['title'], 'slug' => $p['slug'], 'brand' => $p['brand'], 'short_description' => $p['short_description'],
            'description' => $p['description'], 'product_type' => $p['product_type'], 'category_id' => $p['category_id'],
            'tags' => $p['tags'], 'is_featured' => $p['is_featured'], 'status' => 'published', 'track_inventory' => $p['product_type'] === 'digital' ? 0 : 1,
            'published_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $productId = (int) $db->insertID();
        foreach ($p['variants'] as $i => $v) {
            $db->table('product_variants')->insert([
                'product_id' => $productId, 'sku' => $v['sku'], 'price_cents' => $v['price'], 'currency' => 'ZAR', 'weight_grams' => $v['weight'],
                'position' => $i, 'is_active' => 1, 'digital_file_path' => $v['file'] ?? null, 'created_at' => $now,
            ]);
            $variantId = (int) $db->insertID();
            if ($v['stock'] > 0) {
                (new \App\Modules\Shop\Models\InventoryModel())->adjust($variantId, $locationId, $v['stock'], 'initial_stock');
            }
        }
        foreach ($p['images'] as $i => $file) {
            $db->table('product_images')->insert(['product_id' => $productId, 'path' => $this->copyImage($file), 'position' => $i, 'alt' => $p['title']]);
        }
        $db->table('collection_products')->insert(['collection_id' => $collectionId, 'product_id' => $productId, 'position' => 0]);
    }

    private function copyImage(string $file): string
    {
        $dir = FCPATH . 'uploads/products/starter';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (! is_file($dir . '/' . $file)) {
            copy(APPPATH . 'Database/Data/shop/' . $file, $dir . '/' . $file);
        }

        return 'uploads/products/starter/' . $file;
    }

    /**
     * The eBook lives outside the public folder; buyers download it through
     * their order page.
     */
    private function copyDigital(): string
    {
        $dir = WRITEPATH . 'uploads/digital';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (! is_file($dir . '/prayer-journal.pdf')) {
            copy(APPPATH . 'Database/Data/prayer-journal.pdf', $dir . '/prayer-journal.pdf');
        }

        return 'private/digital/prayer-journal.pdf';
    }
}
