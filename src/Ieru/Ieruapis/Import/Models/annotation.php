<?php

// Model:'Annotation' - Database Table: 'annotations'

Class Annotation extends Eloquent
{

    protected $table='annotations';

    public function loms()
    {
        return $this->belongsTo('Loms');
    }

}