<?php
/** 
 * Xerox Translation adapter
 *
 * @package     Analytics API
 * @version     1.0 - 2013-04-18
 * 
 * @author      David Baños Expósito
 */

namespace Ieru\Ieruapis\Analytics\Providers\Translation; 

use \Ieru\Restengine\Engine\Exception\APIException;

define( 'XEROX_USERNAME', 'Put here Xerox username' );
define( 'XEROX_PASSWORD', 'Put here Xerox password' );

class XeroxService implements MultilingualTranslationAdapter
{
    /**
     * Checks if the service is active or not
     *
     * @return boolean
     */
    public function check_status () { return true; }

    /**
     * Tries to connect to the service
     *
     * @return boolean
     */
    public function connect () { return true; }

    /**
     * Closes the service
     *
     * @return void
     */
    public function close () {}

    /**
     * Sends a translation request to the service
     *
     * @param array     $params     Information needed to do the request
     * @return string   The translation
     */
	public function request ( &$params )
	{
		if ( !isset( $params['from'] ) )
			$params['from'] = isset( $params['from'] ) ? $params['from'] : 'en';

		// Connect to Xerox
		$url = 'https://services.open.xerox.com/Auth.svc/OAuth2';
		$post_data['username'] = XEROX_USERNAME;
		$post_data['password'] = XEROX_PASSWORD;

        $access_data = json_decode( $this->_make_curl_post( $url, $post_data ) );

        // Get server status
        $url = 'https://services.open.xerox.com/RestOp/TranslabOrganicLingua/GetServerStatus';
		$post_data = array();
        $headers = array(
            'Authorization: WRAP access_token="'.$access_data->access_token.'"',
        );
		$status = json_decode( $this->_make_curl_post( $url, $post_data, $headers ) );

		// Check to start the server
		$url = 'https://services.open.xerox.com/RestOp/TranslabOrganicLingua/StartServer';
		json_decode( $this->_make_curl_post( $url, $post_data, $headers ) );

		// Now the same with the other server
		$url = 'https://services.open.xerox.com/RestOp/TranslabOrganicLingua/GetTranslationModelServerStatus';

		// Start language trnaslation
		$url = 'https://services.open.xerox.com/RestOp/TranslabOrganicLingua/StartTranslationModelServer';
		$post_data['modelName']      = 'Organic.Lingua';
		$post_data['sourceLanguage'] = $params['from'].'-'.strtoupper( $params['from'] );
		$post_data['targetLanguage'] = $params['to'].'-'.strtoupper( $params['to'] );
		json_decode( $this->_make_curl_post( $url, $post_data, $headers ) );

        // Make the translation
        $url = 'https://services.open.xerox.com/RestOp/TranslabOrganicLingua/TranslateTextStringSync';
		$post_data['text']     = $params['text'];
		$post_data['encoding'] = 'UTF-8';
		$status = json_decode( $this->_make_curl_post( $url, $post_data, $headers ) );
		$a = 'n:TranslateTextStringSyncResponse';
		$b = 'n:StringResponse';
		$c = 'n:resultString';
		return @trim( $status->$a->$b->$c );
	}

	private function _make_curl_post ( $url, $post_data, $headers = array() )
	{
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 20 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $post_data ) );
        if ( $headers )
        	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        $data = curl_exec( $ch );
        curl_close( $ch );

        return $data;
	}
}