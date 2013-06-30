<?php

// Model:'Metametadata' - Database Table: 'metametadatas'

Class Metametadata extends Eloquent
{

    protected $table='metametadatas';

    public function contributes()
    {
        return $this->hasMany('Contributes');
    }

    public function identifiers()
    {
        return $this->hasMany('Identifiers');
    }

    public function loms()
    {
        return $this->belongsTo('Loms');
    }

}