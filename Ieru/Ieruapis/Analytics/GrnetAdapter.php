<?php
/** 
 * Handles API requests for Analytics Service.
 *
 * @package     Analytics API
 * @version     1.2 - 2013-04-04 | 1.1 - 2013-02-18 | 1.0 - 2012-10-15
 * 
 * @author      David Baños Expósito
 */

namespace Ieru\Ieruapis\Analytics; 

use \Ieru\Restengine\Engine\Exception\APIException;

class GrnetAdapter
{
    /**
     * Constructor
     */
    public function __construct ( $params, $config )
    {
        $this->_params = $params;
        $this->_config = $config;
    }
    
    /**
     * Fetches the rating associated with a resource
     *
     * @return array
     */
    public function get_rating ()
    {
        $entry = str_replace( '_', '/', $this->_params['entry'] );
        $entry = str_replace( '___', '://', $entry );
        $entry = str_replace( '@', '?', $entry );

        try
        {
            $clienteSOAP = new \SoapClient( 'http://62.217.124.135/cfmodule/server.php?wsdl', array( 'connection_timeout'=>4,'default_socket_timeout'=>2 ) );

            $func = 'Functionclass1.resourceRatingsMeanValue';
            $rating = $clienteSOAP->$func( $entry );

            $func = 'Functionclass1.resourceRatingsCount';
            $votes = $clienteSOAP->$func( $entry );
        }
        catch( \SoapFault $e )
        {
            $result = array( 'success'=>false, 'message'=>'Error connecting with ratings service.' );
        }
        
        $rating = ( @$rating->noitemfound || @$rating->norate ) ? 0 : $rating->totalRatingsMeanValue;
        $votes = ( @$votes->noitemfound || @$votes->norate ) ? 0 : $votes->resourceRatingsTotalNumber;

        $result = array( 'success'=>true, 'message'=>'Rating retrieved correctly', 'data'=>array( 'rating'=>round( $rating ), 'votes'=>$votes ) );

        return $result;
    }

    /**
     * Rate a resource with GRNET service
     */
    public function add_rating ()
    {
        $entry = str_replace( '_', '/', $this->_params['entry'] );
        $entry = str_replace( '___', '://', $entry );
        $entry = str_replace( '@', '?', $entry );

        $this->_params['id'] = 'Not Yet Implemented.';

        if ( $this->_params['usertoken'] == '' )
            return array( 'success'=>false, 'message'=>'Only registered users are allowed to rate resources.' );

        if ( !isset( $this->_params['rating'] ) OR $this->_params['rating'] == '' )
            return array( 'success'=>false, 'message'=>'You have to set a rating.' );

        // Try to connect database
        $this->_connect_oauth();

        // Query the database with the username and password given by the user
        $sql = 'SELECT users.* FROM users INNER JOIN tokens ON users.user_id = tokens.user_id WHERE tokens.token_chars = ? AND tokens.token_active = 1 LIMIT 1';
        $stmt = $this->_oauthdb->prepare( $sql );
        $stmt->execute( array( $this->_params['usertoken'] ) );

        if ( !$user = $stmt->fetch( \PDO::FETCH_ASSOC ) )
            return array( 'success'=>false, 'message'=>'Wrong usertoken.' );

        // Do the rating mambo
        try
        {
            $clienteSOAP = new \SoapClient( 'http://62.217.124.135/cfmodule/server.php?wsdl', array( 'connection_timeout'=>4,'default_socket_timeout'=>2 ) );
            $func = 'Functionclass1.addRating';
            for ( $i = 1; $i <= 6; $i++ )
            {
                $a[$i]['dimension'] = $i;
                $a[$i]['value']     = $this->_params['rating'];
            }

            // Array with the parameters for the SOAP request
            $p = array( 'apikey' => 'e827aa1ed7', 'user'=>$user['user_id'], 'resource'=>$entry, 'scheme' =>1, 'addratedim' => $a, 'overwrite' =>1 );

            // Do the SOAP request
            $rating = $clienteSOAP->$func( $p['apikey'], $p['user'], $p['resource'], $p['scheme'], $p['addratedim'], $p['overwrite'] );

            // Retrieve the ratings for the resource
            $values = $this->get_rating();
        }
        catch ( \SoapFault $e )
        {
            $result = array( 'success'=>false, 'message'=>'Could not add rating to the resource.' );
        }

        $result = array( 'success'=>true, 'message'=>'Rating added.', 'data'=>$values['data'] );

        return $result;
    }

    /**
     * Fetches the rating associated with a resource
     *
     * @return array
     */
    public function get_tags ()
    {
        $entry = str_replace( '_', '/', $this->_params['entry'] );
        $entry = str_replace( '___', '://', $entry );
        $entry = str_replace( '@', '?', $entry );

        try
        {
            $clienteSOAP = new \SoapClient( 'http://62.217.124.135/cfmodule/server.php?wsdl', array( 'connection_timeout'=>4,'default_socket_timeout'=>2 ) );

            $func = 'Functionclass1.resourceTaggings';
            $tags = $clienteSOAP->$func( $entry );
        }
        catch( \SoapFault $e )
        {
            $result = array( 'success'=>false, 'message'=>'Error connecting with ratings service.' );
        }

        if ( @$tags[0]->noitemfound OR @$tags->norate OR @$tags[0]->notag )
            $tags = array();

        $result = array( 'success'=>true, 'message'=>'Tags retrieved correctly', 'id'=>$this->_params['id'], 'data'=>$tags );

        return $result;
    }

    /**
     * @return array
     */
    public function get_history ()
    {
        $entry = str_replace( '_', '/', $this->_params['entry'] );
        $entry = str_replace( '___', '://', $entry );
        $entry = str_replace( '@', '?', $entry );

        try
        {
            $clienteSOAP = new \SoapClient( 'http://62.217.124.135/cfmodule/server.php?wsdl', array( 'connection_timeout'=>4,'default_socket_timeout'=>2 ) );
            $func = 'Functionclass1.resourceRatings';
            $rating = $clienteSOAP->$func( $entry );
        }
        catch( \SoapFault $e )
        {
            $result = array( 'success'=>false, 'message'=>'Error connecting with ratings service.' );
        }
        

        //$rating = ( @$rating->noitemfound || @$rating->norate ) ? 0 : $rating->totalRatingsMeanValue;
        if ( @$rating[0]->noitemfound || @$rating->norate )
            $rating = array();

        $result = array( 'success'=>true, 'message'=>'Rating history retrieved correctly', 'id'=>$this->_params['id'], 'data'=>$rating );

        return $result;
    }

    /**
     * @return array
     */
    public function get_review_history ()
    {
        $entry = str_replace( '_', '/', $this->_params['entry'] );
        $entry = str_replace( '___', '://', $entry );
        $entry = str_replace( '@', '?', $entry );

        try
        {
            $clienteSOAP = new \SoapClient( 'http://62.217.124.135/cfmodule/server.php?wsdl', array( 'connection_timeout'=>4,'default_socket_timeout'=>2 ) );
            $func = 'Functionclass1.resourceReviewings';
            $rating = $clienteSOAP->$func( $entry );
        }
        catch( \SoapFault $e )
        {
            $result = array( 'success'=>false, 'message'=>'Error connecting with ratings service.' );
        }

        //$rating = ( @$rating->noitemfound || @$rating->norate ) ? 0 : $rating->totalRatingsMeanValue;
        if ( @$rating[0]->noitemfound || @$rating->norate )
        {
            $rating = array();
        }

        $result = array( 'success'=>true, 'message'=>'Rating history retrieved correctly.', 'id'=>$this->_params['id'], 'data'=>$rating );

        return $result;
    }

    /**
     * Connects with the OAuth database
     *
     * @return array is NOK | nothing if OK
     */
    private function _connect_oauth ()
    {
        try 
        {
            $db = $this->_config->get_db_oauth_info();
            $this->_oauthdb = new \PDO( 'mysql:host='.$db['host'].';dbname='.$db['database'], $db['username'], $db['password'] );
        } 
        catch ( \Exception $e ) 
        {
            $e = new APIException( 'An error ocurred while connecting with the database.' );
            $e->to_json();
        }
    }
}