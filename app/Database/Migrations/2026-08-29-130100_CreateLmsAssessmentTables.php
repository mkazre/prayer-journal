<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLmsAssessmentTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'course_id'       => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'lesson_id'       => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'title'           => ['type' => 'varchar', 'constraint' => 200],
            'instructions'    => ['type' => 'text', 'null' => true],
            'pass_percent'    => ['type' => 'int', 'constraint' => 3, 'default' => 70],
            'time_limit_mins' => ['type' => 'int', 'constraint' => 5, 'null' => true],
            'max_attempts'    => ['type' => 'int', 'constraint' => 3, 'default' => 0],
            'created_at'      => ['type' => 'datetime', 'null' => true],
            'updated_at'      => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('course_id');
        $this->forge->addForeignKey('course_id', 'courses', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('lesson_id', 'lessons', 'id', '', 'SET NULL');
        $this->forge->createTable('quizzes');

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'quiz_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'question'   => ['type' => 'text'],
            'type'       => ['type' => 'varchar', 'constraint' => 20, 'default' => 'single'],
            'points'     => ['type' => 'int', 'constraint' => 5, 'default' => 1],
            'position'   => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('quiz_id');
        $this->forge->addForeignKey('quiz_id', 'quizzes', 'id', '', 'CASCADE');
        $this->forge->createTable('quiz_questions');

        $this->forge->addField([
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'question_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'option_text' => ['type' => 'varchar', 'constraint' => 500],
            'is_correct'  => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'position'    => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('question_id');
        $this->forge->addForeignKey('question_id', 'quiz_questions', 'id', '', 'CASCADE');
        $this->forge->createTable('quiz_options');

        $this->forge->addField([
            'id'           => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'quiz_id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'user_id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'score_percent'=> ['type' => 'int', 'constraint' => 3, 'null' => true],
            'passed'       => ['type' => 'tinyint', 'constraint' => 1, 'null' => true],
            'started_at'   => ['type' => 'datetime', 'null' => true],
            'submitted_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['quiz_id', 'user_id']);
        $this->forge->addForeignKey('quiz_id', 'quizzes', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('quiz_attempts');

        $this->forge->addField([
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'attempt_id'  => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'question_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'option_id'   => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'is_correct'  => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('attempt_id');
        $this->forge->addForeignKey('attempt_id', 'quiz_attempts', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('question_id', 'quiz_questions', 'id', '', 'CASCADE');
        $this->forge->createTable('quiz_answers');

        $this->forge->addField([
            'id'           => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'course_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'lesson_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'title'        => ['type' => 'varchar', 'constraint' => 200],
            'instructions' => ['type' => 'text', 'null' => true],
            'max_points'   => ['type' => 'int', 'constraint' => 5, 'default' => 100],
            'due_at'       => ['type' => 'datetime', 'null' => true],
            'created_at'   => ['type' => 'datetime', 'null' => true],
            'updated_at'   => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('course_id');
        $this->forge->addForeignKey('course_id', 'courses', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('lesson_id', 'lessons', 'id', '', 'SET NULL');
        $this->forge->createTable('assignments');

        $this->forge->addField([
            'id'             => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'assignment_id'  => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'user_id'        => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'content'        => ['type' => 'text', 'null' => true],
            'attachment_path'=> ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'status'         => ['type' => 'varchar', 'constraint' => 20, 'default' => 'submitted'],
            'points_awarded' => ['type' => 'int', 'constraint' => 5, 'null' => true],
            'feedback'       => ['type' => 'text', 'null' => true],
            'graded_by'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'graded_at'      => ['type' => 'datetime', 'null' => true],
            'submitted_at'   => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['assignment_id', 'user_id']);
        $this->forge->addForeignKey('assignment_id', 'assignments', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('graded_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('assignment_submissions');

        // Per-student, per-course rollup used for assessment/reporting.
        $this->forge->addField([
            'id'                => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'course_id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'user_id'           => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'quiz_avg_percent'  => ['type' => 'int', 'constraint' => 3, 'null' => true],
            'assignment_avg_percent' => ['type' => 'int', 'constraint' => 3, 'null' => true],
            'overall_percent'  => ['type' => 'int', 'constraint' => 3, 'null' => true],
            'updated_at'       => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['course_id', 'user_id']);
        $this->forge->addForeignKey('course_id', 'courses', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('gradebook_entries');
    }

    public function down()
    {
        $this->forge->dropTable('gradebook_entries', true);
        $this->forge->dropTable('assignment_submissions', true);
        $this->forge->dropTable('assignments', true);
        $this->forge->dropTable('quiz_answers', true);
        $this->forge->dropTable('quiz_attempts', true);
        $this->forge->dropTable('quiz_options', true);
        $this->forge->dropTable('quiz_questions', true);
        $this->forge->dropTable('quizzes', true);
    }
}
