<?php

// Model:'EducationalsType' - Database Table: 'educationals_types'

Class EducationalsType extends Eloquent
{

    protected $table='educationals_types';

    public function educationals()
    {
        return $this->belongsTo('Educationals');
    }

}