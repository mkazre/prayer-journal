<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * LMS upgrade: course modules (sections), richer lesson types, per-course
 * settings, private lesson notes, a personal prayer journal, course
 * announcements, and multi-type quiz questions.
 */
class UpgradeLmsTables extends Migration
{
    public function up()
    {
        // Course modules / sections.
        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'course_id'  => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'title'      => ['type' => 'varchar', 'constraint' => 200],
            'summary'    => ['type' => 'text', 'null' => true],
            'position'   => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'datetime', 'null' => true],
            'updated_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('course_id');
        $this->forge->addForeignKey('course_id', 'courses', 'id', '', 'CASCADE');
        $this->forge->createTable('course_sections');

        $this->forge->addColumn('courses', [
            'subtitle'        => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'level'           => ['type' => 'varchar', 'constraint' => 20, 'default' => 'all'],
            'language'        => ['type' => 'varchar', 'constraint' => 40, 'default' => 'English'],
            'tags'            => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'intro_video_url' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'what_you_learn'  => ['type' => 'text', 'null' => true],
            'requirements'    => ['type' => 'text', 'null' => true],
            'audience'        => ['type' => 'text', 'null' => true],
            'author_name'     => ['type' => 'varchar', 'constraint' => 150, 'null' => true],
            'author_bio'      => ['type' => 'text', 'null' => true],
            'settings'        => ['type' => 'text', 'null' => true],
            'is_featured'     => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'published_at'    => ['type' => 'datetime', 'null' => true],
        ]);

        $this->forge->addColumn('lessons', [
            'section_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'type'          => ['type' => 'varchar', 'constraint' => 20, 'default' => 'text'],
            'summary'       => ['type' => 'varchar', 'constraint' => 500, 'null' => true],
            'duration_mins' => ['type' => 'int', 'constraint' => 5, 'null' => true],
            'is_preview'    => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'drip_days'     => ['type' => 'int', 'constraint' => 5, 'null' => true],
            'settings'      => ['type' => 'text', 'null' => true],
        ]);

        $this->forge->addColumn('quizzes', [
            'section_id'        => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'position'          => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'shuffle_questions' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'show_answers'      => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'status'            => ['type' => 'varchar', 'constraint' => 20, 'default' => 'published'],
        ]);
        $this->forge->addColumn('quiz_questions', [
            'explanation'   => ['type' => 'text', 'null' => true],
            'correct_text'  => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addColumn('quiz_answers', [
            'response' => ['type' => 'text', 'null' => true],
        ]);

        $this->forge->addColumn('assignments', [
            'section_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'position'   => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'allow_file' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
        ]);

        $this->forge->addColumn('enrollments', [
            'source'           => ['type' => 'varchar', 'constraint' => 20, 'default' => 'free'],
            'expires_at'       => ['type' => 'datetime', 'null' => true],
            'last_activity_at' => ['type' => 'datetime', 'null' => true],
        ]);

        // Private per-lesson notes.
        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'lesson_id'  => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'body'       => ['type' => 'text', 'null' => true],
            'created_at' => ['type' => 'datetime', 'null' => true],
            'updated_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'lesson_id']);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('lesson_id', 'lessons', 'id', '', 'CASCADE');
        $this->forge->createTable('lesson_notes');

        // Personal prayer journal: prayer points, meditations, gratitude.
        $this->forge->addField([
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'course_id'   => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'lesson_id'   => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'kind'        => ['type' => 'varchar', 'constraint' => 20, 'default' => 'note'],
            'title'       => ['type' => 'varchar', 'constraint' => 200, 'null' => true],
            'body'        => ['type' => 'text'],
            'scripture'   => ['type' => 'varchar', 'constraint' => 150, 'null' => true],
            'is_answered' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'answered_at' => ['type' => 'datetime', 'null' => true],
            'answer_note' => ['type' => 'text', 'null' => true],
            'entry_date'  => ['type' => 'date', 'null' => true],
            'created_at'  => ['type' => 'datetime', 'null' => true],
            'updated_at'  => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'kind']);
        $this->forge->addKey('lesson_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('course_id', 'courses', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('lesson_id', 'lessons', 'id', '', 'SET NULL');
        $this->forge->createTable('journal_entries');

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'course_id'  => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'title'      => ['type' => 'varchar', 'constraint' => 200],
            'body'       => ['type' => 'text'],
            'created_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('course_id');
        $this->forge->addForeignKey('course_id', 'courses', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('course_announcements');

        // Profile + display preferences (theme, reading font, text size...).
        $this->forge->addField([
            'user_id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'display_name' => ['type' => 'varchar', 'constraint' => 120, 'null' => true],
            'bio'          => ['type' => 'text', 'null' => true],
            'avatar_path'  => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'preferences'  => ['type' => 'text', 'null' => true],
            'updated_at'   => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('user_profiles');
    }

    public function down()
    {
        $this->forge->dropTable('user_profiles', true);
        $this->forge->dropTable('course_announcements', true);
        $this->forge->dropTable('journal_entries', true);
        $this->forge->dropTable('lesson_notes', true);
        $this->forge->dropColumn('enrollments', ['source', 'expires_at', 'last_activity_at']);
        $this->forge->dropColumn('assignments', ['section_id', 'position', 'allow_file']);
        $this->forge->dropColumn('quiz_answers', ['response']);
        $this->forge->dropColumn('quiz_questions', ['explanation', 'correct_text']);
        $this->forge->dropColumn('quizzes', ['section_id', 'position', 'shuffle_questions', 'show_answers', 'status']);
        $this->forge->dropColumn('lessons', ['section_id', 'type', 'summary', 'duration_mins', 'is_preview', 'drip_days', 'settings']);
        $this->forge->dropColumn('courses', [
            'subtitle', 'level', 'language', 'tags', 'intro_video_url', 'what_you_learn', 'requirements',
            'audience', 'author_name', 'author_bio', 'settings', 'is_featured', 'published_at',
        ]);
        $this->forge->dropTable('course_sections', true);
    }
}
