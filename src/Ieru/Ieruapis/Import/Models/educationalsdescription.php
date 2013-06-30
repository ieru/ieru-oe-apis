<?php

// Model:'EducationalsDescription' - Database Table: 'educationals_descriptions'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class EducationalsDescription extends Model
{

    protected $table='educationals_descriptions';
    protected $primaryKey = 'educationals_description_id';

    public function educational()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Educational');
    }

}