<?php

// Model:'TechnicalsLocation' - Database Table: 'technicals_locations'

Class TechnicalsLocation extends Eloquent
{

    protected $table='technicals_locations';

    public function technicals()
    {
        return $this->belongsTo('Technicals');
    }

}