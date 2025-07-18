<?php

namespace App\Models;

use CodeIgniter\Model;

class TeamModel extends Model
{
    protected $table = 'teams';
    protected $primaryKey = 'id';
    protected $allowedFields = ['tournament_id', 'name', 'tag', 'user_id', 'owner_id'];
    protected $useTimestamps = true;
}