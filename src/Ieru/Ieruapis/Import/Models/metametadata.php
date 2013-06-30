<?php

// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class Metametadata extends Model
{
    protected $table='metametadatas';
    protected $primaryKey = 'metametadata_id';

    public function contribute()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\Contribute');
    }

    public function identifier()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\Identifier');
    }

    public function metametadatasschema()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\MetametadatasSchema');
    }

    public function lom()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Lom');
    }

}