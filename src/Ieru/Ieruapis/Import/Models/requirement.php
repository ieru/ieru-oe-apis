<?php

// Model:'Requirement' - Database Table: 'requirements'

Class Requirement extends Eloquent
{

    protected $table='requirements';

    public function orcomposites()
    {
        return $this->hasMany('Orcomposites');
    }

    public function technicals()
    {
        return $this->belongsTo('Technicals');
    }

}