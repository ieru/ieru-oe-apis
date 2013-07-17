<?php

// Model:'Requirement' - Database Table: 'requirements'
namespace Ieru\Ieruapis\Organic\Models;

use \Illuminate\Database\Eloquent\Model;

Class User extends Model
{
    protected $table='users';
    protected $primaryKey='user_id';

	public $timestamps = false;
}