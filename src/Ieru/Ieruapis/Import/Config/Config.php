<?php
/**
 * Import IEEE LOM resources from XML files to a MySQL database
 *
 * @package     Organic API
 * @version     1.0 - 2013-06-30
 * 
 * @author      David Baños Expósito
 */

namespace Ieru\Ieruapis\Import\Config;

class Config
{
	private $_routes;

	/**
	 * Returns the routes allowed in this API
	 *
	 * @return array
	 */
	public function & get_routes ()
	{
		if ( !$this->_routes )
		{
			$this->_routes['GET'][] = array( '/import',    'controller'=>'ImportAPI#import' );
			$this->_routes['GET'][] = array( '/get/:id',    'controller'=>'ImportAPI#get' );
		}
		return $this->_routes;
	}
}