<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Blog: archives (series such as "Prayerthon") holding posts, each post
 * with downloadable resources. Access is set per archive and can be
 * overridden per post.
 */
class CreateBlogTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'title'        => ['type' => 'varchar', 'constraint' => 150],
            'slug'         => ['type' => 'varchar', 'constraint' => 170],
            'description'  => ['type' => 'text', 'null' => true],
            'cover_path'   => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            // members = any signed-in user; customers = bought a product, a course or a membership.
            'access'       => ['type' => 'varchar', 'constraint' => 20, 'default' => 'members'],
            'status'       => ['type' => 'varchar', 'constraint' => 20, 'default' => 'draft'],
            'show_in_menu' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'position'     => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'created_at'   => ['type' => 'datetime', 'null' => true],
            'updated_at'   => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('blog_archives');

        $this->forge->addField([
            'id'             => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'archive_id'     => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'title'          => ['type' => 'varchar', 'constraint' => 200],
            'slug'           => ['type' => 'varchar', 'constraint' => 220],
            'excerpt'        => ['type' => 'varchar', 'constraint' => 500, 'null' => true],
            'body'           => ['type' => 'mediumtext', 'null' => true],
            'featured_image' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            // inherit = use the archive's setting.
            'access'         => ['type' => 'varchar', 'constraint' => 20, 'default' => 'inherit'],
            'status'         => ['type' => 'varchar', 'constraint' => 20, 'default' => 'draft'],
            'published_at'   => ['type' => 'datetime', 'null' => true],
            'created_by'     => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'datetime', 'null' => true],
            'updated_at'     => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['archive_id', 'slug']);
        $this->forge->addForeignKey('archive_id', 'blog_archives', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('blog_posts');

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'post_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'title'      => ['type' => 'varchar', 'constraint' => 200],
            'file_path'  => ['type' => 'varchar', 'constraint' => 255],
            'kind'       => ['type' => 'varchar', 'constraint' => 20], // pdf, image, video, audio, file
            'mime'       => ['type' => 'varchar', 'constraint' => 100, 'null' => true],
            'size_bytes' => ['type' => 'bigint', 'constraint' => 20, 'unsigned' => true, 'default' => 0],
            'position'   => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('post_id');
        $this->forge->addForeignKey('post_id', 'blog_posts', 'id', '', 'CASCADE');
        $this->forge->createTable('blog_resources');

        // Starting archive for the home-page Prayerthon block.
        $now = date('Y-m-d H:i:s');
        $this->db->table('blog_archives')->insert([
            'title'        => 'Prayerthon',
            'slug'         => 'prayerthon',
            'description'  => 'Embark on a guided prayer journey, with each kilometre inspiring focused prayer, reflection and a deeper connection with GOD.',
            'access'       => 'members',
            'status'       => 'published',
            'show_in_menu' => 1,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('blog_resources', true);
        $this->forge->dropTable('blog_posts', true);
        $this->forge->dropTable('blog_archives', true);
    }
}
