<?php

// Model:'TechnicalsInstallationremark' - Database Table: 'technicals_installationremarks'

Class TechnicalsInstallationremark extends Eloquent
{

    protected $table='technicals_installationremarks';

    public function technicals()
    {
        return $this->belongsTo('Technicals');
    }

}