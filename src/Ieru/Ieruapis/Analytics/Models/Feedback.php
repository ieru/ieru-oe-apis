<?php

// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Analytics\Models;

use \Illuminate\Database\Eloquent\Model;

Class Feedback extends Model
{
    protected $table = 'feedbacks';
    protected $primaryKey = 'feedback_id';
    protected $connection = 'analytics';
}