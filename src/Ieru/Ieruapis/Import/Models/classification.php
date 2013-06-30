<?php

// Model:'Classification' - Database Table: 'classifications'

Class Classification extends Eloquent
{

    protected $table='classifications';

    public function classificationskeywords()
    {
        return $this->hasMany('ClassificationsKeywords');
    }

    public function taxonpaths()
    {
        return $this->hasMany('Taxonpaths');
    }

    public function loms()
    {
        return $this->belongsTo('Loms');
    }

}