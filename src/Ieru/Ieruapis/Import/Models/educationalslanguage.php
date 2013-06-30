<?php

// Model:'EducationalsLanguage' - Database Table: 'educationals_languages'

Class EducationalsLanguage extends Eloquent
{

    protected $table='educationals_languages';

    public function educationals()
    {
        return $this->belongsTo('Educationals');
    }

}