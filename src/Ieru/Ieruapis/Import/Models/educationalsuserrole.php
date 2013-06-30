<?php

// Model:'EducationalsUserrole' - Database Table: 'educationals_userroles'

Class EducationalsUserrole extends Eloquent
{

    protected $table='educationals_userroles';

    public function educationals()
    {
        return $this->belongsTo('Educationals');
    }

}