<?php

// Model:'Lom' - Database Table: 'loms'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class Contribute extends Model
{

    protected $table = 'contributes';
    protected $primaryKey = 'contribute_id';

    public function contributesentity()
    {
        return $this->hasMany('\Ieru\Ieruapis\Import\Models\ContributesEntity');
    }

    public function lom()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Lom');
    }

    public function metametadata()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Metametadata');
    }

    public function lifecycle()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Lifecycle');
    }

}