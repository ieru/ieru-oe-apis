<?php

// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class GeneralsTitle extends Model
{
    protected $table = 'generals_titles';
    protected $primaryKey = 'generals_title_id';

    public function general()
    {
    	return $this->belongsTo('\Ieru\Ieruapis\Import\Models\General');
    }

}