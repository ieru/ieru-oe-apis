<?php

// Model:'TechnicalsFormat' - Database Table: 'technicals_formats'

Class TechnicalsFormat extends Eloquent
{

    protected $table='technicals_formats';

    public function technicals()
    {
        return $this->belongsTo('Technicals');
    }

}