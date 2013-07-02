<?php

// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class Lom extends Model
{

    protected $table='loms';
    protected $primaryKey = 'lom_id';

    public function annotation()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\Annotation');
    }

    public function classification()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\Classification');
    }

    public function contribute()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\Contribute');
    }

    public function educational()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\Educational');
    }

    public function general()
    {
        return $this->hasOne('\Ieru\Ieruapis\Import\Models\General');
    }

    public function identifier()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\Identifier');
    }

    public function lifecycle()
    {
        return $this->hasOne('\Ieru\Ieruapis\Import\Models\Lifecycle');
    }

    public function metametadata()
    {
        return $this->hasOne('\Ieru\Ieruapis\Import\Models\Metametadata');
    }

    public function relation()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\Relation');
    }

    public function right()
    {
        return $this->hasOne('\Ieru\Ieruapis\Import\Models\Right');
    }

    public function technical()
    {
        return $this->hasOne('\Ieru\Ieruapis\Import\Models\Technical');
    }

}