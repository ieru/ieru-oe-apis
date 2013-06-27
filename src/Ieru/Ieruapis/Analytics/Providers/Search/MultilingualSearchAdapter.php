<?php
/** 
 * Multilingual search adapter
 *
 * @package     Analytics API
 * @version     1.1 - 2013-04-04 | 1.0 - 2013-03-15
 * 
 * @author      David Baños Expósito
 */

namespace Ieru\Ieruapis\Analytics\Providers\Search;

interface MultilingualSearchAdapter
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
    public function request ( &$data, &$request_uri, &$config = null );
}