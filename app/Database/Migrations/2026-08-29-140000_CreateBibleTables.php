<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBibleTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'varchar', 'constraint' => 40],
            'abbrev'     => ['type' => 'varchar', 'constraint' => 10],
            'testament'  => ['type' => 'varchar', 'constraint' => 3],
            'position'   => ['type' => 'int', 'constraint' => 3],
            'chapter_count' => ['type' => 'int', 'constraint' => 5, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('abbrev');
        $this->forge->createTable('bible_books');

        $this->forge->addField([
            'id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'book_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'chapter' => ['type' => 'int', 'constraint' => 5],
            'verse'   => ['type' => 'int', 'constraint' => 5],
            'text'    => ['type' => 'text'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['book_id', 'chapter', 'verse']);
        $this->forge->addForeignKey('book_id', 'bible_books', 'id', '', 'CASCADE');
        $this->forge->createTable('bible_verses');

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'verse_id'   => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'verse_id']);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('verse_id', 'bible_verses', 'id', '', 'CASCADE');
        $this->forge->createTable('bible_bookmarks');

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'verse_id'   => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'color'      => ['type' => 'varchar', 'constraint' => 20, 'default' => 'gold'],
            'created_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'verse_id']);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('verse_id', 'bible_verses', 'id', '', 'CASCADE');
        $this->forge->createTable('bible_highlights');

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'verse_id'   => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'body'       => ['type' => 'text'],
            'created_at' => ['type' => 'datetime', 'null' => true],
            'updated_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'verse_id']);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('verse_id', 'bible_verses', 'id', '', 'CASCADE');
        $this->forge->createTable('bible_notes');
    }

    public function down()
    {
        $this->forge->dropTable('bible_notes', true);
        $this->forge->dropTable('bible_highlights', true);
        $this->forge->dropTable('bible_bookmarks', true);
        $this->forge->dropTable('bible_verses', true);
        $this->forge->dropTable('bible_books', true);
    }
}
