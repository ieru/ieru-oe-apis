<?php
/** 
 * Handles API requests for Analytics Service.
 *
 * @package     Analytics API
 * @version     1.2 - 2013-04-04 | 1.1 - 2013-02-18 | 1.0 - 2012-10-15
 * 
 * @author      David Baños Expósito
 */

namespace Ieru\Ieruapis\Import; 

use \Ieru\Restengine\Engine\Exception\APIException;
use \Ieru\Ieruapis\Import\Models\Lom;
use \Ieru\Ieruapis\Import\Models\General;
use \Ieru\Ieruapis\Import\Models\GeneralsTitle;
use \Ieru\Ieruapis\Import\Models\GeneralsLanguage;
use \Ieru\Ieruapis\Import\Models\GeneralsDescription;
use \Ieru\Ieruapis\Import\Models\GeneralsKeyword;
use \Ieru\Ieruapis\Import\Models\GeneralsKeywordsText;

class ImportAPI
{
    /**
     * Constructor
     */
    public function __construct ( $params, $config )
    {
        $this->_params = $params;
        $this->_config = $config;

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
     *
     */
    public function import ()
    {
    	// Load XML file
    	$file = $_SERVER['DOCUMENT_ROOT'].'/xml/resource.xml';
    	$xml = simplexml_load_file( $file );

    	// Create new LOM object
    	$lom = new Lom();
    	$lom->save();

    	// Parse General metadata
    	$general = new General();
    	$general->general_structure = (string)$xml->general->structure->value;
    	$general->general_structure_source = (string)$xml->general->structure->source;
    	$general->general_aggregation_level = (string)$xml->general->aggregationLevel->value;
    	$general->general_aggregation_level_source = (string)$xml->general->aggregationLevel->source;
    	$lom->general()->save( $general );

    	// Parse General Titles
    	foreach ( $xml->general->title->string as $desc )
    	{
	    	$gen_d = new GeneralsTitle();
	    	$gen_d->generals_title_string = $desc;
	    	$gen_d->generals_title_lang = $desc->attributes()['language'];
	    	$general->generalstitle()->save( $gen_d );
    	}

    	// Parse General languages
    	foreach ( $xml->general->language as $desc )
    	{
	    	$gen_d = new GeneralsLanguage();
	    	$gen_d->generals_language_lang = $desc;
	    	$general->generalslanguage()->save( $gen_d );
    	}

    	// Parse General Descriptions
    	foreach ( $xml->general->description->string as $desc )
    	{
	    	$gen_d = new GeneralsDescription();
	    	$gen_d->generals_description_string = $desc;
	    	$gen_d->generals_description_lang = $desc->attributes()['language'];
	    	$general->generalsdescription()->save( $gen_d );
    	}

    	// Parse General Keywords
    	foreach ( $xml->general->keyword as $keyword )
    	{
			// Create root keyword
	    	$genk = new GeneralsKeyword();
	    	$general->generalskeyword()->save($genk);
    		foreach ( $keyword as $lang )
    		{
    			$gen_d = new GeneralsKeywordsText();
		    	$gen_d->generals_keywords_text_string = $lang;
		    	$gen_d->generals_keywords_text_lang = $lang->attributes()['language'];
		    	$genk->generalskeywordtext()->save( $gen_d );
    		}
    	}

    	// Parse General Coverages
    	foreach ( $xml->general->coverage as $coverage )
    	{
			// Create root coverage
	    	$genk = new GeneralsCoverage();
	    	$general->generalscoverage()->save($genk);
    		foreach ( $coverage as $lang )
    		{
    			$gen_d = new GeneralsCoveragesText();
		    	$gen_d->generals_coverages_text_string = $lang;
		    	$gen_d->generals_coverages_text_lang = $lang->attributes()['language'];
		    	$genk->generalscoveragestext()->save( $gen_d );
    		}
    	}


    	return array( 'data'=>$lom->general->toArray() );
    }
}















