<?php

// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class GeneralsKeyword extends Model
{
    protected $table = 'generals_keywords';
    protected $primaryKey = 'generals_keyword_id';

    public function general()
    {
    	return $this->belongsTo('\Ieru\Ieruapis\Import\Models\General');
    }

    public function generalskeywordtext()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\GeneralsKeywordsText');
    }
}