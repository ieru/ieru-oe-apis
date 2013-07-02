<?php

// Model:'Requirement' - Database Table: 'requirements'
// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class Orcomposite extends Model
{

    protected $table='orcomposites';
    protected $primaryKey='orcomposite_id';

    public function requirement()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Requirement');
    }
}