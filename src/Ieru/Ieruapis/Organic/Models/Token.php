<?php

// Model:'Requirement' - Database Table: 'requirements'
namespace Ieru\Ieruapis\Organic\Models;

use \Illuminate\Database\Eloquent\Model;

Class Token extends Model
{
    protected $table='tokens';
    protected $primaryKey='token_id';

    public $timestamps = false;
}