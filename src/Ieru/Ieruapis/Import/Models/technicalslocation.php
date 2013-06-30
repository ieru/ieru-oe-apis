<?php

// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class TechnicalsLocation extends Model
{

    protected $table='technicals_locations';
    protected $primaryKey = 'technicals_location_id';

    public function technical()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Technical');
    }

}