<?php

// Model:'ClassificationsKeyword' - Database Table: 'classifications_keywords'

Class ClassificationsKeyword extends Eloquent
{

    protected $table='classifications_keywords';

    public function classifications()
    {
        return $this->belongsTo('Classifications');
    }

}