<?php

// Model:'GeneralsContribute' - Database Table: 'generals_contributes'

Class GeneralsContribute extends Eloquent
{

    protected $table='generals_contributes';

    public function generals()
    {
        return $this->belongsTo('Generals');
    }

}