<?php

// Model:'EducationalsUserrole' - Database Table: 'educationals_userroles'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class EducationalsUserrole extends Model
{

    protected $table='educationals_userroles';
    protected $primaryKey = 'educationals_userrole_id';

    public function educational()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Educational');
    }

}