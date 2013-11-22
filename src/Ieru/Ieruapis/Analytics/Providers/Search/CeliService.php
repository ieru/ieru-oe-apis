<?php
/** 
 * Multilingual search adapter
 *
 * @package     Analytics API
 * @version     1.0 - 2013-03-15
 * 
 * @author      David Baños Expósito
 */

namespace Ieru\Ieruapis\Analytics\Providers\Search;

use \Ieru\Restengine\Engine\Exception\APIException;

class CeliService implements MultilingualSearchAdapter
{
    private $_lang;

    public function request ( &$data, &$request_uri, &$config = null )
    {
        $filters = $this->_format_filters( $data, $config );

        // Experimental search options
        $data['semanticexpansion'] = $data['semanticexpansion'] ? $data['semanticexpansion'] : 'false';
        $data['prfexpansion'] = $data['prfexpansion'] ? $data['prfexpansion'] : 'false';
        $data['monolingual'] = $data['monolingual'] ? $data['monolingual'] : 'false';
        $data['guesslanguage'] = isset( $data['guesslanguage'] ) ? $data['guesslanguage'] : '';

        # Format the request URI, check documentation for more details. This will return a json array.
        $data['filter'] = ( isset( $data['filter'] ) ) ? $data['filter'] : '*';
        $request_uri = 'http://research.celi.it:8080/OrganicLinguaSolr/select?indent=on&version=2.2'.
                       '&q='.urlencode( html_entity_decode( $data['text'] ) ).'&start='.$data['offset'].
                       '&rows='.$data['limit'].
                       '&semanticExpansion='.$data['semanticexpansion'].
                       '&prfExpansion='.$data['prfexpansion'].
                       '&monolingual='.$data['monolingual'].
                       '&language='.$data['guesslanguage'].
                       '&fl=general_identifier%2Cscore&wt=json&explainOther=&hl.fl='.
                       '&facet=true&facet.field=educationalContext&facet.field=language&facet.field=technicalFormat'.
                       '&facet.field=collection&facet.field=educationalRole&facet.field=educationalLearningResourceType'.
                       '&fq='.urlencode( str_replace('@', '/', $filters ) );
        $response = $this->_curl_get_data( $request_uri );
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
    public function close () {}

    /**
     * Formats the output according to the API document
     *
     * @param array     $data       The data to format into a new array
     * @return array The data formatted according to the API document
     */
    public function & format ( $data )
    {
        $response = array();
        $response['success'] = true;
        $response['message'] = 'Resources retrieved.';
        $response['data']['total_records'] = $data->response->numFound;
        $response['data']['retrieved_records'] = count( $data->response->docs );

        foreach ( $data->response->docs as $doc )
        {
            $response['data']['resources'][] = array( 'resource'=>$doc->general_identifier[0] );
        }
        $response['data']['facets'] = array();

        foreach ( $data->facet_counts->facet_fields as $facet_name=>$facet )
        {
            $arr = array();
            $arr['facet'] = $facet_name;
            
            foreach ( $facet as $key=>$filter )
            {
                $resource =& $data->facet_counts->facet_fields->$facet_name;
                if ( $key % 2 == 0 AND $resource[$key+1] > 0 )
                    $arr['filters'][] = array( 'filter'=>$filter, 'resources'=>$resource[$key+1] );
            }
            $response['data']['facets'][] = $arr;
        }

        return $response;
    }

    /**
     * Format the filters for making a proper request
     *
     * @return void
     */
    private function _format_filters ( &$data, &$config )
    {
        $this->_lang = $config->get_iso_lang();

        $filters = '*:*';

        if ( isset( $data['filter'] ) )
        {
            $f = array();
            foreach ( $data['filter'] as &$filter )
            {
                if ( !empty( $filter ) )
                {
                    if ( $filter['clave'] == 'language' )
                    {                       
                        $f[] = $filter['clave'].':"'.array_search( trim( $filter['valor'] ), $this->_lang ).'"';
                    }
                    else
                    {
                        $orx = array();
                        $or = explode( '|', $filter['valor'] );
                        if ( count( $or ) > 1 )
                        {
                            foreach ( $or as $o )
                            {
                                $orx[] = $filter['clave'].':"'.trim( $o ).'"';
                            }
                            $or_text = '('.implode( ' OR ', $orx ).')';
                            $f[] = $or_text;
                        }
                        else
                        {
                            $f[] = $filter['clave'].':"'.trim( $filter['valor'] ).'"';
                        }
                    }
                    
                }
            }
            $filters = implode( ' AND ', $f );
        }
        //echo $filters; die();
        return $filters;
    }

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
}