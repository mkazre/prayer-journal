<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMonetizationTables extends Migration
{
    public function up()
    {
        $this->forge->addColumn('courses', [
            'access_mode' => ['type' => 'varchar', 'constraint' => 20, 'default' => 'free', 'after' => 'price_cents'],
        ]);
        $this->forge->addColumn('lessons', [
            'price_cents' => ['type' => 'int', 'constraint' => 11, 'default' => 0, 'after' => 'position'],
        ]);

        $this->forge->addField([
            'id'             => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'           => ['type' => 'varchar', 'constraint' => 100],
            'type'           => ['type' => 'varchar', 'constraint' => 20], // 'subscription' or 'lifetime'
            'billing_interval' => ['type' => 'varchar', 'constraint' => 20, 'null' => true], // 'month' | 'year' | null for lifetime
            'price_cents'    => ['type' => 'int', 'constraint' => 11],
            'currency'       => ['type' => 'varchar', 'constraint' => 3, 'default' => 'USD'],
            'is_active'      => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'created_at'     => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('subscription_plans');

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'plan_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'status'     => ['type' => 'varchar', 'constraint' => 20, 'default' => 'pending'],
            'started_at' => ['type' => 'datetime', 'null' => true],
            'expires_at' => ['type' => 'datetime', 'null' => true],
            'created_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('plan_id', 'subscription_plans', 'id', '', 'CASCADE');
        $this->forge->createTable('user_subscriptions');

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'lesson_id'  => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'lesson_id']);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('lesson_id', 'lessons', 'id', '', 'CASCADE');
        $this->forge->createTable('lesson_unlocks');

        // Generalize payments: allow a payment to fund a plan or a single lesson too.
        $this->forge->addColumn('payments', [
            'plan_id'   => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'course_id'],
            'lesson_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'plan_id'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('payments', ['plan_id', 'lesson_id']);
        $this->forge->dropTable('lesson_unlocks', true);
        $this->forge->dropTable('user_subscriptions', true);
        $this->forge->dropTable('subscription_plans', true);
        $this->forge->dropColumn('lessons', 'price_cents');
        $this->forge->dropColumn('courses', 'access_mode');
    }
}
