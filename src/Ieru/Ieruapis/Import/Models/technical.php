<?php

// Model:'Technical' - Database Table: 'technicals'

Class Technical extends Eloquent
{

    protected $table='technicals';

    public function requirements()
    {
        return $this->hasMany('Requirements');
    }

    public function technicalsformats()
    {
        return $this->hasMany('TechnicalsFormats');
    }

    public function technicalsinstallationremarks()
    {
        return $this->hasMany('TechnicalsInstallationremarks');
    }

    public function technicalslocations()
    {
        return $this->hasMany('TechnicalsLocations');
    }

    public function technicalsotherplatformrequirements()
    {
        return $this->hasMany('TechnicalsOtherplatformrequirements');
    }

    public function loms()
    {
        return $this->hasOne('Loms');
    }

}