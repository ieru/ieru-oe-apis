<?php

// Model:'ContributesEntity' - Database Table: 'contributes_entitys'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class ContributesEntity extends Model
{

    protected $table='contributes_entitys';
    protected $primaryKey = 'contributes_entity_id';

    public function contribute()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Contribute');
    }

}