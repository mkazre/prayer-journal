<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateShopCatalogTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'parent_id'   => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'name'        => ['type' => 'varchar', 'constraint' => 150],
            'slug'        => ['type' => 'varchar', 'constraint' => 170],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addForeignKey('parent_id', 'product_categories', 'id', '', 'SET NULL');
        $this->forge->createTable('product_categories');

        $this->forge->addField([
            'id'             => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'vendor_id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'category_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'title'          => ['type' => 'varchar', 'constraint' => 200],
            'slug'           => ['type' => 'varchar', 'constraint' => 220],
            'description'    => ['type' => 'text', 'null' => true],
            'status'         => ['type' => 'varchar', 'constraint' => 20, 'default' => 'draft'],
            'created_at'     => ['type' => 'datetime', 'null' => true],
            'updated_at'     => ['type' => 'datetime', 'null' => true],
            'deleted_at'     => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addForeignKey('vendor_id', 'users', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('category_id', 'product_categories', 'id', '', 'SET NULL');
        $this->forge->createTable('products');

        $this->forge->addField([
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'product_id'  => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'sku'         => ['type' => 'varchar', 'constraint' => 60],
            'option_name' => ['type' => 'varchar', 'constraint' => 100, 'null' => true],
            'price_cents' => ['type' => 'int', 'constraint' => 11],
            'currency'    => ['type' => 'varchar', 'constraint' => 3, 'default' => 'USD'],
            'weight_grams'=> ['type' => 'int', 'constraint' => 11, 'null' => true],
            'created_at'  => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('sku');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->createTable('product_variants');

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'path'       => ['type' => 'varchar', 'constraint' => 255],
            'position'   => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->createTable('product_images');
    }

    public function down()
    {
        $this->forge->dropTable('product_images', true);
        $this->forge->dropTable('product_variants', true);
        $this->forge->dropTable('products', true);
        $this->forge->dropTable('product_categories', true);
    }
}
