<?php

// Model:'Requirement' - Database Table: 'requirements'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class Resource extends Model
{

    protected $table='resources';
    protected $primaryKey='resource_id';

    public function relation()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Relation');
    }

    public function identifier()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\Identifier');
    }

}