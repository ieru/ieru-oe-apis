<?php

// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class Technical extends Model
{
    protected $table='technicals';
    protected $primaryKey = 'technical_id';

    public function requirement()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\Requirement');
    }

    public function technicalsformat()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\TechnicalsFormat');
    }

    public function technicalsinstallationremark()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\TechnicalsInstallationremark');
    }

    public function technicalslocation()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\TechnicalsLocation');
    }

    public function technicalsotherplatformrequirement()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\TechnicalsOtherplatformrequirement');
    }

    public function lom()
    {
        return $this->hasOne('\Ieru\Ieruapis\Import\Models\Lom');
    }

}