<?php
/**
 * Configuration file for Organic.Edunet Analytics API
 *
 * @package     Organic API
 * @version     1.0 - 2013-04-04
 * 
 * @author      David Baños Expósito
 */

namespace Ieru\Ieruapis\Analytics\Config;

class Config
{
	private $_routes;

	/**
	 * Returns the routes allowed in this API
	 *
	 * @return array
	 */
	public function & get_routes ()
	{
		if ( !$this->_routes )
		{
			$this->_routes['GET'][] = array( '/search',    'controller'=>'AnalyticsAPI#get_search' );
			$this->_routes['GET'][] = array( '/translate', 'controller'=>'AnalyticsAPI#get_translation' );

			$this->_routes['GET'][] = array( '/resources/:entry/rating',          'controller'=>'AnalyticsAPI#get_rating' );
			$this->_routes['GET'][] = array( '/resources/:entry/ratings',         'controller'=>'AnalyticsAPI#get_history' );
			$this->_routes['GET'][] = array( '/resources/:entry/ratings/reviews', 'controller'=>'AnalyticsAPI#get_review_history' );
			$this->_routes['GET'][] = array( '/resources/:entry/tags',            'controller'=>'AnalyticsAPI#get_tags' );

			$this->_routes['POST'][] = array( '/resources/:entry/rating',         'controller'=>'AnalyticsAPI#add_rating' );
		}
		return $this->_routes;
	}

	/**
	 * Returns the routes allowed in this API
	 *
	 * @return array
	 */
	public function get_translation_services ()
	{
		return array( 'microsoft', 'celi', 'xerox', 'google' );
	}

	/**
	 * Languages for which we have to use an specific translation service by default
	 *
	 * @return array
	 */
	public function get_default_translation_services ()
	{
		return array();
		return array( 'es'=>'xerox', 'fr'=>'xerox', 'de'=>'xerox', 'it'=>'xerox' );
	}

	/**
	 * Returns the routes allowed in this API
	 *
	 * @return array
	 */
	public function get_search_services ()
	{
		return array( 'celi' );
	}

	/**
	 * Get the data for connecting with the ANALYTICS database
	 *
	 * @return array The data needed for connecting with the database
	 */
	public function get_db_analytics_info ()
	{
		return array( 
			'host'=>'localhost',
			'database'=>'analytics_service',
			'username'=>'root',
			'password'=>''
		);
	}

	/**
	 * Get the data for connecting with the OAUTH database
	 *
	 * @return array The data needed for connecting with the database
	 */
	public function get_db_oauth_info ()
	{
		return array( 
			'host'=>'localhost',
			'database'=>'ieru_organic_lingua_oauth',
			'username'=>'root',
			'password'=>''
		);
	}
}