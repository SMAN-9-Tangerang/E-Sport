<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTournamentsTeamsPlayersTables extends Migration
{
    public function up()
    {
        // Tabel Turnamen
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'challonge_id' => ['type' => 'BIGINT', 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'game_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'tournament_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'start_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tournaments');

        // Tabel Tim
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tournament_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'tag' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tournament_id', 'tournaments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('teams');
    }

    public function down()
    {
        $this->forge->dropTable('teams');
        $this->forge->dropTable('tournaments');
    }
}
