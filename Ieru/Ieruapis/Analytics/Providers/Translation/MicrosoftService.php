<?php
/** 
 * Microsoft Translation adapter
 *
 * @package     Analytics API
 * @version     1.1 - 2013-04-04 | 1.0 - 2013-03-15
 * 
 * @author      David Baños Expósito
 */

namespace Ieru\Ieruapis\Analytics\Providers\Translation; 

use \Ieru\Restengine\Engine\Exception\APIException;

class MicrosoftService implements MultilingualTranslationAdapter
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
        try 
        {
            // Set the translation parameters
            $fromLanguage = !isset( $params['from'] ) ? 'en' : $params['from'];
            $toLanguage   = $params['to'];
            $inputStr     = $params['text'];
        
            // Make the request
            $parameters   = 'text='.urlencode($inputStr).'&to='.$toLanguage.'&from='.$fromLanguage;
            $translateUrl = 'http://api.microsofttranslator.com/v2/Http.svc/Translate?'.$parameters;
            $curlResponse = $this->_curl_request( $translateUrl, $this->_get_auth_header() );
        
            // Interprets a string of XML into an object.
            $xmlObj = simplexml_load_string( $curlResponse );
            foreach ( (array)$xmlObj[0] as $val )
            	$translatedStr = $val;

            // The errors of the Microsoft API are sent using HTML
            if ( isset( $translatedStr->h1 ) )
                throw new APIException( 'Microsoft Translate: '.$translatedStr->h1.'.', array( 'full_text'=>$curlResponse ) );
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
            $translateUrl = 'http://api.microsofttranslator.com/v2/Http.svc/GetLanguagesForTranslate';
            $curlResponse = $this->_curl_request( $translateUrl, $this->_get_auth_header() );
        
            // Interprets a string of XML into an object.
            $xmlObj = simplexml_load_string( $curlResponse );
            foreach ( (array)$xmlObj[0] as $val )
                $translatedStr = $val;

            // The errors of the Microsoft API are sent using HTML
            if ( isset( $translatedStr->h1 ) )
                throw new APIException( 'Microsoft Translate: '.$translatedStr->h1.'.', array( 'full_text'=>$curlResponse ) );
        }
        catch ( APIException $e )
        {
            $e->to_json();
        }

        return $translatedStr;
    }

    /**
     * Create and execute the HTTP CURL request.
     *
     * @param string $url        HTTP Url.
     * @param string $authHeader Authorization Header string.
     * @param string $postData   Data to post.
     * @return string.
     */
    private function _curl_request ( $url, $authHeader )
    {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( $authHeader, 'Content-Type: text/xml' ) );
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

    /**
     * Get the authentication header for making requests to Microsoft Translate.
     *
     * @return  string   The authentication header
     */
    private function _get_auth_header ()
    {
        // Client ID of the application.
        $data['clientID']     = 'ce294d5d-605a-4841-a8e3-b74971689e62';
        // Client Secret key of the application.
        $data['clientSecret'] = 'PjOAWsvyv7UFVwow98DBzK+2Y+n7Ym0czxNylk+uV3o=';
        // OAuth Url.
        $data['authUrl']      = 'https://datamarket.accesscontrol.windows.net/v2/OAuth2-13/';
        // Application Scope Url
        $data['scopeUrl']     = 'http://api.microsofttranslator.com';
        // Application grant type
        $data['grantType']    = 'client_credentials';

        // Get the Access token.
        return 'Authorization: Bearer '.$this->_get_tokens( $data['grantType'], $data['scopeUrl'], $data['clientID'], $data['clientSecret'], $data['authUrl'] );
    }

    /**
     * Get the access token.
     *
     * @param string $grantType    Grant type.
     * @param string $scopeUrl     Application Scope URL.
     * @param string $clientID     Application client ID.
     * @param string $clientSecret Application client ID.
     * @param string $authUrl      Oauth Url.
     * @return string.
     */
    private function _get_tokens ( $grantType, $scopeUrl, $clientID, $clientSecret, $authUrl )
    {
        try 
        {
            // Create request array
            $paramArr = array (
                 'grant_type'    => $grantType,
                 'scope'         => $scopeUrl,
                 'client_id'     => $clientID,
                 'client_secret' => $clientSecret
            );
            $paramArr = http_build_query($paramArr);

            // Make the request
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $authUrl );
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $paramArr );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            $objResponse = json_decode( curl_exec( $ch ) );
            $curlErrno = curl_errno( $ch );
            if ( $curlErrno )
            {
                $curlError = curl_error( $ch );
                throw new APIException( $curlError );
            }
            curl_close( $ch );

            // Decode the returned JSON string.
            if ( @$objResponse->access_token )
                return $objResponse->access_token;
            else
                throw new APIException( 'Error connecting to the Microsoft Service.' );
        }
        catch ( APIException $e ) 
        {
            $e->to_json();
        }
    }
}