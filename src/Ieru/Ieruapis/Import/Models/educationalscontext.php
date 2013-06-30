<?php

// Model:'EducationalsContext' - Database Table: 'educationals_contexts'

Class EducationalsContext extends Eloquent
{

    protected $table='educationals_contexts';

    public function educationals()
    {
        return $this->belongsTo('Educationals');
    }

}