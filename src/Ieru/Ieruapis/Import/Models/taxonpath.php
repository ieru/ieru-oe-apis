<?php

// Model:'Taxonpath' - Database Table: 'taxonpaths'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class Taxonpath extends Model
{

    protected $table='taxonpaths';
    protected $primaryKey = 'taxonpath_id';

    public function taxon()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\Taxon');
    }

    public function classification()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Classification');
    }

}