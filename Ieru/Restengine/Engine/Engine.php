<?php
/**
 * API start point.
 *
 * @package     Organic API
 * @version     1.1 - 2013-04-04
 * 
 * @author      David Baños Expósito
 */

namespace Ieru\Restengine\Engine;

class Engine
{
    /* Attributes */
    private $_url = array();
    private $_params = array();
    private $_routes = array();
    private $_config = null;

    private $_api_path;
    private $_api_name;
    private $_api_vendor;
    private $_api_namspace;

    /**
     * Constructor
     *
     * @param string $path_to_api   The first parts of the URI must be the API identifier 
     *                              (like 'api', '2.0', '1.1', 'api/2.0', etc.)
     * @param string $api_vendor    The namespace used for the IERU REST Engine API 
     *                              data is stored
     */
    public function __construct ( $path_to_api, $api_vendor )
    {
        // Extract info from the URI
        $uri = explode( '?', $_SERVER['REQUEST_URI'] );
        $uri = explode( '/', substr( $uri[0], 1 ) );

        // If the first part of the URI is not the api path
        if ( $uri[0] == $path_to_api )
        {
            // Set parameters in this 
            $this->_api_name      = $uri[1];
            $this->_api_path      = $path_to_api;
            $this->_api_vendor    = $api_vendor;
            $this->_api_namespace = $this->_api_vendor.'\\'.ucfirst( $this->_api_name );

            $config = $this->_api_namespace.'\Config\Config';
            $this->_config = new $config();

            $this->_routes =& $this->_config->get_routes();
            $this->_url['route'] = $this->_parse_url_params();
            try
            {
                $this->_set_params();
            }
            catch ( Exception\APIException $e )
            {
                $e->to_json();
            }
        }
        else
        {
            $this->redirect( 404 );
        }
    }

    /**
     * Starter method of the API.
     *
     * @return  void
     */
    public function start ()
    {
        // Gets the class and method to execute
        $arr = explode( '#', $this->_url['route']['controller'] );
        $name = $this->_api_namespace.'\\'.$arr[0];
        $controller = new $name( $this->_params, $this->_config );

        // Returns the result of the execution of class+method as a json
        if ( $_SERVER['REQUEST_METHOD'] != 'HEAD' )
            header( 'Content-Type: application/json; charset=utf-8' );
        die( json_encode( $controller->$arr[1]() ) );
    }

    /**
     * Redirect to the defined website's page and ends script
     * 
     * @param   string  $code        The page to be redirected to (home page by default)
     * @return  void
     */
    public function redirect ( $code ) 
    {
        // Available codes
        $codes[403] = array( 'Forbidden', 'You can not access the resource.' );
        $codes[404] = array( 'Not Found', 'Resource not found.' );
        $codes[410] = array( 'Gone',      'The resource does not exist in this server any longer.' );

        // Default redirection code is 404
        if ( !in_array( $code, $codes ) OR !is_numeric( $code ) )
            $code = 404;

        // Set headers (including HTML error) and return json with the specified error
        if ( $_SERVER['REQUEST_METHOD'] != 'HEAD' )
            header( 'content-type: application/json; charset=utf-8' );
        header( $_SERVER['SERVER_PROTOCOL'].' '.$code.' '.$codes[$code][0] );
        echo json_encode( array( 'success'=>false, 'message'=>$codes[$code][1] ) );
        exit(); // for stopping the script
    }

    /**
     * Looks for the URL in the routes allowed for the application.
     * Also extracts the information from the URL and assign it to variables.
     * Checks for request method (RESTful services).
     *
     * @return route
     */
    private function _parse_url_params ()
    {      
        // Check if the URL is like any of the route options of this application. Uses REST. Done with a regex.
        if ( isset( $this->_routes[$_SERVER['REQUEST_METHOD']] ) )
        {
            foreach ( $this->_routes[$_SERVER['REQUEST_METHOD']] as $route )
            {
                // If there is a match, stop looking for more
                $uri = explode( '?', $_SERVER['REQUEST_URI'] );
                if ( preg_match( '@^'.preg_replace( '@:([^/]+)@si', '(?P<\1>[^/]+)', "/{$this->_api_path}/{$this->_api_name}".$route[0] ).'$@', $uri[0], $this->_params ) )
                {
                    array_shift( $this->_params );
                    return $route;
                }
            }
        }

        // If the route is not set in the router variables
        $this->redirect( 404 );
    }

    /**
     * Sets in the $_params object var the $_POST variables
     *
     * @return void
     * @throws APIException If a duplicated entry
     * @todo merge $_POST and $_GET, and and support for PUT
     */
    private function _set_params ()
    {
        // Set PUT, POST and GET variables into $this->_params
        // Dont use $_REQUEST because we do not want to use the cookies too 
        foreach ( $_POST as $key=>$val )
        {
            if ( !isset( $this->_params[$key] ) )
                $this->_params[$key] = $val;
            else
                throw new Exception\APIException( 'Duplicated entry in {params}: '.$key );
        }

        foreach ( $_GET as $key=>$val )
        {
            if ( !isset( $this->_params[$key] ) )
                $this->_params[$key] = $val;
            else
                throw new Exception\APIException( 'Duplicated entry in {params}: '.$key );
        }
    }
}