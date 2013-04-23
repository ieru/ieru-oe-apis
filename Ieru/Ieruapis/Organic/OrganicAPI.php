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
        if ( !$resources )
        {
            $results = array( 'success'=>false, 'errcode'=>100, 'message'=>'No results found.' );
        }
        # Resources retrieved from Celi
        else
        {
            # Get the metadata information of the resource
            $records =& $this->_get_lom_data_of_resources( $resources['data']['resources'], $this->_params['lang'] );
            if ( count( $records ) > 0 )
            {
                # Parses the facets for filtering the results
                $results['success'] = true;
                $results['message'] = 'Search results retrieved from Celi Service. ';
                $results['total']   = $resources['data']['total_records'];
                $results['pages']   = ceil( $resources['data']['total_records'] / $this->_params['limit'] );
                $results['records'] =& $records;
                $this->_parse_facets( $resources, $results['filters'], $this->_params['lang'] );
                foreach ( $results['records'] as $key => &$value )
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
                    WHERE string.FK_general=? AND string.FK_title is not NULL AND strings.FK_description is not NULL 
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
                $temp['texts'][$fetched['info_lang']]['lang'] = $fetched['info_lang'];
                $temp['texts'][$fetched['info_lang']]['type_class'] = 'human-translation';
                $temp['texts'][$fetched['info_lang']]['type'] = 'human';
                $temp['texts'][$fetched['info_lang']]['title'] = $fetched['title'];
                $temp['texts'][$fetched['info_lang']]['description'] = $fetched['description'];
                @$temp['texts'][$fetched['info_lang']]['keywords'] = $keytemp[$fetched['info_lang']];
            }
            # Add automatic translations from $this->_autolang
            foreach ( $this->_autolang as $autolang )
            {
                if ( !array_key_exists( $autolang, $temp['texts'] ) )
                {
                    $temp['texts'][$autolang]['lang'] = $autolang;
                    $temp['texts'][$autolang]['type_class'] = 'automatic-translation';
                    $temp['texts'][$autolang]['type'] = 'automatic';
                    $temp['texts'][$autolang]['title'] = '';
                    $temp['texts'][$autolang]['description'] = '';
                    $temp['texts'][$autolang]['keywords'] = '';
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
    private function & _get_lom_data_of_resources ( &$uris, $language )
    {
        $results = array();

        // ÑAPA ALERT
        $napa_lang = $this->_config->get_resources_langs();
        // ÑAPA ALERT        

        # Loop the uris for getting the local LOM resource info
        if ( $uris )
        {
            foreach ( $uris as $uri ) 
            {
                try 
                {
                    # Picks up the resource in both english (always must be in english) and the user language. Will return one or two rows.
                    $sql = 'SELECT  string.Text as title, strings.Text as description, technical.format, identifier.entry_metametadata as entry, 
                                    agerange.Text as age_range, identifier.entry as resource, string.language as info_lang,
                                    general.language as language, general.language as res_lang, string.FK_general as id, technical.location as location
                            FROM general
                            INNER JOIN identifier           ON general.FK_lom           = identifier.FK_general
                            INNER JOIN string               ON string.FK_general        = identifier.FK_general
                            INNER JOIN technical            ON identifier.FK_general    = technical.FK_lom
                            INNER JOIN string as strings    ON string.FK_general        = strings.FK_general
                            INNER JOIN string as agerange   ON string.FK_general        = agerange.FK_typicalAgeRange 
                            WHERE ( identifier.entry = ? OR identifier.entry_metametadata = ? OR identifier.entry = ? OR identifier.entry_metametadata = ?) 
                                AND string.language = strings.language
                            GROUP BY string.language';

                    $stmt = $this->_db->prepare( $sql );

                    if ( count( $uri['resource'] ) == 1 )
                        $stmt->execute( array( trim( $uri['resource'][0] ), trim( $uri['resource'][0] ), trim( $uri['resource'][0] ), trim( $uri['resource'][0] ) ) );
                    else
                        $stmt->execute( array( trim( $uri['resource'][0] ), trim( $uri['resource'][1] ), trim( $uri['resource'][1] ), trim( $uri['resource'][0] ) ) );
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
                    $stmt->execute( array( $fetches[0]['id'] ) );
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
                        $temp['texts'][$fetched['info_lang']]['lang'] = $fetched['info_lang'];
                        $temp['texts'][$fetched['info_lang']]['type_class'] = 'human-translation';
                       @$temp['texts'][$fetched['info_lang']]['type'] = 'human';
                        $temp['texts'][$fetched['info_lang']]['title'] = $fetched['title'];
                        $temp['texts'][$fetched['info_lang']]['description'] = $fetched['description'];
                       @$temp['texts'][$fetched['info_lang']]['keywords'] = $keytemp[$fetched['info_lang']];
                    }
                    # Add automatic language translations
                    foreach ( $this->_autolang as $autolang )
                    {
                        if ( !array_key_exists( $autolang, $temp['texts'] ) )
                        {
                            $temp['texts'][$autolang]['lang'] = $autolang;
                            $temp['texts'][$autolang]['type_class'] = 'automatic-translation';
                            $temp['texts'][$autolang]['type'] = 'automatic';
                            $temp['texts'][$autolang]['title'] = '';
                            $temp['texts'][$autolang]['description'] = '';
                            $temp['texts'][$autolang]['keywords'] = '';
                        }
                    }
                    ksort( $temp['texts'] );
                    unset( $temp['title'], $temp['description'], $temp['keyword'] );
                    #$temp['entry_link'] = str_replace( '/', '@', $temp['entry'] );
                    // ÑAPA ALERT
                    if ( array_key_exists( $temp['location'], $napa_lang ) )
                        $temp['napa_langs'] = $napa_lang[$temp['location']];
                    else
                        $temp['napa_langs'] = array( $temp['language'] );
                    // ÑAPA ALERT
                    $results[] = $temp;
                }

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
            $results['success'] = true;
            $results['message'] = 'No results found.';
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
        curl_setopt( $ch, CURLOPT_TIMEOUT, 7 );
        #curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $post_data ) );
        $data = curl_exec( $ch );
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
        curl_setopt( $ch, CURLOPT_TIMEOUT, 7 );
        $data = curl_exec( $ch );
        curl_close( $ch );
        return $data;
    }
}
/*
{
  "responseHeader":{
    "guessedLanguage":"es",
    "translation_nl":"vissen",
    "translation_en":"seize fish",
    "translation_el":"Î±ÏÏ€Î¬Î¶Ï‰ ÏˆÎ±ÏÎµÏÏ‰ Î±Î»Î¹ÎµÏÏ‰",
    "translation_lv":"konfiscÄ“t pieÄ·erties satvert",
    "translation_pl":"chwytaÄ‡ konfiskowaÄ‡ Å‚owiÄ‡",
    "translation_it":"afferrare prendere pescare",
    "translation_fr":"avaler pÃªcher prendre",
    "translation_tr":"gaspetmek kavramak tutmak",
    "translation_hu":"elkoboz halÃ¡szik megfog",
    "translation_et":"anastama kinni rabama konfiskeerima",
    "translation_de":"fangen fassen",
    "translation_sv":"gripa",
    "translation_es":"pescar",
    "translation_pt":"pescar agarrar confiscar",
    "status":0,
    "QTime":563,
    "params":{
      "explainOther":"",
      "facet":"true",
      "fl":"general_identifier,score",
      "indent":"on",
      "start":"0",
      "q":"pescado",
      "hl.fl":"",
      "facet.field":["educationalContext",
        "language",
        "technicalFormat",
        "educationalRole"],
      "wt":"json",
      "fq":"*:*",
      "version":"2.2",
      "rows":"10"}},
  "response":{"numFound":331,"start":0,"maxScore":0.0232714,"docs":[
      {
        "general_identifier":["24761","http://prodinra.inra.fr/record/33159"],
        "score":0.0232714},
      {
        "general_identifier":["25622","http://prodinra.inra.fr/record/91502"],
        "score":0.015876405},
      {
        "general_identifier":["25384","http://prodinra.inra.fr/record/181400"],
        "score":0.014427877},
      {
        "general_identifier":["11292","http://www.ruma.org.uk/"],
        "score":0.013628369},
      {
        "general_identifier":["25598","http://prodinra.inra.fr/record/133600"],
        "score":0.012403673},
      {
        "general_identifier":["24328","http://prodinra.inra.fr/record/161665"],
        "score":0.012403673},
      {
        "general_identifier":["24299","http://prodinra.inra.fr/record/125328"],
        "score":0.011914171},
      {
        "general_identifier":["24113","http://prodinra.inra.fr/record/53545"],
        "score":0.011420921},
      {
        "general_identifier":["23990","http://prodinra.inra.fr/record/21484"],
        "score":0.011420921},
      {
        "general_identifier":["24004","http://prodinra.inra.fr/record/128097"],
        "score":0.010900196}]
  },
  "facet_counts":{
    "facet_queries":{},
    "facet_fields":{
      "educationalContext":[
        "higher education",176,
        "vocational education",75,
        "policy making",26,
        "compulsory education",25,
        "continuing education",17,
        "Higher education",6,
        "professional development",5,
        "other",4,
        "Compulsory education",3,
        "library",3,
        "Continuing education",1,
        "Vocational education",1,
        "distance education",1,
        "Continuous Formation",0,
        "Distance education",0,
        "Educational administration",0,
        "Higher Education",0,
        "Other",0,
        "Policy making",0,
        "Primary Education",0,
        "Professional Formation",0,
        "Professional development",0,
        "Secondary Education",0,
        "Technical School First Cycle",0,
        "Technical School Second Cycle",0,
        "University First Cycle",0,
        "University Postgraduate",0,
        "University Second Cycle",0,
        "Vocational Training",0,
        "educational administration",0,
        "pre-school",0,
        "special education",0],
      "language":[
        "en",223,
        "fr",104,
        "or",7,
        "de",6,
        "es",6,
        "no",1,
        "ar",0,
        "cs",0,
        "cy",0,
        "da",0,
        "el",0,
        "et",0,
        "fi",0,
        "hi",0,
        "hu",0,
        "it",0,
        "ja",0,
        "kn",0,
        "lt",0,
        "lv",0,
        "nl",0,
        "pl",0,
        "pt",0,
        "ro",0,
        "ru",0,
        "sk",0,
        "sv",0,
        "te",0,
        "tr",0,
        "tw",0,
        "zh",0],
      "technicalFormat":[
        "application/pdf",198,
        "text/html",50,
        "audio/mp3",3,
        "image/jpeg",3,
        "XML",1,
        "application/x-shockwave-flash",1,
        "Application",0,
        "Image",0,
        "Multipart",0,
        "Text",0,
        "Video",0,
        "application/msword",0,
        "application/octet-stream",0,
        "application/ppt",0,
        "application/vnd.ms-excel",0,
        "application/vnd.ms-powerpoint",0,
        "application/vnd.ms-word",0,
        "application/zip",0,
        "audio/mpeg",0,
        "image/gif",0,
        "image/png",0,
        "text/plain",0,
        "video/avi",0,
        "video/mp4",0,
        "video/mpeg",0,
        "video/quicktime",0,
        "video/x-ms-wmv",0],
      "educationalRole":[
        "learner",257,
        "teacher",24,
        "manager",13,
        "other",12,
        "counsellor",7,
        "author",3,
        "parent",2,
        "Author",0,
        "Learner",0,
        "Manager",0,
        "Other",0,
        "Teacher",0,
        "counselor",0]},
    "facet_dates":{},
    "facet_ranges":{}}}


    */