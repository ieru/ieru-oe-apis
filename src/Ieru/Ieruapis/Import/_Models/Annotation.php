<?php

// Model:'Requirement' - Database Table: 'requirements'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class Annotation extends Model
{

    protected $table='annotations';
    protected $primaryKey='annotation_id';

    public function lom()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Annotation');
    }

}