<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTeamsMakeTournamentIdNullable extends Migration
{
    public function up()
    {
        $fields = [
            'tournament_id' => [
                'name' => 'tournament_id',
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
        ];
        $this->forge->modifyColumn('teams', $fields);
    }

    public function down()
    {
        $fields = [
            'tournament_id' => [
                'name' => 'tournament_id',
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
        ];
        $this->forge->modifyColumn('teams', $fields);
    }
}
