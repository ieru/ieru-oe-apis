<?php
/**
 * Configuration file for Organic Lingua API
 *
 * @package     Organic API
 * @version     1.2 - 2013-04-04 | 1.1 - 2013-03-05 | 1.0 - 2012-10-08
 * 
 * @author      David Baños Expósito
 */

namespace Ieru\Ieruapis\Organic\Config;

class Config
{
    private $_routes;
    private $_autolang;
      
    /**
     * Returns the routes allowed in this API
     *
     * @return array
     */
    public function & get_routes ()
    {
        if ( !$this->_routes )
        {
            $this->_routes['POST'][] = array( '/resources',     'controller'=>'OrganicAPI#fetch_resources' );
            $this->_routes['GET'][]  = array( '/resources/:id', 'controller'=>'OrganicAPI#fetch_resource' );

            $this->_routes['GET'][]  = array( '/search',           'controller'=>'OrganicAPI#get_search' );
            $this->_routes['POST'][] = array( '/search',           'controller'=>'OrganicAPI#fetch_resources' );
            $this->_routes['GET'][]  = array( '/search/typeahead', 'controller'=>'OrganicAPI#fetch_typeahead' );

            $this->_routes['GET'][]  = array( '/login',    'controller'=>'AuthAPI#login' );
            $this->_routes['GET'][]  = array( '/logout',   'controller'=>'AuthAPI#logout' );
            $this->_routes['POST'][] = array( '/register', 'controller'=>'AuthAPI#register' );

            $this->_routes['GET'][]  = array( '/users/:user/activate', 'controller'=>'AuthAPI#activate' );
            $this->_routes['POST'][] = array( '/users/retrieve', 'controller'=>'AuthAPI#retrieve' );
            $this->_routes['GET'][]  = array( '/users/password/change/:token', 'controller'=>'AuthAPI#retrieve_accepted' );

            $this->_routes['POST'][] = array( '/feedback', 'controller'=>'OrganicAPI#feedback' );
        }
        return $this->_routes;
    }

    /**
     * Returns the routes allowed in this API
     *
     * @return array
     */
    public function & get_autolang ()
    {
        if ( !$this->_autolang )
        {
            $this->_autolang[] = 'en'; # English
            $this->_autolang[] = 'de'; # German
            $this->_autolang[] = 'fr'; # French
            $this->_autolang[] = 'es'; # Spanish
            $this->_autolang[] = 'it'; # Italian
            $this->_autolang[] = 'el'; # Greek
            $this->_autolang[] = 'tr'; # Turkish
            $this->_autolang[] = 'lv'; # Latvian
            $this->_autolang[] = 'et'; # Estonian
            $this->_autolang[] = 'pl'; # Polish
            $this->_autolang[] = 'pt'; # Portuguese
            $this->_autolang[] = 'pl'; # Polish
            $this->_autolang[] = 'cn'; # Chinese
            $this->_autolang[] = 'ru'; # Russian
            $this->_autolang[] = 'no'; # Norwegian
            $this->_autolang[] = 'sl'; # Slovenian
            $this->_autolang[] = 'hu'; # Hungarian
            $this->_autolang[] = 'et'; # Estonian
            $this->_autolang[] = 'ar'; # Arabic
        }
        return $this->_autolang;
    }

    /**
     * Retuns an array with all the possible ISO 639-2 lang specification
     *
     * @return array An array with the 2 letter ISO language codes
     */
    public function get_iso_lang ()
    {
        return include( 'Languages.php' );
    }
}