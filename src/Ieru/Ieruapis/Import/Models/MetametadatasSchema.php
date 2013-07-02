<?php

// Model:'ContributesEntity' - Database Table: 'contributes_entitys'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class MetametadatasSchema extends Model
{

    protected $table='metametadatas_schemas';
    protected $primaryKey = 'metametadatas_schema_id';

    public function metametada()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Metametadata');
    }

}