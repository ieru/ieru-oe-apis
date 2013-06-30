<?php

// Model:'EducationalsType' - Database Table: 'educationals_types'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class EducationalsType extends Model
{

    protected $table='educationals_types';
    protected $primaryKey = 'educationals_type_id';

    public function educational()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Educational');
    }

}