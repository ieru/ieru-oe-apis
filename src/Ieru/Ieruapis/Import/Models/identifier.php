<?php

// Model:'Identifier' - Database Table: 'identifiers'

Class Identifier extends Eloquent
{

    protected $table='identifiers';

    public function loms()
    {
        return $this->belongsTo('Loms');
    }

    public function resources()
    {
        return $this->belongsTo('Resources');
    }

    public function generals()
    {
        return $this->belongsTo('Generals');
    }

    public function metametadatas()
    {
        return $this->belongsTo('Metametadatas');
    }

}