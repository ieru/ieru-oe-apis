<?php
/** 
 * Cache service
 *
 * @package     Cache API
 * @version     1.0 - 2013-08-30
 * 
 * @author      David Baños Expósito
 */

namespace Ieru\Ieruapis\Analytics\Providers\Cache;

interface CacheAdapter
{
	/**
	 * Checks if the service is active or not
	 *
	 * @return boolean
	 */
    public function check_status ();

    /**
     * Tries to connect to the service
     *
     * @return boolean
     */
    public function connect ();

    /**
     * Closes the service
     *
     * @return void
     */
    public function close ();

    /**
     * Sends a translation request to the service
     *
     * @param array 	$data 			Information needed to do the request
     * @return string 	The translation
     */
    public function request ( &$data, &$config = null );
}