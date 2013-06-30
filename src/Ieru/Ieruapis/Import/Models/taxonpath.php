<?php

// Model:'Taxonpath' - Database Table: 'taxonpaths'

Class Taxonpath extends Eloquent
{

    protected $table='taxonpaths';

    public function taxons()
    {
        return $this->hasMany('Taxons');
    }

    public function classifications()
    {
        return $this->belongsTo('Classifications');
    }

}