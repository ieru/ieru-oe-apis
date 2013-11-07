<?php

// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Analytics\Models;

use \Illuminate\Database\Eloquent\Model;

Class Rating extends Model
{
    protected $table = 'ratings';
    protected $primaryKey = 'rating_id';
    protected $connection = 'analytics';
}