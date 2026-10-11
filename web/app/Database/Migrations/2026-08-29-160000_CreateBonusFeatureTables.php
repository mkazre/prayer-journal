<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBonusFeatureTables extends Migration
{
    public function up()
    {
        $this->forge->addColumn('enrollments', [
            'last_lesson_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'progress_percent'],
        ]);

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'course_id'  => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'rating'     => ['type' => 'int', 'constraint' => 1],
            'body'       => ['type' => 'text', 'null' => true],
            'created_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['course_id', 'user_id']);
        $this->forge->addForeignKey('course_id', 'courses', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('course_reviews');

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'product_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'product_id']);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->createTable('product_wishlists');

        $this->forge->addField([
            'id'           => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'body'         => ['type' => 'text'],
            'is_anonymous' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'prayer_count' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'created_at'   => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('prayer_requests');

        $this->forge->addField([
            'id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'request_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'user_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['request_id', 'user_id']);
        $this->forge->addForeignKey('request_id', 'prayer_requests', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('prayer_request_prayers');

        $this->forge->addField([
            'id'        => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'   => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'read_date' => ['type' => 'date'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'read_date']);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('bible_reading_log');

        $this->forge->addField([
            'setting_key'   => ['type' => 'varchar', 'constraint' => 60],
            'setting_value' => ['type' => 'text', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('setting_key');
        $this->forge->createTable('app_settings');
    }

    public function down()
    {
        $this->forge->dropTable('app_settings', true);
        $this->forge->dropTable('bible_reading_log', true);
        $this->forge->dropTable('prayer_request_prayers', true);
        $this->forge->dropTable('prayer_requests', true);
        $this->forge->dropTable('product_wishlists', true);
        $this->forge->dropTable('course_reviews', true);
        $this->forge->dropColumn('enrollments', 'last_lesson_id');
    }
}
