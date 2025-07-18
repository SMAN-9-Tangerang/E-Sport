<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTeamMembershipRequestsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id'     => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'team_id'     => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'status'      => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'accepted', 'rejected'],
                'default'    => 'pending',
            ],
            'created_at'  => [
                'type'       => 'DATETIME',
                'null'       => true,
            ],
            'updated_at'  => [
                'type'       => 'DATETIME',
                'null'       => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('team_id', 'teams', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('team_membership_requests');
    }

    public function down()
    {
        $this->forge->dropTable('team_membership_requests');
    }
}
