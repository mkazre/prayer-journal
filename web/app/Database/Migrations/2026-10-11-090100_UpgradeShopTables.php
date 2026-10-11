<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Shop upgrade: product options and variant option values, compare-at
 * (sale) pricing, digital products, collections, product reviews, an order
 * timeline, and richer discount rules.
 */
class UpgradeShopTables extends Migration
{
    public function up()
    {
        $this->forge->addColumn('products', [
            'short_description' => ['type' => 'varchar', 'constraint' => 500, 'null' => true],
            'product_type'      => ['type' => 'varchar', 'constraint' => 20, 'default' => 'physical'],
            'brand'             => ['type' => 'varchar', 'constraint' => 120, 'null' => true],
            'tags'              => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'options'           => ['type' => 'text', 'null' => true],
            'seo_title'         => ['type' => 'varchar', 'constraint' => 200, 'null' => true],
            'seo_description'   => ['type' => 'varchar', 'constraint' => 320, 'null' => true],
            'is_featured'       => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'track_inventory'   => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'allow_backorder'   => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'published_at'      => ['type' => 'datetime', 'null' => true],
        ]);

        $this->forge->addColumn('product_variants', [
            'compare_at_cents'  => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'option1'           => ['type' => 'varchar', 'constraint' => 100, 'null' => true],
            'option2'           => ['type' => 'varchar', 'constraint' => 100, 'null' => true],
            'option3'           => ['type' => 'varchar', 'constraint' => 100, 'null' => true],
            'position'          => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'image_path'        => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'digital_file_path' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'is_active'         => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
        ]);

        $this->forge->addColumn('product_images', [
            'alt' => ['type' => 'varchar', 'constraint' => 200, 'null' => true],
        ]);

        $this->forge->addColumn('product_categories', [
            'description' => ['type' => 'text', 'null' => true],
            'position'    => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ]);

        $this->forge->addField([
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'title'       => ['type' => 'varchar', 'constraint' => 150],
            'slug'        => ['type' => 'varchar', 'constraint' => 170],
            'description' => ['type' => 'text', 'null' => true],
            'image_path'  => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'is_featured' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'position'    => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'created_at'  => ['type' => 'datetime', 'null' => true],
            'updated_at'  => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('collections');

        $this->forge->addField([
            'id'            => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'collection_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'product_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'position'      => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['collection_id', 'product_id']);
        $this->forge->addForeignKey('collection_id', 'collections', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->createTable('collection_products');

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'rating'     => ['type' => 'int', 'constraint' => 1],
            'title'      => ['type' => 'varchar', 'constraint' => 150, 'null' => true],
            'body'       => ['type' => 'text', 'null' => true],
            'status'     => ['type' => 'varchar', 'constraint' => 20, 'default' => 'published'],
            'created_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['product_id', 'user_id']);
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('product_reviews');

        $this->forge->addColumn('discounts', [
            'description'        => ['type' => 'varchar', 'constraint' => 200, 'null' => true],
            'min_subtotal_cents' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'is_active'          => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
        ]);

        $this->forge->addColumn('orders', [
            'customer_name'   => ['type' => 'varchar', 'constraint' => 150, 'null' => true],
            'phone'           => ['type' => 'varchar', 'constraint' => 40, 'null' => true],
            'shipping_method' => ['type' => 'varchar', 'constraint' => 100, 'null' => true],
            'customer_note'   => ['type' => 'text', 'null' => true],
            'admin_note'      => ['type' => 'text', 'null' => true],
            'paid_at'         => ['type' => 'datetime', 'null' => true],
        ]);

        $this->forge->addColumn('order_items', [
            'product_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'sku'        => ['type' => 'varchar', 'constraint' => 60, 'null' => true],
        ]);

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'order_id'   => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'type'       => ['type' => 'varchar', 'constraint' => 30],
            'message'    => ['type' => 'text'],
            'created_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('order_id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', '', 'CASCADE');
        $this->forge->createTable('order_events');
    }

    public function down()
    {
        $this->forge->dropTable('order_events', true);
        $this->forge->dropColumn('order_items', ['product_id', 'sku']);
        $this->forge->dropColumn('orders', ['customer_name', 'phone', 'shipping_method', 'customer_note', 'admin_note', 'paid_at']);
        $this->forge->dropColumn('discounts', ['description', 'min_subtotal_cents', 'is_active']);
        $this->forge->dropTable('product_reviews', true);
        $this->forge->dropTable('collection_products', true);
        $this->forge->dropTable('collections', true);
        $this->forge->dropColumn('product_categories', ['description', 'position']);
        $this->forge->dropColumn('product_images', ['alt']);
        $this->forge->dropColumn('product_variants', [
            'compare_at_cents', 'option1', 'option2', 'option3', 'position', 'image_path', 'digital_file_path', 'is_active',
        ]);
        $this->forge->dropColumn('products', [
            'short_description', 'product_type', 'brand', 'tags', 'options', 'seo_title', 'seo_description',
            'is_featured', 'track_inventory', 'allow_backorder', 'published_at',
        ]);
    }
}
