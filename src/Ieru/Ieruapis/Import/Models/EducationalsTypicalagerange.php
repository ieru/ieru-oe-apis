<?php

// Model:'EducationalsTypicalagerange' - Database Table: 'educationals_typicalageranges'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class EducationalsTypicalagerange extends Model
{

    protected $table='educationals_typicalageranges';
    protected $primaryKey = 'educationals_typicalagerange_id';

    public function educational()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Educational');
    }

}