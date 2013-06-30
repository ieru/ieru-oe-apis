<?php

// Model:'ClassificationsKeyword' - Database Table: 'classifications_keywords'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class ClassificationsKeyword extends Model
{

    protected $table='classifications_keywords';
    protected $primaryKey='classifications_keyword_id';

    public function classification()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Classification');
    }

}