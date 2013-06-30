<?php

// Model:'Contribute' - Database Table: 'contributes'

Class Contribute extends Eloquent
{

    protected $table='contributes';

    public function contributesentitys()
    {
        return $this->hasMany('ContributesEntitys');
    }

    public function loms()
    {
        return $this->belongsTo('Loms');
    }

    public function metametadatas()
    {
        return $this->belongsTo('Metametadatas');
    }

    public function lifecycles()
    {
        return $this->belongsTo('Lifecycles');
    }

}