<?php
/** 
 * Moses Translation adapter
 *
 * @package     Analytics API
 * @version     1.0 - 2014-02-10
 * 
 * @author      David Baños Expósito
 */

namespace Ieru\Ieruapis\Analytics\Providers\Translation; 

use \Ieru\Restengine\Engine\Exception\APIException;

class MosesService implements MultilingualTranslationAdapter
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
        $translatedStr = '';
        
        try 
        {
            // Set the translation parameters
            $fromLanguage = !isset( $params['from'] ) ? 'en' : $params['from'];
            $toLanguage   = $params['to'];
            $inputStr     = $params['text'];
        
            // Make the request
            $parameters   = 'text='.urlencode($inputStr).'&langTo='.$toLanguage.'&langFrom='.$fromLanguage.'&JSONoutput=true';
            $translateUrl = 'http://research.celi.it:8080/MosesServerProxy/rest/MosesService?'.$parameters;
            $curlResponse = json_decode( $this->_curl_request( $translateUrl ) );

            if ( $curlResponse->translation[0] == 'true' )
                $translatedStr = $curlResponse->translation[1];
        }
        catch ( APIException $e )
        {
            $e->to_json();
        }

        return $translatedStr;
	}

    /**
     * Sends a translation request to the service
     *
     * @param   array     $params     Information needed to do the request
     * @return  string   The translation
     */
    public function languages ( &$params )
    {
        try 
        {
            // Make the request
            $translateUrl = 'http://research.celi.it:8080/MosesServerProxy/rest/MosesService/SupportedLanguages?JSONoutput=true';
            $curlResponse = $this->_curl_request( $translateUrl, $this->_get_auth_header() );
        }
        catch ( APIException $e )
        {
            $e->to_json();
        }

        return '[NYI]';
    }

    /**
     * Create and execute the HTTP CURL request.
     *
     * @param string $url        HTTP Url.
     * @param string $authHeader Authorization Header string.
     * @param string $postData   Data to post.
     * @return string.
     */
    private function _curl_request ( $url )
    {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        $curlResponse = curl_exec( $ch );
        $curlErrno = curl_errno( $ch );
        if ( $curlErrno )
        {
            $curlError = curl_error( $ch );
            throw new APIException( $curlError );
        }
        curl_close( $ch );
        return $curlResponse;
    }
}