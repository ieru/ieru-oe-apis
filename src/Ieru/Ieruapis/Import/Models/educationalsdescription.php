<?php

// Model:'EducationalsDescription' - Database Table: 'educationals_descriptions'

Class EducationalsDescription extends Eloquent
{

    protected $table='educationals_descriptions';

    public function educationals()
    {
        return $this->belongsTo('Educationals');
    }

}