<?php

// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class TechnicalsFormat extends Model
{

    protected $table='technicals_formats';
    protected $primaryKey = 'technicals_format_id';

    public function technical()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Technical');
    }

}