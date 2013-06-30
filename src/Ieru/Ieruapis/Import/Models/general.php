<?php

// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class General extends Model
{
    protected $table='generals';
    protected $primaryKey = 'general_id';

    public function generalscontribute()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\GeneralsContribute');
    }

    public function generalstitle()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\GeneralsTitle');
    }

    public function generalsdescription()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\GeneralsDescription');
    }

    public function generalskeyword()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\GeneralsKeyword');
    }

    public function generalscoverage()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\GeneralsCoverage');
    }

    public function generalslanguage()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\GeneralsLanguage');
    }

    public function identifier()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\Identifier');
    }

    public function lom()
    {
        return $this->hasOne('\Ieru\Ieruapis\Import\Models\Lom');
    }

}