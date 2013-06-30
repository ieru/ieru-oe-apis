<?php

// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class GeneralsLanguage extends Model
{
    protected $table = 'generals_languages';
    protected $primaryKey = 'generals_language_id';

    public function general()
    {
    	return $this->belongsTo('\Ieru\Ieruapis\Import\Models\General');
    }

}