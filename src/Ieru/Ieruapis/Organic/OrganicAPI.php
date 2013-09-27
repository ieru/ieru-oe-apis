<?php
/** 
 * Handles API requests for Organic.Lingua project.
 *
 * @package     Organic API
 * @version     1.2 - 2013-07-01 | 1.1 - 2013-04-04
 * 
 * @author      David Baños Expósito
 */

namespace Ieru\Ieruapis\Organic; 

use \Ieru\Restengine\Engine\Exception\APIException;
use \Ieru\Ieruapis\Import\Models\Lom;
use \Ieru\Ieruapis\Import\Models\Identifier;

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
    public function __construct ( &$params, &$config = null, $databases = null )
    {
        $this->_params = $params;
        $this->_config = $config;

        $this->_lang     = $config->get_iso_lang();
        $this->_autolang = $config->get_autolang();
        $this->_db = $databases;

        // Create database connection through Eloquent ORM
        \Capsule\Database\Connection::make('main', $this->_db['resources'], true);
    }
    
    /**
     * Search method. Get the metadata of our resources for all languages from Celi.
     *
     * @return array
     */
    public function & get_search () 
    {
        # Request translation and resources for the searched word
        $url = API_SERVER.'/api/analytics/search';
        $data = $this->_curl_request( $url, $this->_params );
        $resources = json_decode( $data, true );
        $this->_params['lang'] = isset( $this->_params['lang'] ) ? $this->_params['lang'] : 'en';

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
        elseif ( isset( $resources['data']['resources'] ) )
        {
            $res = array();
            foreach ( $resources['data']['resources'] as $resource )
                $res[] = $resource['resource'];

            # Get the metadata information of the resource
            $records =& $this->_get_lom_data_of_resources( $res );
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
        // No results found
        else
        {
            $results = array( 'success'=> false, 'errcode'=>20, 'message'=>'No results found.' );
        }

        return $results;
    }

    /**
     * Retrieve a resource by lom_id (its an internal identification) from the database
     *
     * @return  String  json returned by remote service
     */
    public function & fetch_resource ()
    {
        try 
        {
            $resource = Lom::with(array('Technical.TechnicalsLocation', 'General.GeneralsTitle', 'General.GeneralsDescription', 
                    'General.GeneralsLanguage','General.GeneralsKeyword.generalskeywordtext', 'Educational.educationalsuserrole',
                    'Educational.EducationalsTypicalagerange', 'Educational.EducationalsContext', 'Metametadata',
                    'Educational.EducationalsType'))
                ->where('loms.lom_id','=',$this->_params['id'])
                ->first();

            if ( $resource )
                $results = array( 'success'=>true, 'message'=>'Resource metadata retrieved.', 'data'=>$this->_make_resource( $resource ) );
            else
                $results = array( 'success'=>false, 'errcode'=>10, 'message'=>'API resource not found.' );
        }
        catch ( \Exception $e ) 
        {
            die( $e->getMessage() );
        }

        return $results;
    }

    /**
     * Fetch a specific set of resources
     *
     * @return array 
     */
    public function & fetch_resources ()
    {
        $results = array();

        $this->_params['lang'] = 'en';
        $results['records'] =& $this->_get_lom_data_of_resources( $this->_params['identifiers'] );

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
     * Makes an autocomplete request to the search service, with a small list of possible terms.
     *
     * @return array
     */
    public function & fetch_typeahead ()
    {
        $auto = array();

        // Make request
        $url = 'http://demo.celi.it:9095/OrganicLinguaSolr/select/?q=*%3A*&facet=true&facet.field=autocompletion&facet.mincount=1&facet.prefix='.$this->_params['text'].'&rows=0&wt=json';
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
        foreach ( $uris as $uri ) 
        {
            $lom = array();

            $resource = Identifier::where('identifier_entry','=',$uri)
                                  ->first( array( 'lom_id' ) );

            // Check that a resource was found
            if ( is_object( $resource  ) )
            {
                $this->_retrieve_basic_data( $lom, $resource->lom );
                $this->_add_automatic_languages( $lom );
                $results[] = $lom;
            }
        }

        return $results;
    }

    /**
     * Retrieves all the metadata of a given resource
     *
     * @param   int     $resource   The internal identification number of the resource
     * @return  array   The complete metadata of the resource
     */
    private function _make_resource ( &$resource )
    {
        $lom = array();

        $this->_retrieve_basic_data( $lom, $resource );
        $this->_add_automatic_languages( $lom );

        return $lom;
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
                        $url = API_SERVER.'/api/analytics/translate';
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
                    fwrite( $fp, "\t\t'$k'=>'". addslashes($v)."',\n" );
                fwrite( $fp, "\t),\n" );
            }
            fwrite( $fp, "\n);" );
            fclose( $fp );
        }
    } 

    /**
     * Adds automatic translation languages
     *
     * @param   array   $lom    The resource info
     * @return void
     */
    private function _add_automatic_languages ( &$lom )
    {
        # Add automatic translations from $this->_autolang
        foreach ( $this->_autolang as $autolang )
        {
            if ( !array_key_exists( $autolang, $lom['texts'] ) )
            {
                $lom['texts'][$autolang]['lang']        = $autolang;
                $lom['texts'][$autolang]['type_class']  = 'automatic-translation';
                $lom['texts'][$autolang]['type']        = 'automatic';
                $lom['texts'][$autolang]['title']       = '';
                $lom['texts'][$autolang]['description'] = '';
                $lom['texts'][$autolang]['keywords']    = '';
            }
        }
    }

    /**
     * Retrieves the basic information of a resource
     *
     * @param   array   $lom        The array where to put the resulting info
     * @param   object  $resrouce   Eloquent ORM database object with the resoource data
     * @return void
     */
    private function _retrieve_basic_data ( &$lom, &$resource )
    {
        $lom['id'] = $resource->lom_id;
        $lom['location'] = $resource->technical->technicalslocation[0]->technicals_location_text;
        $lom['format'] = $resource->technical->technicalsformat[0]->technicals_format_text;
        $lom['xml'] = $resource->lom_original_file_name;
        $lom['identifiers'] = $resource->general->identifier[0]->identifier_entry;

        // Set languages
        foreach ( $resource->general->generalslanguage as $lang )
            $lom['languages'][] = $lang->generals_language_lang;
        // ÑAPA: delete after all the resources have a valid general.language (1..*)
        if ( !isset( $lom['languages'] ) ) $lom['languages'][0] = 'en';

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
                    $lom['texts'][$text->generals_keywords_text_lang]['keywords'][] = $text->generals_keywords_text_string;
                elseif ( $resource->metametadata->metametadata_lang )
                    $lom['texts'][$resource->metametadata->metametadata_lang]['keywords'][] = $text->generals_keywords_text_string;
        // Educational contexts
        foreach ( $resource->educational as $res )
            foreach ( $res->educationalscontext as $edu )
                $lom['educational'][] = $edu->educationals_context_string;
        // Educational types
        foreach ( $resource->educational as $res )
            foreach ( $res->educationalstype as $edu )
                $lom['types'][] = $edu->educationals_type_string;
        // Intended audience
        foreach ( $resource->educational as $res )
            foreach ( $res->educationalsuserrole as $edu)
                $lom['audience'][] = $edu->educationals_userrole_string;
        // Set copyright info
        $lom['copyright']['has_copyright'] = $resource->right->right_copyright;
        $lom['copyright']['language'] = $resource->right->right_description;
        $lom['copyright']['description'] = $resource->right->right_description;

        // Adds basic information
        foreach ( $lom['texts'] as $key=>&$language )
        {
            $language['type_class'] = ( isset( $language['title'] ) ) ? 'human-translation' : 'automatic-translation';
            $language['type'] = ( isset( $language['title'] ) ) ? 'human' : 'automatic';
            if ( !isset( $language['title'] ) ) $language['title'] = '';
            if ( !isset( $language['description'] ) ) $language['description'] = '';
            if ( !isset( $language['keywords'] ) ) $language['keywords'] = array();
            $language['lang'] = $key;
        }
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
}