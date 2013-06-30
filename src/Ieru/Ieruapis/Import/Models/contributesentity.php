<?php

// Model:'ContributesEntity' - Database Table: 'contributes_entitys'

Class ContributesEntity extends Eloquent
{

    protected $table='contributes_entitys';

    public function contributes()
    {
        return $this->belongsTo('Contributes');
    }

}