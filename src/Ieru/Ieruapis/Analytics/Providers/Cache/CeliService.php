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

use \Ieru\Restengine\Engine\Exception\APIException;

class CeliService implements CacheAdapter
{
    private $_lang;

    public function request ( &$data, &$config = null )
    {
        $request_uri = 'http://research.celi.it:8080/MT_caching_service/rest/cache/translation?text='.
                        urlencode( html_entity_decode( $data['text'] ) ).'&from='.$data['from'].'&to='.$data['to'].'&json_output=true';
        $response = $this->_curl_get_data( $request_uri );

        return $response;
    }

    public function add ( &$data )
    {
        $request_uri = 'http://research.celi.it:8080/MT_caching_service/rest/cache/translation';
        $response = $this->_curl_post_data( $request_uri, $data );

        return $response;
    }

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
    public function close () { return true; }

    /**
     * Connects with the remote services. Sets a timeout for connecting the service and a timeout for receiving the data.
     *
     * @param   String  $url        The url to retrieve, it must return a json.
     * @return  String  json returned by remote service
     */
    private function & _curl_get_data ( $url ) 
    {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 14 );
        $data = curl_exec( $ch );
        if ( curl_errno($ch) )
        {
            $e = new APIException( 'CLIR request timeout.' );
            $e->to_json();
        }
        curl_close( $ch );
        return $data;
    }

    private function _curl_post_data ( &$url, &$info )
    {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 14 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $info ) );
        $data = curl_exec( $ch );
        if ( curl_errno($ch) )
        {
            $e = new APIException( 'CLIR request timeout.' );
            $e->to_json();
        }
        curl_close( $ch );
        return $data;
    }
}