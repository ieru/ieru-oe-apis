<?php

// Model:'Requirement' - Database Table: 'requirements'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class Requirement extends Model
{

    protected $table='requirements';
    protected $primaryKey='requirement_id';

    public function orcomposite()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\Orcomposite');
    }

    public function technical()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Technical');
    }

}