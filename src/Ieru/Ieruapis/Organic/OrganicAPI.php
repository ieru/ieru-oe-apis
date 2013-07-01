<?php
/** 
 * Handles API requests for Organic.Lingua project.
 *
 * @package     Organic API
 * @version     1.1 - 2013-04-04
 * 
 * @author      David Baños Expósito
 *
 * @todo    The database used in this example of API is the VERY RUSTIC implementation of the IEEE LOM standard
 *          Not done by me, reused some very horrible code that must be refactored
 *          Steps to take with the IEEE LOM database:
 *              1) Check that the new version created by Fernando is good and uses best practices
 *              2) (Evaluate) Use an ORM for accessing the database data
 *              3) Refactor the request to the database
 */

namespace Ieru\Ieruapis\Organic; 

use \Ieru\Restengine\Engine\Exception\APIException;
use \Ieru\Ieruapis\Import\Models\Lom;
use \Ieru\Ieruapis\Import\Models\General;
use \Ieru\Ieruapis\Import\Models\GeneralsTitle;
use \Ieru\Ieruapis\Import\Models\GeneralsLanguage;
use \Ieru\Ieruapis\Import\Models\GeneralsDescription;
use \Ieru\Ieruapis\Import\Models\GeneralsKeyword;
use \Ieru\Ieruapis\Import\Models\GeneralsKeywordsText;
use \Ieru\Ieruapis\Import\Models\GeneralsCoverage;
use \Ieru\Ieruapis\Import\Models\GeneralsCoveragesText;
use \Ieru\Ieruapis\Import\Models\Identifier;
use \Ieru\Ieruapis\Import\Models\Lifecycle;
use \Ieru\Ieruapis\Import\Models\Contribute;
use \Ieru\Ieruapis\Import\Models\ContributesEntity;
use \Ieru\Ieruapis\Import\Models\Metametadata;
use \Ieru\Ieruapis\Import\Models\MetametadatasSchema;
use \Ieru\Ieruapis\Import\Models\Technical;
use \Ieru\Ieruapis\Import\Models\TechnicalsFormat;
use \Ieru\Ieruapis\Import\Models\TechnicalsLocation;
use \Ieru\Ieruapis\Import\Models\TechnicalsInstallationremark;
use \Ieru\Ieruapis\Import\Models\TechnicalsOtherplatformrequirement;
use \Ieru\Ieruapis\Import\Models\Requirement;
use \Ieru\Ieruapis\Import\Models\Orcomposite;
use \Ieru\Ieruapis\Import\Models\Educational;
use \Ieru\Ieruapis\Import\Models\EducationalsContext;
use \Ieru\Ieruapis\Import\Models\EducationalsDescription;
use \Ieru\Ieruapis\Import\Models\EducationalsLanguage;
use \Ieru\Ieruapis\Import\Models\EducationalsType;
use \Ieru\Ieruapis\Import\Models\EducationalsTypicalagerange;
use \Ieru\Ieruapis\Import\Models\EducationalsUserrole;
use \Ieru\Ieruapis\Import\Models\Right;
use \Ieru\Ieruapis\Import\Models\Relation;
use \Ieru\Ieruapis\Import\Models\Resource;
use \Ieru\Ieruapis\Import\Models\Annotation;
use \Ieru\Ieruapis\Import\Models\Classification;
use \Ieru\Ieruapis\Import\Models\ClassificationsKeyword;
use \Ieru\Ieruapis\Import\Models\Taxonpath;
use \Ieru\Ieruapis\Import\Models\Taxon;

class OrganicAPI
{
    private $_params;
    private $_db;
    private $_lang;
    private $_autolang;

    /**
     * Constructor
     *
     * @param   array   The parameters sent through a POST request or parsed from the URL routing machine
     */
    public function __construct ( &$params, &$config = null )
    {
        $this->_params = $params;
        $this->_config = $config;

        // Connect with the IEEE LOM database that stores the Organic.Edunet resources
        try
        {
            $this->_db =& LOMDatabase::get_db( $config->get_db_info() );
        }
        catch ( APIException $e )
        {
            $e->to_json();
        }

        $this->_lang     = $config->get_iso_lang();
        $this->_autolang = $config->get_autolang();

        // Create database connection
        \Capsule\Database\Connection::make('main', array(
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'database'  => 'IEEE-LOM',
            'username'  => 'root',
            'password'  => '',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
            'charset'    => 'utf8'
        ), true);
    }
    
    /**
     * Search method. Get the metadata of our resources for all languages from Celi.
     *
     * @return array
     */
    public function & get_search () 
    {
        # Request translation and resources for the searched word
        $url = $this->_config->get_analytics_server_ip().'/api/analytics/search';
        $data = $this->_curl_request( $url, $this->_params );
        $resources = json_decode( $data, true );

        # Celi service not available
        if ( isset( $resources['success'] ) AND !$resources['success'] )
        {
            $results = array( 'success'=>false, 'errcode'=>201, 'message'=>$resources['message'] );
        }
        elseif ( $resources == null )
        {
            // En este caso CLIR está caído y habrá que hacer una búsqueda local
            $results = array( 'success'=>false, 'errcode'=>200, 'message'=>'Search service not available. Try again later.' );
        }
        elseif ( !count( $resources ) )
        {
            $results = array( 'success'=>false, 'errcode'=>100, 'message'=>$resources['message'] );
        }
        # Resources retrieved from Celi
        else
        {
            # Get the metadata information of the resource
            $records =& $this->_get_lom_data_of_resources( $resources['data']['resources'] );
            if ( count( $records ) > 0 )
            {
                # Parses the facets for filtering the results
                $results['success'] = true;
                $results['message'] = 'Search results retrieved from Celi Service. ';
                $results['data']['total']   = $resources['data']['total_records'];
                $results['data']['pages']   = ceil( $resources['data']['total_records'] / $this->_params['limit'] );
                $results['data']['resources'] =& $records;
                $this->_parse_facets( $resources, $results['data']['facets'], $this->_params['lang'] );
                foreach ( $results['data']['resources'] as $key => &$value )
                    $value['position'] = @++$cont;
            }
            elseif ( count( $resources['data']['resources'] ) <> count( $records ) )
            {
                $results = array( 'success'=> false, 'errcode'=>30, 'message'=>'No results found in the local database (Not Yet Imported).' );
            }
            else
            {
                $results = array( 'success'=> false, 'errcode'=>20, 'message'=>'No results found.' );
            }
        }

        return $results;
    }

    /**
     * @todo hay que fusionar este con el _get_lom_data_of_resources, 
     * ya que hacen lo mismo (básicamente que el otro llame a este para generar cada recurso)
     *
     * @return  String  json returned by remote service
     */
    public function & fetch_resource ()
    {
        /*
        $sql = 'select * from identifier where entry_metametadata like "oai:green-oer:%"';
        $stmt = $this->_db->prepare( $sql );
        $stmt->execute();
        $fetches = $stmt->fetchAll( \PDO::FETCH_ASSOC );

        foreach ( $fetches as $row )
        {
            $sql = 'update identifier set entry_metametadata = "'.preg_replace('/oai:green-oer:/si', '', $row['entry_metametadata']).'" where FK_general = '.$row['FK_general'];
            $stmt = $this->_db->prepare( $sql );
            echo $stmt->execute();

        }
        die();
        */

        // ÑAPA ALERT
        $napa_lang = $this->_config->get_resources_langs();
        // ÑAPA ALERT

        try 
        {
            # Picks up the resource in both english (always must be in english) and the user language. Will return one or two rows.
            $sql = 'SELECT  string.Text as title, strings.Text as description, technical.format, identifier.entry_metametadata as entry, 
                            agerange.Text as age_range, identifier.entry as resource, string.language as info_lang,
                            general.language as language, string.FK_general as id, identifier.catalog as catalog, coverage.string as coverage,
                            structure.value as structure, purpose.value as purpose, technical.size as size, technical.location as location,
                            context.value as context, intendedenduserrole.value as intendeduserrole, learningresourcetype.value as learningresourcetype
                    FROM general
                        LEFT JOIN identifier ON general.PrimaryKey = identifier.FK_general
                        LEFT JOIN purpose ON purpose.FK_classification = identifier.FK_general
                        LEFT JOIN intendedenduserrole ON intendedenduserrole.FK_educational = identifier.FK_general
                        LEFT JOIN learningresourcetype ON learningresourcetype.FK_educational = identifier.FK_general
                        LEFT JOIN context ON context.FK_educational = identifier.FK_general
                        LEFT JOIN coverage ON coverage.FK_general = identifier.FK_general
                        LEFT JOIN structure ON structure.FK_general = identifier.FK_general
                        LEFT JOIN string ON string.FK_general = identifier.FK_general
                        LEFT JOIN technical ON identifier.FK_general=technical.FK_lom
                        LEFT JOIN description ON identifier.FK_general=description.FK_general
                        LEFT JOIN string as strings ON string.FK_general=strings.FK_general
                        LEFT JOIN string as agerange ON string.FK_general=agerange.FK_typicalAgeRange 
                    WHERE string.FK_general=? AND string.FK_title is not NULL
                          AND string.language = strings.language
                    GROUP BY string.language';

            $stmt = $this->_db->prepare( $sql );
            $stmt->execute( array( $this->_params['id'] ) );
            $fetches = $stmt->fetchAll( \PDO::FETCH_ASSOC );

        }
        # If an exception raises, return an empty array
        catch ( \Exception $e ) 
        {
            die( $e->getMessage() );
        }

        if ( $fetches )
        {
            # Fetch keywords
            $sql = 'SELECT language, Text, FK_keyword
                    FROM string
                    WHERE FK_general = ? AND FK_keyword > 0';
            $stmt = $this->_db->prepare( $sql );
            $stmt->execute( array( $this->_params['id'] ) );
            $keywords = $stmt->fetchAll( \PDO::FETCH_ASSOC );

            # Order keywords
            $keytemp = array();
            foreach ( $keywords as $keyword )
            {
                foreach ( explode( ',', $keyword['Text'] ) as $t )
                {
                    $keytemp[$keyword['language']][] = trim( $t );
                }
            }

            # Selects the result that is in the user language, or english by default.
            $temp = $fetches[0];
            foreach ( $fetches as &$fetched )
            {
                $temp['texts'][$fetched['info_lang']]['lang']        = $fetched['info_lang'];
                $temp['texts'][$fetched['info_lang']]['type_class']  = 'human-translation';
                $temp['texts'][$fetched['info_lang']]['type']        = 'human';
                $temp['texts'][$fetched['info_lang']]['title']       = $fetched['title'];
               @$temp['texts'][$fetched['info_lang']]['keywords']    = $keytemp[$fetched['info_lang']];

                # Fetch description
                $sql = 'SELECT Text as description
                        FROM string
                        WHERE FK_general = ? AND language = ? AND FK_description is not null';
                $stmt = $this->_db->prepare( $sql );
                $stmt->execute( array( $fetches[0]['id'], $fetched['info_lang'] ) );
                $description = $stmt->fetchAll( \PDO::FETCH_ASSOC );

                if ( $description )
                    $temp['texts'][$fetched['info_lang']]['description'] = $description[0]['description'];
            }
            # Add automatic translations from $this->_autolang
            foreach ( $this->_autolang as $autolang )
            {
                if ( !array_key_exists( $autolang, $temp['texts'] ) )
                {
                    $temp['texts'][$autolang]['lang']        = $autolang;
                    $temp['texts'][$autolang]['type_class']  = 'automatic-translation';
                    $temp['texts'][$autolang]['type']        = 'automatic';
                    $temp['texts'][$autolang]['title']       = '';
                    $temp['texts'][$autolang]['description'] = '';
                    $temp['texts'][$autolang]['keywords']    = '';
                }
            }
            ksort( $temp['texts'] );
            $results = $temp;
            $results['success'] = true;
            $results['message'] = 'API resource found.';
            $results['def_lang'] = ( isset( $this->_params['lang'] ) ) ? $this->_params['lang'] : 'en';

            // ÑAPA ALERT
            if ( array_key_exists( $results['location'], $napa_lang ) )
                $results['napa_langs'] = $napa_lang[$results['location']];
            else
                $results['napa_langs'] = array( $results['language'] );
            // ÑAPA ALERT
        }
        else
        {
            $results['success'] = false;
            $results['errcode'] = 10;
            $results['message'] = 'API resource not found.';
        } 
        return $results;
    }

    /**
     * Parses and formats the facets information of the request to Celi service.
     *
     * @param   String  $resources  The facets
     * @return  void
     */
    private function _parse_facets ( &$resources, &$facets, $lang )
    {
        // Location of filters language file
        $file = 'filters.php';

        // Load to $translation the contents of filters languages
        $translations = include( $file );

        // Cycle through the facets
        $i = $j = 0;
        foreach ( $resources['data']['facets'] as $key=>&$facet )
        {
            if ( isset( $facet['filters'] ) )
            {
                // Cycle through the filters
                foreach ( $facet['filters'] as $k => $v )
                {
                    $facet_name = ( $facet['facet'] == 'language' ) ? $this->_lang[$v['filter']] : $v['filter'];
                    $facets[$i]['name'] = $facet['facet'];

                    // Check if there is a tranlation for it in the translations array,
                    // or request a translation to the translation service (and then
                    // it will be stored in the array and sent to a file)
                    if ( array_key_exists( strtolower( $v['filter'] ), $translations )
                         AND array_key_exists( $lang, $translations[strtolower( $v['filter'] )] )  )
                    {
                        $tr = $translations[strtolower($v['filter'])][$lang];
                    }
                    else
                    {
                        $saved = true;
                        $url = $this->_config->get_analytics_server_ip().'/api/analytics/translate';
                        $data = array( 'text'=>$facet_name, 'from'=>'en', 'to'=>$lang, 'service'=>'microsoft' );
                        $tr = json_decode( $this->_curl_get_data( $url, $data ) );
                        $tr = $tr->data->translation;
                        $translations[strtolower($v['filter'])][$lang] = $tr;
                    }

                    // create filter entry
                    $facets[$i]['results'][] = array( 'filter'=>$facet_name, 'value'=>$v['resources'], 'translation'=>$tr );
                }
                $i++;
            }
        }

        // Store languages in the file if any modification was done
        if ( @$saved AND $fp = fopen( $file, 'w+' ) )
        {
            fwrite( $fp, "<?php\n" );
            fwrite( $fp, "return array(\n" );
            foreach ( $translations as $key=>$value )
            {
                fwrite( $fp, "\t'$key'=>array(\n" );
                foreach ( $value as $k=>$v )
                {
                    fwrite( $fp, "\t\t'$k'=>'". addslashes($v)."',\n" );
                }
                fwrite( $fp, "\t),\n" );
            }
            fwrite( $fp, "\n);" );
            fclose( $fp );
        }
    } 

    /**
     * Gets the resources 
     *
     * @param   array   $uris   The identifiers of the resources to search in LOM database.
     * @return  array
     */
    private function & _get_lom_data_of_resources ( &$uris )
    {
        $results = array();  

        # Loop the uris for getting the local LOM resource info
        if ( $uris )
        {
            foreach ( $uris as $uri ) 
            {
                $lom = array();

                $resource = Lom::with(array('Technical', 'General.GeneralsTitle', 'General.GeneralsDescription', 
                                'General.GeneralsLanguage','General.GeneralsKeyword.generalskeywordtext',
                                'Educational.EducationalsTypicalagerange', 'Metametadata'))
                            ->join('technicals', 'loms.lom_id','=','technicals.lom_id')
                            ->join('technicals_locations', 'technicals.technical_id','=','technicals_locations.technical_id')
                            ->where('technicals_locations.technicals_location_text','=',$uri['resource'])
                            ->get(array( 'loms.lom_id', 'technicals_locations.technicals_location_text') )[0];

                $lom['id'] = $resource->lom_id;
                $lom['location'] = $resource->technicals_location_text;

                foreach ( $resource->general->generalslanguage as $lang )
                    $lom['languages'][] = $lang->generals_language_lang;
                foreach ( $resource->educational as $edu )
                    foreach ( $edu->educationalstypicalagerange as $age )
                        $lom['age_range'][] = $age->educationals_typicalagerange_string;

                // Set titles: if it has no language set, use metametadata language identifier
                foreach ( $resource->general->generalstitle as $title )
                    if ( $title->generals_title_lang )
                        $lom['texts'][$title->generals_title_lang]['title'] = $title->generals_title_string;
                    elseif ( $resource->metametadata->metametadata_lang )
                        $lom['texts'][$resource->metametadata->metametadata_lang]['title'] = $title->generals_title_string;
                // Set descriptions: if it has no language set, use metametadata language identifier
                foreach ( $resource->general->generalsdescription as $description )
                    if ( $description->generals_description_lang )
                        $lom['texts'][$description->generals_description_lang]['description'] = $description->generals_description_string;
                    elseif ( $resource->metametadata->metametadata_lang )
                        $lom['texts'][$resource->metametadata->metametadata_lang]['description'] = $description->generals_description_string;
                // Set keywords: if it has no language set, use metametadata language identifier
                foreach ( $resource->general->generalskeyword as $keyword )
                    foreach ( $keyword->generalskeywordtext as $text )
                        if ( $text->generals_keywords_text_lang )
                            $lom['texts'][$text->generals_keywords_text_lang]['keyword'][] = $text->generals_keywords_text_string;
                        elseif ( $resource->metametadata->metametadata_lang )
                            $lom['texts'][$resource->metametadata->metametadata_lang]['keyword'][] = $text->generals_keywords_text_string;

                $results[] = $lom;
            }
        }
        return $results;
    }

    /**
     * Fetch a specific set of resources
     *
     * @return array 
     */
    public function fetch_resources ()
    {
        $results = array();

        $this->_params['lang'] = 'en';
        $results['records'] =& $this->_get_lom_data_of_resources( $this->_params['identifiers'], $this->_params['lang'] );

        if ( count( $results['records'] ) > 0 )
        {
            $results['success'] = true;
            $results['message'] = 'Resources retrieved.';
            $results['total']   = $this->_params['total'];
            $results['pages']   = ceil( $this->_params['total'] / $this->_params['limit'] );
            $results['time']    = 0;
            $results['offset']  = $this->_params['offset'];
            $results['limit']   = $this->_params['limit'];

            foreach ( $results['records'] as $key => &$value )
                $value['position'] = @++$cont;
        }
        else
        {
            $results['success'] = false;
            $results['message'] = 'No results found.';
            unset( $results['records'] );
        }

        return $results;
    }

    /**
     * Makes an HTTP through curl as POST
     *
     * @param   string    $url          The URL for making the request to
     * @param   array     $post_data    The data to be passed through as POST payload
     * @return string The requested URL content
     */
    private function _curl_request ( $url, &$post_data )
    {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url.'?'.http_build_query( $post_data ) );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 15 );
        #curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $post_data ) );
        $data = curl_exec( $ch );
        if ( curl_errno($ch) > 0 )
        {
            $e = new APIException( 'Search request timeout.' );
            $e->to_json();
            die();
        }
        curl_close( $ch );
        return $data;
    }

    /**
     * Connects with the remote services. Sets a timeout for connecting the 
     * service and a timeout for receiving the data.
     *
     * @param   String  $url        The url to retrieve, it must return a json.
     * @return  String  json returned by remote service
     */
    private function & _curl_get_data ( $url, $data = null ) 
    {
        $ch = curl_init();
        if ( $data )
            curl_setopt( $ch, CURLOPT_URL, $url.'?'.http_build_query( $data ) );
        else
            curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 15 );
        $data = curl_exec( $ch );
        curl_close( $ch );
        return $data;
    }

    /**
     * Makes an autocomplete request to the search service, with a small list of possible terms.
     *
     * @return array
     */
    public function fetch_typeahead ()
    {
        $auto = array();

        // Make request
        $url = 'http://research.celi.it:8080/OrganicLinguaSolr/select/?q=*%3A*&facet=true&facet.field=autocompletion&facet.mincount=1&facet.prefix='.$this->_params['text'].'&rows=0&wt=json';
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 2 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 15 );
        $data = curl_exec( $ch );

        // Check timeout error
        if ( curl_errno($ch) > 0 )
        {
            $e = new APIException( 'Search request timeout.' );
            $e->to_json();
            die();
        }

        // Format the autocompletion terms
        $terms = json_decode( $data );
        foreach ( $terms->facet_counts->facet_fields->autocompletion as $term )
            if ( !is_numeric( $term ) )
                $auto[] = $term;

        // Return the json with the autoterms
        //$results = array( 'success'=> true, 'message'=>'Autocomplete fetches.', 'data'=>array( 'terms'=>$auto ) );
        // Must return only the array of terms for using Twitter Typeahed javascript module
        return $auto;
    }
}