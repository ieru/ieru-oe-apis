<?php

// Model:'Orcomposite' - Database Table: 'orcomposites'

Class Orcomposite extends Eloquent
{

    protected $table='orcomposites';

    public function requirements()
    {
        return $this->belongsTo('Requirements');
    }

}