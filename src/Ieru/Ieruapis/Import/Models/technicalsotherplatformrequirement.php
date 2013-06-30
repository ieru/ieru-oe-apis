<?php

// Model:'TechnicalsOtherplatformrequirement' - Database Table: 'technicals_otherplatformrequirements'

Class TechnicalsOtherplatformrequirement extends Eloquent
{

    protected $table='technicals_otherplatformrequirements';

    public function technicals()
    {
        return $this->belongsTo('Technicals');
    }

}