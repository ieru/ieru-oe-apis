<?php

// Model:'Requirement' - Database Table: 'requirements'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class Relation extends Model
{

    protected $table='relations';
    protected $primaryKey='relation_id';

    public function lom()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Lom');
    }

    public function resource()
    {
        return $this->hasOne('\Ieru\Ieruapis\Import\Models\Resource');
    }

}