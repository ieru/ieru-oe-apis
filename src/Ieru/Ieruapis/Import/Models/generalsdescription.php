<?php

// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class GeneralsDescription extends Model
{
    protected $table = 'generals_descriptions';
    protected $primaryKey = 'generals_description_id';

    public function general()
    {
    	return $this->belongsTo('\Ieru\Ieruapis\Import\Models\General');
    }

}