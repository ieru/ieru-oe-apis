<?php

// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class GeneralsCoveragesText extends Model
{
    protected $table = 'generals_coverages_texts';
    protected $primaryKey = 'generals_coverages_text_id';

    public function generalcoverage()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\GeneralsCoverage');
    }
}