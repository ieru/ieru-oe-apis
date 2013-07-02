<?php

// Model:'Lifecycle' - Database Table: 'lifecycles'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class Lifecycle extends Model
{

    protected $table='lifecycles';
    protected $primaryKey = 'lifecycle_id';

    public function contribute()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\Contribute');
    }

    public function lom()
    {
        return $this->hasOne('\Ieru\Ieruapis\Import\Models\Lom');
    }

}