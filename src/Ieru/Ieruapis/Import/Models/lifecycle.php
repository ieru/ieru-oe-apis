<?php

// Model:'Lifecycle' - Database Table: 'lifecycles'

Class Lifecycle extends Eloquent
{

    protected $table='lifecycles';

    public function contributes()
    {
        return $this->hasMany('Contributes');
    }

    public function loms()
    {
        return $this->hasOne('Loms');
    }

}