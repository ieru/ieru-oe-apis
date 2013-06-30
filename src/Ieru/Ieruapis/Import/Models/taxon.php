<?php

// Model:'Taxon' - Database Table: 'taxons'

Class Taxon extends Eloquent
{

    protected $table='taxons';

    public function taxonpaths()
    {
        return $this->belongsTo('Taxonpaths');
    }

}