<?php

// Model:'Educational' - Database Table: 'educationals'

Class Educational extends Eloquent
{

    protected $table='educationals';

    public function educationalscontexts()
    {
        return $this->hasMany('EducationalsContexts');
    }

    public function educationalsdescriptions()
    {
        return $this->hasMany('EducationalsDescriptions');
    }

    public function educationalslanguages()
    {
        return $this->hasMany('EducationalsLanguages');
    }

    public function educationalstypes()
    {
        return $this->hasMany('EducationalsTypes');
    }

    public function educationalstypicalageranges()
    {
        return $this->hasMany('EducationalsTypicalageranges');
    }

    public function educationalsuserroles()
    {
        return $this->hasMany('EducationalsUserroles');
    }

    public function loms()
    {
        return $this->belongsTo('Loms');
    }

}