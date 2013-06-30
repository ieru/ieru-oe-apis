<?php

// Model:'EducationalsLanguage' - Database Table: 'educationals_languages'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class EducationalsLanguage extends Model
{

    protected $table='educationals_languages';
    protected $primaryKey = 'educationals_language_id';

    public function educational()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Educational');
    }

}