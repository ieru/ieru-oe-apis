<?php

// Model:'Requirement' - Database Table: 'requirements'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class Right extends Model
{

    protected $table='rights';
    protected $primaryKey='right_id';

    public function lom()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Lom');
    }

}