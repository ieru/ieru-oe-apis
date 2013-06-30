<?php

// Model:'TechnicalsOtherplatformrequirement' - Database Table: 'technicals_otherplatformrequirements'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class TechnicalsOtherplatformrequirement extends Model
{

    protected $table='technicals_otherplatformrequirements';
    protected $primaryKey = 'technicals_otherplatformrequirement_id';

    public function technicals()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Technical');
    }

}
