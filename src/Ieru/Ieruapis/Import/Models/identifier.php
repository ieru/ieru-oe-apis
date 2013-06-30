<?php

// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class Identifier extends Model
{

    protected $table = 'identifiers';
    protected $primaryKey = 'identifier_id';

    public function lom()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Lom');
    }

    public function resource()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Resource');
    }

    public function general()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\General');
    }

    public function metametadata()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Metametadata');
    }

}