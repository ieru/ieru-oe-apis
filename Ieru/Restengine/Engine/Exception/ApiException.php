<?php
/**
 * Throws exceptions in json format.
 *
 * @package     Organic API
 * @version     1.1 - 2013-03-30
 * 
 * @author      David Baños Expósito
 */

namespace Ieru\Restengine\Engine\Exception;

class APIException extends \Exception
{
	private $_data;

	public function __construct ( $message, $data = null, $code = 0, Exception $previous = null )
	{
		if ( is_array( $data ) )
			$this->_data = $data;

		parent::__construct( $message, $code, $previous );
	}

	public function to_json ()
	{
        if ( $_SERVER['REQUEST_METHOD'] != 'HEAD' )
        	header( 'content-type: application/json; charset=utf-8' );

        // Json parameters, according to IERU API specification
		$json['success'] = false;
		$json['message'] = $this->message;
		if ( $this->_data )
			$json['data'] = $this->_data;
		die( json_encode( $json ) );
	}
}