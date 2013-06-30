<?php

// Model:'Right' - Database Table: 'rights'

Class Right extends Eloquent
{

    protected $table='rights';

    public function loms()
    {
        return $this->hasOne('Loms');
    }

}