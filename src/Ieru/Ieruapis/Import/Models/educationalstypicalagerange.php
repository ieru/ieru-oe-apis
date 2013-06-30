<?php

// Model:'EducationalsTypicalagerange' - Database Table: 'educationals_typicalageranges'

Class EducationalsTypicalagerange extends Eloquent
{

    protected $table='educationals_typicalageranges';

    public function educationals()
    {
        return $this->belongsTo('Educationals');
    }

}