<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLmsCoreTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'varchar', 'constraint' => 150],
            'slug'        => ['type' => 'varchar', 'constraint' => 170],
            'description' => ['type' => 'text', 'null' => true],
            'created_at'  => ['type' => 'datetime', 'null' => true],
            'updated_at'  => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('course_categories');

        $this->forge->addField([
            'id'               => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'category_id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'title'            => ['type' => 'varchar', 'constraint' => 200],
            'slug'             => ['type' => 'varchar', 'constraint' => 220],
            'summary'          => ['type' => 'varchar', 'constraint' => 500, 'null' => true],
            'description'      => ['type' => 'text', 'null' => true],
            'thumbnail_path'   => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'price_cents'      => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'currency'         => ['type' => 'varchar', 'constraint' => 3, 'default' => 'USD'],
            'status'           => ['type' => 'varchar', 'constraint' => 20, 'default' => 'draft'],
            'created_by'       => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'created_at'       => ['type' => 'datetime', 'null' => true],
            'updated_at'       => ['type' => 'datetime', 'null' => true],
            'deleted_at'       => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('category_id');
        $this->forge->addForeignKey('category_id', 'course_categories', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('created_by', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('courses');

        // Multi-instructor support: an instructor row for the course owner is
        // added automatically; extra rows let more than one instructor co-teach.
        $this->forge->addField([
            'id'           => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'course_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'user_id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'role'         => ['type' => 'varchar', 'constraint' => 20, 'default' => 'instructor'],
            'created_at'   => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['course_id', 'user_id']);
        $this->forge->addForeignKey('course_id', 'courses', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('course_instructors');

        $this->forge->addField([
            'id'             => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'course_id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'title'          => ['type' => 'varchar', 'constraint' => 200],
            'content'        => ['type' => 'text', 'null' => true],
            'video_url'      => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'attachment_path'=> ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'bible_reference'=> ['type' => 'varchar', 'constraint' => 100, 'null' => true],
            'position'       => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'available_at'   => ['type' => 'datetime', 'null' => true],
            'status'         => ['type' => 'varchar', 'constraint' => 20, 'default' => 'draft'],
            'created_at'     => ['type' => 'datetime', 'null' => true],
            'updated_at'     => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('course_id');
        $this->forge->addForeignKey('course_id', 'courses', 'id', '', 'CASCADE');
        $this->forge->createTable('lessons');

        $this->forge->addField([
            'id'               => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'course_id'        => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'user_id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'status'           => ['type' => 'varchar', 'constraint' => 20, 'default' => 'active'],
            'progress_percent' => ['type' => 'int', 'constraint' => 3, 'default' => 0],
            'enrolled_at'      => ['type' => 'datetime', 'null' => true],
            'completed_at'     => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['course_id', 'user_id']);
        $this->forge->addForeignKey('course_id', 'courses', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('enrollments');

        $this->forge->addField([
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'enrollment_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'lesson_id'   => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'completed_at'=> ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['enrollment_id', 'lesson_id']);
        $this->forge->addForeignKey('enrollment_id', 'enrollments', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('lesson_id', 'lessons', 'id', '', 'CASCADE');
        $this->forge->createTable('lesson_completions');
    }

    public function down()
    {
        $this->forge->dropTable('lesson_completions', true);
        $this->forge->dropTable('enrollments', true);
        $this->forge->dropTable('lessons', true);
        $this->forge->dropTable('course_instructors', true);
        $this->forge->dropTable('courses', true);
        $this->forge->dropTable('course_categories', true);
    }
}
