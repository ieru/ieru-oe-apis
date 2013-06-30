<?php

// Model:'Taxonpath' - Database Table: 'taxonpaths'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class Taxon extends Model
{

    protected $table='taxons';
    protected $primaryKey = 'taxon_id';

    public function taxonpath()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Taxonpath');
    }
}