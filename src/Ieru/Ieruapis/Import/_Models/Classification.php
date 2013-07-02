<?php

// Model:'Requirement' - Database Table: 'requirements'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class Classification extends Model
{

    protected $table='classifications';
    protected $primaryKey='classification_id';

    public function classificationskeyword()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\ClassificationsKeyword');
    }

    public function taxonpath()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\Taxonpath');
    }

    public function lom()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Lom');
    }
}