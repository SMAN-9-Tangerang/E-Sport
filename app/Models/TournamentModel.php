<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentModel extends Model
{
    protected $table = 'tournaments';
    protected $primaryKey = 'id';
    protected $allowedFields = ['challonge_id','challonge_url', 'name', 'game_name', 'description', 'tournament_type', 'start_at'];
    protected $useTimestamps = true;
}