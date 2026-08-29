<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateShopOrderingTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code'            => ['type' => 'varchar', 'constraint' => 40],
            'type'            => ['type' => 'varchar', 'constraint' => 10, 'default' => 'percent'],
            'value'           => ['type' => 'int', 'constraint' => 11],
            'max_redemptions' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'times_redeemed'  => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'starts_at'       => ['type' => 'datetime', 'null' => true],
            'expires_at'      => ['type' => 'datetime', 'null' => true],
            'created_at'      => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('discounts');

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'session_key'=> ['type' => 'varchar', 'constraint' => 60, 'null' => true],
            'status'     => ['type' => 'varchar', 'constraint' => 20, 'default' => 'active'],
            'created_at' => ['type' => 'datetime', 'null' => true],
            'updated_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('carts');

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'cart_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'variant_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'quantity'   => ['type' => 'int', 'constraint' => 11, 'default' => 1],
            'unit_price_cents' => ['type' => 'int', 'constraint' => 11],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['cart_id', 'variant_id']);
        $this->forge->addForeignKey('cart_id', 'carts', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', '', 'CASCADE');
        $this->forge->createTable('cart_items');

        $this->forge->addField([
            'id'                => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'order_number'      => ['type' => 'varchar', 'constraint' => 30],
            'user_id'           => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'email'             => ['type' => 'varchar', 'constraint' => 255],
            'status'            => ['type' => 'varchar', 'constraint' => 20, 'default' => 'pending'],
            'subtotal_cents'    => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'discount_cents'    => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'tax_cents'         => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'shipping_cents'    => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'total_cents'       => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'currency'          => ['type' => 'varchar', 'constraint' => 3, 'default' => 'USD'],
            'discount_id'       => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'shipping_address'  => ['type' => 'text', 'null' => true],
            'created_at'        => ['type' => 'datetime', 'null' => true],
            'updated_at'        => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('order_number');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('discount_id', 'discounts', 'id', '', 'SET NULL');
        $this->forge->createTable('orders');

        $this->forge->addField([
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'order_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'variant_id'  => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'title_snapshot' => ['type' => 'varchar', 'constraint' => 250],
            'quantity'    => ['type' => 'int', 'constraint' => 11],
            'unit_price_cents' => ['type' => 'int', 'constraint' => 11],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', '', 'RESTRICT');
        $this->forge->createTable('order_items');

        $this->forge->addField([
            'id'             => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'order_id'       => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'status'         => ['type' => 'varchar', 'constraint' => 20, 'default' => 'pending'],
            'tracking_number'=> ['type' => 'varchar', 'constraint' => 100, 'null' => true],
            'carrier'        => ['type' => 'varchar', 'constraint' => 60, 'null' => true],
            'shipped_at'     => ['type' => 'datetime', 'null' => true],
            'delivered_at'   => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', '', 'CASCADE');
        $this->forge->createTable('order_fulfillments');

        $this->forge->addField([
            'id'              => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'order_id'        => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'course_id'       => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'user_id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'gateway'         => ['type' => 'varchar', 'constraint' => 30],
            'gateway_ref'     => ['type' => 'varchar', 'constraint' => 100, 'null' => true],
            'amount_cents'    => ['type' => 'int', 'constraint' => 11],
            'currency'        => ['type' => 'varchar', 'constraint' => 3, 'default' => 'USD'],
            'status'          => ['type' => 'varchar', 'constraint' => 20, 'default' => 'pending'],
            'created_at'      => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('course_id', 'courses', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('payments');
    }

    public function down()
    {
        $this->forge->dropTable('payments', true);
        $this->forge->dropTable('order_fulfillments', true);
        $this->forge->dropTable('order_items', true);
        $this->forge->dropTable('orders', true);
        $this->forge->dropTable('cart_items', true);
        $this->forge->dropTable('carts', true);
        $this->forge->dropTable('discounts', true);
    }
}
