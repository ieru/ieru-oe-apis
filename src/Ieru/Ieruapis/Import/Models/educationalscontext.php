<?php

// Model:'EducationalsContext' - Database Table: 'educationals_contexts'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class EducationalsContext extends Model
{

    protected $table='educationals_contexts';
    protected $primaryKey = 'educationals_context_id';

    public function educational()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Educational');
    }

}