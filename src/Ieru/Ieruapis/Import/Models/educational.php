<?php

// Model:'Educational' - Database Table: 'educationals'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class Educational extends Model
{

    protected $table='educationals';
    protected $primaryKey = 'educational_id';

    public function educationalscontext()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\EducationalsContext');
    }

    public function educationalsdescription()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\EducationalsDescription');
    }

    public function educationalslanguage()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\EducationalsLanguage');
    }

    public function educationalstype()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\EducationalsType');
    }

    public function educationalstypicalagerange()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\EducationalsTypicalagerange');
    }

    public function educationalsuserrole()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\EducationalsUserrole');
    }

    public function lom()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Lom');
    }

}