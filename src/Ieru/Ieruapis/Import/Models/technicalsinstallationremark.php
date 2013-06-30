<?php

// Model:'TechnicalsOtherplatformrequirement' - Database Table: 'technicals_otherplatformrequirements'
namespace Ieru\Ieruapis\Import\Models;

use \Illuminate\Database\Eloquent\Model;

Class TechnicalsInstallationremark extends Model
{

    protected $table='technicals_installationremarks';
    protected $primaryKey = 'technicals_installationremark_id';

    public function technicals()
    {
        return $this->belongsTo('\Ieru\Ieruapis\Import\Models\Technical');
    }

}
