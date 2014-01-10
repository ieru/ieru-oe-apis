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
use \Ieru\Ieruapis\Analytics\Models\Rating;

use Illuminate\Database\Capsule\Manager as Capsule;

class AnalyticsAPI
{
    /**
     * Constructor
     */
    public function __construct ( $params, $config, $databases = null )
    {
        $this->_db     = $databases;
        $this->_params = $params;
        $this->_config = $config;
    }

    /**
     * Translates a word or phrase, automatically selecting the translation service.
     *
     * @return string The json with the request to be formatted
     */
    public function get_search ()
    {
        // Default search service
        $service = 'celi';

        // Check the service intended to be used for translation purposes
        try
        {
            // Check that the required parameters are set according to the API document
            if ( !isset( $this->_params['offset'] ) OR !isset( $this->_params['limit'] ) )
                throw new APIException( 'Check the API documentation for the required parameters.' );

            // If the user requests a specific service, check that it is an allowed one
            if ( array_key_exists( 'service', $this->_config->get_search_services() ) )
            {
                // Check for a valid service
                if ( in_array( $this->_params['service'], $this->_config->get_search_services() ) )
                    $service = $this->_params['service'];
                else
                    throw new APIException( 'Requested search service not available in this API.' );
            }

            // Create service provider adapter
            $class_name = 'Ieru\Ieruapis\Analytics\Providers\Search\\'.ucfirst( $service ).'Service';
            $service = new $class_name();

            // Start of request
            $this->_start = microtime( true );

            // Try to connect to the translation service
            if ( $service->check_status() )
                $service->connect();
            else
                throw new APIException( $class_name.' unavailable.' );

            $response = $service->request( $this->_params, $this->_params['request_string'], $this->_config );
            $json = $service->format( json_decode( $response ) );

            // End of request
            $this->_end = microtime( true );

            // Save request in the database
            $this->_save_search_request( $response );
        }
        catch ( APIException $e )
        {
            // Save request in the database
            @$this->_save_search_request();

            // Throw the error as json
            $e->to_json();
        }

        return $json;
    }

    /**
     * Stores the info of a request in the database.
     *
     * @return void
     * @todo If an error raises while saving the request to a log, send an email to the admins
     */
    private function _save_search_request ( &$response = '' )
    {
        try
        {
            $db_info = $this->_db['analytics'];
            $this->_db_al = new \PDO( 'mysql:host='.$db_info['host'].';dbname='.$db_info['database'], $db_info['username'], $db_info['password'] );
            $data['service_id']         = 1;
           @$data['request_language']   = $this->_params['lang'];
            $data['request_string']     = $this->_params['request_string'];
            $data['request_response']   = $response;
            $data['request_term']       = $this->_params['text'];
            $data['request_start_time'] = @$this->_start;
            $data['request_end_time']   = @$this->_end;
            $this->_save_request( $data );

            // Mirar en qué formato se van a guardar los logs a disco y cómo.
            // redis.io <- nosql a logs.
            // little book of redis.
            // Cambiar logs a un adaptador también.

            // Selector de mejor traductor para cada idioma
        }
        // Even if this throws an exception, it must not block sending back the resources
        catch ( \Exception $e )
        {
            // Best option -> send email to the administrator telling them that the logging system is down
        }
    }

    /**
     * Stores the info of a request in the database.
     *
     * @return void
     * @throws Exception
     */
    private function _save_request ( $data )
    {
        if ( !is_array( $data ) )
            throw new Exception( 'Could not save request into analytics service. ' );

        // Get time and ip of the request
        $data['request_ip']       = $_SERVER['REMOTE_ADDR'];
        $data['request_datetime'] = @date( 'Y-m-d H:i:s' );

        // Variables for formatting automatically the INSERT statement
        foreach ( $data as $key=>$value )
        {
            if ( !is_array( $value ) )
            {
                $set[] = $key.' = ?';
                $info[] = $value;
            }
        }

        // Esto está haciendo que falle cuando hay varios filtros activos
        // Store in the database the Analytics Service database
        $stmt = $this->_db_al->prepare( 'INSERT INTO request SET '.implode( ',', $set ) );
        $stmt->execute( $info );
    }

    /**
     * Translates a text from one language to another specified by the user
     *
     * @return array
     */
    public function get_translation ()
    {
        if ( isset( $this->_params['cache'] ) AND $this->_params['cache'] )
        {
            // Try to get translation from cache
            $cache = new Providers\Cache\CeliService( $this->_params );
            $translation = json_decode( $cache->request( $this->_params ) );

            // If not in cache, request a translation and store it in the cache service
            if ( @$translation->success == 'false' OR !$translation )
            {
                // Get the text and set it in the cache
                $translation = $this->_translate();
                $data['provider'] = $translation['data']['service_used'];
                $data['from'] = $this->_params['from'];
                $data['to'] = $this->_params['to'];
                $data['text'] = $this->_params['text'];
                $data['translation'] = $translation['data']['translation'];
                $cache->add( $data );
                return $translation;
            }
            else
            {
                return array( 'success'=>true, 
                              'message'=>'Translation retrieved from cache.', 
                              'data'=>array( 'translation'=>$translation->translation,
                                             'service_used'=>$translation->provider ) );
            }
        }
        else
        {
            return $this->_translate();
        }
    }

    /**
     *
     */
    private function _translate ()
    {
        // Check the service intended to be used for translation purposes
        try
        {
            // Check that the request has a target language set
            if ( !isset( $this->_params['to'] ) )
                throw new APIException( 'Specify a target language for the translation.' );

            // Default translation service, check language for that
            $def_service = 'microsoft';
            $def_langs = $this->_config->get_default_translation_services();
            if ( array_key_exists( $this->_params['to'], $def_langs ) )
                $def_service = $def_langs[$this->_params['to']];

            if ( array_key_exists( 'service', $this->_params ) )
            {
                // Check for a valid service
                if ( in_array( $this->_params['service'], $this->_config->get_translation_services() ) )
                {
                    $def_service = $this->_params['service'];
                }
                else
                {
                    throw new APIException( 'Requested translation service not available in this API.' );
                }
            }

            $class_name = 'Ieru\Ieruapis\Analytics\Providers\Translation\\'.ucfirst( $def_service ).'Service';
            $service = new $class_name( $this->_params );

            // Track how many time takes a translation with this service to complete
            $start = microtime(true);

            // Try to connect to the translation service
            if ( $service->check_status() )
                $service->connect();
            else
                throw new APIException( $class_name.' unavailable.' );
            
            // Execute the translation
            $translation = $service->request( $this->_params );

            // Track how many time takes a translation with this service to complete
            $end = microtime(true);

            // In case there is no translation retrieved, use default
            // translation service
            if ( $translation == '' )
            {
                $this->_params['service'] = 'microsoft';
                return $this->get_translation();
            }
        }
        catch ( APIException $e )
        {
            $e->to_json();
        }

        // Save the translation details to the database
        return array( 'success'=>true, 'message'=>'Translation done.', 'data'=>array( 'translation'=>$translation, 'service_used'=>$def_service, 'seconds_taken'=>$end-$start ) );
    }

    /**
     * Retrieves the available services for doing translations
     *
     * @return array
     */
    public function get_providers ()
    {
        $services = $this->_config->get_translation_services();
        foreach ( $services as $service )
            $providers[] = array( 'name'=>$service );

        return array( 'success'=>true, 'message'=>'Providers of translation services retrieved.', 'data'=>array( 'providers'=>$providers ) );
    }

    /**
     * Retrieves the available languages for translation
     *
     * @return array
     */
    public function get_languages ()
    {
        try
        {
            // Create service provider adapter
            $class_name = 'Ieru\Ieruapis\Analytics\Providers\Translation\\MicrosoftService';
            $service = new $class_name();

            // Try to connect to the translation service
            if ( $service->check_status() )
                $service->connect();
            else
                throw new APIException( $class_name.' unavailable.' );

            $response = $service->languages( $this->_params );
            foreach ( $response as $language )
                $languages[] = array( 'language'=>$language );
        }
        catch ( APIException $e )
        {
            $e->to_json();
        }

        return array( 'success'=>true, 'message'=>'Available languages for translations retrieved.', 'data'=>array( 'languages'=>$languages ) );
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
            $db = $this->_db['oauth'];
            $this->_oauthdb = new \PDO( 'mysql:host='.$db['host'].';dbname='.$db['database'], $db['username'], $db['password'] );
        } 
        catch ( \Exception $e ) 
        {
            $e = new APIException( 'An error ocurred while connecting with the database.' );
            $e->to_json();
        }
    }

    /**
     * Add a rating to an automatic translation
     *
     * @return array 
     */
    public function add_rating ()
    {
        // Check valid usertoken
        $config  = null;
        $oauth   = new \Ieru\Ieruapis\Organic\AuthAPI( $this->_params, $config, $this->_db );

        // Add the rating
        try
        {
            // Add an entry to the
            $rating = new Rating();
            $rating->user_id          = $oauth->check( $this->_params['usertoken'] );
            $rating->service_id       = @trim( $this->_params['service'] ) == 'xerox' ? 2 : 1;
            $rating->resource_id      = $this->_params['entry'];
            $rating->translation_id   = $this->_params['hash'];
            $rating->rating_value     = $this->_params['rating'];
            $rating->translation_from = @$this->_params['from'];
            $rating->translation_lang = $this->_params['to'];
            $rating->save();
        }
        // Duplicated rating
        catch ( \Exception $e )
        {
        }

        // Retrieve rating
        $rating  = 0;
        $ratings = Rating::where( 'translation_id', '=', $this->_params['hash'] )->get();
        $votes   = $ratings->count();
        foreach ( $ratings as $vote )
            $rating += $vote->rating_value;

        // Return successful rating addition
        return array( 'success'=>true, 'message'=>'Rating added to the resource.', 'data'=>array( 'rating'=>round( $rating / $votes ), 'votes'=>$votes ) );
    }

    /**
     * Retrieve the rating of a translation
     *
     * @return array
     */
    public function get_rating ()
    {
        // Create database connection through Eloquent ORM to the Analytics DB
        $capsule = new Capsule();
        $capsule->addConnection( $this->_db['analytics'], 'analytics' );
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Retrieve rating
        $ratings = Rating::where( 'translation_id', '=', $this->_params['hash'] )->get();

        // Return information
        if ( $ratings->count() )
        {
            $votes = $ratings->count();
            foreach ( $ratings as $vote )
                @$rating += $vote->rating_value;

            return array( 'success'=>true, 'message'=>'Rating retrieved for the translation.', 'data'=>array( 'rating'=>round( $rating / $votes ), 'votes'=>$votes ) );
        }
        else
        {
            return array( 'success'=>false, 'message'=>'No ratings yet for the resource.' );
        }
    }

    /**
     * Retrieve the ratings of a translation for a given resource
     * @return array
     */
    public function get_ratings_history ()
    {
        // Create database connection through Eloquent ORM to the Analytics DB
        $capsule = new Capsule();
        $capsule->addConnection( $this->_db['analytics'], 'analytics' );
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Retrieve rating
        $ratings = Rating::where( 'translation_id', '=', $this->_params['hash'] )->get();

        // Return information
        if ( $ratings->count() )
        {
            $formatted = array();
            foreach ( $ratings as $rating )
                $formatted[] = array( 'userid'=>$rating->user_id, 'ratingMean'=>$rating->rating_value, 'rated_on'=>$rating->updated_at );

            return array( 'success'=>true, 'message'=>'Ratings retrieved for the translation.', 'data'=>$formatted );
        }
        else
        {
            return array( 'success'=>false, 'message'=>'No ratings yet for the resource.' );
        }
    }
}