<?php

// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class GeneralsKeywordsText extends Model
{
    protected $table = 'generals_keywords_texts';
    protected $primaryKey = 'generals_keywords_text_id';

    public function generalkeyword()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\GeneralsKeyword');
    }
}