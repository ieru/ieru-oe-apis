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
		    'database'  => 'ieru_organic_resources',
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
    public function check ()
    {
        $bad = 0;

        foreach ( glob( $_SERVER['DOCUMENT_ROOT'].'/xml_full/*/*.xml' ) as $file ) 
        {
            $name = preg_replace( '@/users/david/sites/github/ieru-api-server/xml_full@si', '', $file );

            $check = array(
                            'general.identifier',
                            'general.language',
                            'technical.location',
                        );

            libxml_use_internal_errors(true);
            $xml = simplexml_load_file( $file );
            if ( $xml !== false )
            {
                foreach ( $check as $field )
                {
                    $tags = explode( '.', $field );
                    if ( !isset( $xml->$tags[0]->$tags[1] ) )
                        echo "Resource without field: {$field} \t".$name."\t ".++$bad."\n";
                }
            }
            else
            {
                foreach(libxml_get_errors() as $error)
                {
                    //echo "Error: \t".$name."\t ".++$bad."\n";
                }
            }

        }
    }

    /**
     *
     */
    public function import ()
    {
        // Avoid problems with exceeding max time of execution of script
        set_time_limit(0);
        //gc_enable();
        //ini_set('memory_limit', '3000M');

    	// Load XML file
    	//$file = $_SERVER['DOCUMENT_ROOT'].'/xml/resource.xml';
        $parsed = 0;
        foreach ( glob( $_SERVER['DOCUMENT_ROOT'].'/xml_full/*/*.xml' ) as $file ) 
        //foreach ( glob( $_SERVER['DOCUMENT_ROOT'].'/xml/*.xml' ) as $file ) 
        {
            $name = preg_replace( '@/users/david/sites/github/ieru-api-server/xml_full@si', '', $file );

            if ( $finder = Lom::where('lom_original_file_name', '=', $name)->get()->toArray() )
            {
                echo "encontrado: ".$name."\n";
                continue;
            }

            echo $name;
            $xml = simplexml_load_file( $file );


        	// Create new LOM object
        	$lom = new Lom();
            $lom->lom_original_file_name = $name;
        	$lom->save();

        	/*------------------------------------------------------------------------------------------------
        	 * GENERAL
        	 *------------------------------------------------------------------------------------------------*/
        	// Parse General metadata
        	$general = new General();
        	$general->general_structure = (string)$xml->general->structure->value;
        	$general->general_structure_source = (string)$xml->general->structure->source;
        	$general->general_aggregation_level = (string)$xml->general->aggregationLevel->value;
        	$general->general_aggregation_level_source = (string)$xml->general->aggregationLevel->source;
        	$lom->general()->save( $general );

        	// Parse General Titles
        	if ( isset( $xml->general->title ) )
        	foreach ( $xml->general->title->string as $desc )
        	{
    	    	$gen_d = new GeneralsTitle();
    	    	$gen_d->generals_title_string = $desc;
    	    	$gen_d->generals_title_lang = $desc->attributes()['language'];
    	    	$general->generalstitle()->save( $gen_d );
        	}

        	// Parse General languages
        	if ( isset( $xml->general->language ) )
        	foreach ( $xml->general->language as $desc )
        	{
    	    	$gen_d = new GeneralsLanguage();
    	    	$gen_d->generals_language_lang = $desc;
    	    	$general->generalslanguage()->save( $gen_d );
        	}

        	// Parse General Descriptions
        	if ( isset( $xml->general->description ) )
        	foreach ( $xml->general->description as $descri )
        	{
                foreach ( $descri->string as $desc )
                {
        	    	$gen_d = new GeneralsDescription();
        	    	$gen_d->generals_description_string = $desc;
        	    	$gen_d->generals_description_lang = $desc->attributes()['language'];
        	    	$general->generalsdescription()->save( $gen_d );
                } 
        	}

        	// Parse General Keywords
        	if ( isset( $xml->general->keyword ) )
        	foreach ( @$xml->general->keyword as $keyword )
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
        	if ( isset( $xml->general->coverage ) )
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

        	// Parse General identifiers
        	if ( isset( $xml->general->identifier ) )
        	foreach ( $xml->general->identifier as $id )
        	{
    			$identifier = new Identifier();
    			$identifier->lom_id = $general->lom_id;
    	    	$identifier->identifier_catalog =@ $id->catalog;
    	    	$identifier->identifier_catalog_lang =@ $id->catalog->attributes()['language'];
    	    	$identifier->identifier_entry =@ $id->entry;
    	    	$identifier->identifier_entry_lang =@ $id->entry->attributes()['language'];
    	    	$general->identifier()->save( $identifier );
        	}

        	/*------------------------------------------------------------------------------------------------
        	 * LIFECYCLE
        	 *------------------------------------------------------------------------------------------------*/
        	// Parse lifecycle metadata
        	if ( isset( $xml->lifeCycle ) )
        	{
    	    	$life = new Lifecycle();
    	    	if ( isset( $xml->lifeCycle->status ) )
    	    	{
    		    	$life->lifecycle_status =@ $xml->lifeCycle->status->value;
    		    	$life->lifecycle_status_source =@ $xml->lifeCycle->status->source;
    	    	}
    	    	if ( isset( $xml->lifeCycle->version ) )
    	    	{
    		    	$life->lifecycle_version =@ $xml->lifeCycle->version->string;
    		    	$life->lifecycle_version_lang =@ $xml->lifeCycle->version->string->attributes()['language'];
    	    	}
    	    	$lom->lifecycle()->save( $life );
        	}
        	// Parse lifecycle contribute
        	if ( isset( $xml->lifeCycle->contribute ) )
        	foreach ( $xml->lifeCycle->contribute as $id )
        	{
        		// basic contribute info
    			$contribute = new Contribute();
    			$contribute->lom_id = $general->lom_id;
    	    	$contribute->contribute_role =@ $id->role->value;
    	    	$contribute->contribute_role_source =@ $id->role->source;
                if ( isset( $id->date->dateTime ) )
                {
        	    	$contribute->contribute_date =@ $id->date->dateTime;
        			$contribute->contribute_date_description =@ $id->date->string;
        	    	$contribute->contribute_date_description_lang =@ $id->date->string->attributes()['language'];
                }
    	    	$life->contribute()->save( $contribute );

    	    	// Contribute entities
    	    	foreach ( $id->entity as $e )
    	    	{
    		    	// contribute entities info
    		    	$entity = new ContributesEntity();
    		    	$entity->contributes_entity_string = $e;
    		    	$contribute->contributesentity()->save( $entity );
    	    	}
        	}

            /*------------------------------------------------------------------------------------------------
             * METAMETADATA
             *------------------------------------------------------------------------------------------------*/
            // Parse metametadatas
            $metadata = new Metametadata();
            if ( isset( $xml->metaMetadata->language ) )
                $metadata->metametadata_lang = $xml->metaMetadata->language;
            $lom->metametadata()->save( $metadata );

            // Parse metametadatas identifiers
            if ( isset( $xml->metaMetadata->identifier ) )
            foreach ( $xml->metaMetadata->identifier as $id )
            {
                $identifier = new Identifier();
                $identifier->lom_id = $metadata->lom_id;
                $identifier->identifier_catalog =@ $id->catalog;
                $identifier->identifier_catalog_lang =@ $id->catalog->attributes()['language'];
                $identifier->identifier_entry =@ $id->entry;
                $identifier->identifier_entry_lang =@ $id->entry->attributes()['language'];
                $metadata->identifier()->save( $identifier );
            }

            // Parse metametadatas schemas
            if ( isset( $xml->metaMetadata->metadataSchema ) )
            foreach ( $xml->metaMetadata->metadataSchema as $schema )
            {
                $s = new MetametadatasSchema();
                $s->metametadatas_schema_text =@ $schema;
                $metadata->metametadatasschema()->save( $s );
            }

            // Parse metametadatas contribute
            if ( isset( $xml->metaMetadata->contribute ) )
            foreach ( $xml->metaMetadata->contribute as $id )
            {
                // basic contribute info
                $contribute = new Contribute();
                $contribute->lom_id = $metadata->lom_id;
                $contribute->contribute_role =@ $id->role->value;
                $contribute->contribute_role_source =@ $id->role->source;
                if ( isset( $id->date->dateTime ) )
                {
                    $contribute->contribute_date =@ $id->date->dateTime;
                    $contribute->contribute_date_description =@ $id->date->string;
                    $contribute->contribute_date_description_lang =@ $id->date->string->attributes()['language'];
                }
                $metadata->contribute()->save( $contribute );

                // Contribute entities
                foreach ( $id->entity as $e )
                {
                    // contribute entities info
                    $entity = new ContributesEntity();
                    $entity->contributes_entity_string = $e;
                    $contribute->contributesentity()->save( $entity );
                }
            }

            /*------------------------------------------------------------------------------------------------
             * TECHNICAL
             *------------------------------------------------------------------------------------------------*/
            // Parse technical
            $technical = new Technical();
            if ( isset( $xml->technical->size ) )
                $technical->technical_size = $xml->technical->size;
            if ( isset( $xml->technical->duration ) )
                $technical->technical_duration = $xml->technical->duration->duration;
            $lom->technical()->save( $technical );

            // Parse technical formats
            if ( isset( $xml->technical->format ) )
            foreach ( $xml->technical->format as $format )
            {
                $entry = new TechnicalsFormat();
                $entry->technicals_format_text = $format;
                $technical->technicalsformat()->save( $entry );
            }

            // Parse technical locations
            if ( isset( $xml->technical->location ) )
            foreach ( $xml->technical->location as $location )
            {
                $entry = new TechnicalsLocation();
                $entry->technicals_location_text = $location;
                $technical->technicalslocation()->save( $entry );
            }

            // Parse technical installation remarks
            if ( isset( $xml->technical->installationRemarks ) AND $xml->technical->installationRemarks->string != '' )
            foreach ( $xml->technical->installationRemarks as $installation )
            {
                $entry = new TechnicalsInstallationremark();
                $entry->technicals_installationremark_string =@ $installation->string;
                $entry->technicals_installationremark_lang =@ $installation->string->attributes()['language'];
                $technical->technicalsinstallationremark()->save( $entry );
            }

            // Parse technical other platform requirements
            if ( isset( $xml->technical->otherPlatformRequirements ) AND $xml->technical->otherPlatformRequirements->string != '' )
            foreach ( $xml->technical->otherPlatformRequirements as $requirement )
            {
                $entry = new TechnicalsOtherplatformrequirement();
                $entry->technicals_otherplatformrequirement_string =@ $requirement->string;
                $entry->technicals_otherplatformrequirement_lang =@ $requirement->string->attributes()['language'];
                $technical->technicalsotherplatformrequirement()->save( $entry );
            }

            // parse technical requirements
            if ( isset( $xml->technical->requirement ) )
            foreach ( $xml->technical->requirement as $requirement )
            {
                $entry = new Requirement();
                $technical->requirement()->save( $entry );

                foreach ( $requirement->orComposite as $or )
                {
                    $ent = new Orcomposite();
                    $ent->orcomposite_type = $or->type->value;
                    $ent->orcomposite_type_source = $or->type->source;
                    $ent->orcomposite_name = $or->name->value;
                    $ent->orcomposite_name_source = $or->name->source;
                    $ent->orcomposite_minimumversion = $or->minimumVersion;
                    $ent->orcomposite_maximumversion = $or->maximumVersion;
                    $entry->orcomposite()->save( $ent );
                }
            }

            /*------------------------------------------------------------------------------------------------
             * EDUCATIONAL
             *------------------------------------------------------------------------------------------------*/
            if ( isset( $xml->educational ) )
            {
                foreach ( $xml->educational as $educational )
                {
                    $resource = new Educational();
                    // Interactivity Level
                    $resource->educational_interactivitylevel = $educational->interactivityLevel->value;
                    $resource->educational_interactivitylevel_source = $educational->interactivityLevel->source;
                    // Interactivity Type
                    $resource->educational_interactivitytype = $educational->interactivityType->value;
                    $resource->educational_interactivitytype_source = $educational->interactivityType->source;
                    // Difficulty
                    if ( isset( $educational->difficulty ) )
                    {
                        $resource->educational_difficulty = $educational->difficulty->value;
                        $resource->educational_difficulty_source = $educational->difficulty->source;
                    }
                    // semanticDensity
                    if ( isset( $educational->semanticDensity ) )
                    {
                        $resource->educational_semanticdensity = $educational->semanticDensity->value;
                        $resource->educational_semanticdensity_source = $educational->semanticDensity->source;
                    }
                    // Learning time
                    if ( isset( $educational->typicalLearningTime ) )
                    {
                        $resource->educational_typicallearningtime = $educational->typicalLearningTime->duration;
                    }
                    $lom->educational()->save( $resource );

                    // Language
                    foreach ( $educational->language as $learningtype )
                    {
                        $type = new EducationalsLanguage();
                        $type->educationals_language_string = $learningtype;
                        $resource->educationalstype()->save( $type );
                    }

                    // Learning resource type
                    foreach ( $educational->learningResourceType as $learningtype )
                    {
                        $type = new EducationalsType();
                        $type->educationals_type_string = $learningtype->value;
                        $type->educationals_type_source = $learningtype->source;
                        $resource->educationalstype()->save( $type );
                    }

                    // Intended end user role
                    foreach ( $educational->intendedEndUserRole as $learningtype )
                    {
                        $type = new EducationalsUserrole();
                        $type->educationals_userrole_string = $learningtype->value;
                        $type->educationals_userrole_source = $learningtype->source;
                        $resource->educationalstype()->save( $type );
                    }

                    // Context
                    foreach ( $educational->context as $learningtype )
                    {
                        $type = new EducationalsContext();
                        $type->educationals_context_string = $learningtype->value;
                        $type->educationals_context_source = $learningtype->source;
                        $resource->educationalstype()->save( $type );
                    }

                    // Typical age range
                    foreach ( $educational->typicalAgeRange as $learningtype )
                    {
                        $type = new EducationalsTypicalagerange();
                        $type->educationals_typicalagerange_string = $learningtype->string;
                        $type->educationals_typicalagerange_lang = $learningtype->string->attributes()['language'];
                        $resource->educationalstype()->save( $type );
                    }
                    // Typical age range
                    foreach ( $educational->description as $learningtype )
                    {
                        $type = new EducationalsDescription();
                        $type->educationals_description_string = $learningtype->string;
                        $type->educationals_description_lang = $learningtype->string->attributes()['language'];
                        $resource->educationalstype()->save( $type );
                    }
                }
            }

            /*------------------------------------------------------------------------------------------------
             * RIGHTS
             *------------------------------------------------------------------------------------------------*/
            if ( isset( $xml->rights ) )
            {
                $rights = new Right();
                if ( $xml->rights->copyrightAndOtherRestrictions->value )
                {
                    $rights->right_copyright = $xml->rights->copyrightAndOtherRestrictions->value;
                    $rights->right_copyright_source = $xml->rights->copyrightAndOtherRestrictions->source;
                }
                if ( isset( $xml->rights->cost ) )
                {
                    $rights->right_cost = $xml->rights->cost->value;
                    $rights->right_cost_source = $xml->rights->cost->source;
                }
                if ( isset( $xml->rights->description ) )
                {
                    $rights->right_description = $xml->rights->description->string;
                    $rights->right_description_lang = $xml->rights->description->string->attributes()['language'];
                }
                $lom->right()->save( $rights );
            }

            /*------------------------------------------------------------------------------------------------
             * RELATION
             *------------------------------------------------------------------------------------------------*/
            if ( isset( $xml->relation ) )
            {
                foreach ( $xml->relation as $r )
                {
                    $relation = new Relation();
                    // Set KIND
                    if ( isset( $r->kind ) )
                    {
                        $relation->relation_kind =@ $r->kind->value;
                        $relation->relation_kind_source =@ $r->kind->source;
                    }
                    $lom->relation()->save( $relation );
                    // Set RESOURCES
                    if ( isset( $r->resource ) )
                    {
                        foreach ( $r->resource as $r )
                        {
                            $resource = new Resource();
                            // Set resource
                            if ( $r->description->string )
                            {
                                $resource->resource_description = $r->description->string;
                                $resource->resource_description_lang = $r->description->string->attributes()['language'];
                            }
                            $relation->resource()->save( $resource );
                            // Set identifiers
                            foreach ( $r->identifier as $i )
                            {
                                $identifier = new Identifier();
                                $identifier->lom_id = $lom->lom_id;
                                $identifier->identifier_catalog =@ $i->catalog;
                                $identifier->identifier_entry =@ $i->entry;
                                $resource->identifier()->save( $identifier );
                            }
                        }
                    }
                }
            }

            /*------------------------------------------------------------------------------------------------
             * ANNOTATION
             *------------------------------------------------------------------------------------------------*/
            if ( isset( $xml->annotation ) )
            {
                foreach ( $xml->annotation as $annotation )
                {
                    $arr = new Annotation();
                    if ( isset( $annotation->entity ) )
                        $arr->annotation_entity = $annotation->entity;
                    if ( isset( $annotation->date ) )
                        $arr->annotation_date = $annotation->date->dateTime;
                    $arr->annotation_description = $annotation->description->string;
                    $arr->annotation_description_lang = $annotation->description->string->attributes()['language'];
                    $lom->annotation()->save( $arr );
                }
            }

            /*------------------------------------------------------------------------------------------------
             * CLASSIFICATION
             *------------------------------------------------------------------------------------------------*/
            if ( isset( $xml->classification ) )
            {
                foreach ( $xml->classification as $class )
                {
                    $arr = new Classification();
                    // Purpose
                    if ( isset( $class->purpose->value ) )
                        $arr->classification_purpose = $class->purpose->value;
                    if ( isset( $class->purpose->value ) )
                        $arr->classification_purpose_source = $class->purpose->source;

                    $lom->classification()->save( $arr );

                    // Taxonpath
                    foreach ( $class->taxonPath as $tp )
                    {
                        $tax = new Taxonpath();
                        $tax->taxonpath_source =@ $tp->source->string;
                        $tax->taxonpath_source_lang =@ $tp->source->string->attributes()['language'];
                        $arr->taxonpath()->save( $tax );

                        // Taxon
                        foreach ( $tp->taxon as $t )
                        {
                            $ta = new Taxon();
                            $ta->taxon_id_string = $t->id;
                            if ( $t->entry->string )
                            {
                                $ta->taxon_entry =@ $t->entry->string;
                                $ta->taxon_entry_lang =@ $t->entry->string->attributes()['language'];
                            }
                            $tax->taxon()->save( $ta );
                        }
                    }
                }
            }

            echo " \t|| no. ", ++$parsed, " \t|| Lomid: ", $general->lom_id, "\n";
        }

        /*------------------------------------------------------------------------------------------------
         * END OF SCRIPT
         *------------------------------------------------------------------------------------------------*/
        //$this->retrieve_resource( $general->lom_id );
    	return array( 'success'=>true, 'message'=>'Resource imported.' );
    }

    public function get ()
    {
        header('Content-type: application/xml');
        $this->retrieve_resource( $this->_params['id'] );
        die();
    }

    /**
     *
     */
    private function retrieve_resource ( $id )
    {
    	$l = Lom::find( $id );

    	/*------------------------------------------------------------------------------------------------
    	 * GENERAL
    	 *------------------------------------------------------------------------------------------------*/
        // general identifiers
        foreach ( $l->general->identifier as $identifier )
            $r['general']['identifier'][] = array( 'catalog'=>$identifier->identifier_catalog, 'entry'=>$identifier->identifier_entry );
    	// general title
    	foreach ( $l->general->generalstitle as $text )
    		$r['general']['title']['string'][] = array( '@attributes'=>array( 'language'=>$text['generals_title_lang'] ), '@value'=>$text['generals_title_string'] );
    	// general language
    	foreach ( $l->general->generalslanguage as $text )
    		$r['general']['language'][] = array( '@value'=>$text->generals_language_lang );
    	// general description
    	foreach ( $l->general->generalsdescription as $text )
    		$r['general']['description']['string'][] = array( '@attributes'=>array( 'language'=>$text['generals_description_lang'] ), '@value'=>$text['generals_description_string'] );
    	// general keyword
    	foreach ( $l->general->generalskeyword as $keyword )
    	{
    		$k = array();
    		foreach ( $keyword->generalskeywordtext as $text )
    			$k['string'][] = array( '@attributes'=>array( 'language'=>$text['generals_keywords_text_lang'] ), '@value'=>$text['generals_keywords_text_string'] );
    		$r['general']['keyword'][] = $k;
    	}
    	// general coverage
    	foreach ( $l->general->generalscoverage as $coverage )
    	{
    		$k = array();
    		foreach ( $coverage->generalscoveragestext as $text )
    			$k['string'][] = array( '@attributes'=>array( 'language'=>$text['generals_coverages_text_lang'] ), '@value'=>$text['generals_coverages_text_string'] );
    		$r['general']['coverage'][] = $k;
    	}
    	// general structure
    	$r['general']['structure'] = array( 'value'=>$l->general->general_structure, 'source'=>$l->general->general_structure_source );
    	// general aggregation level
    	$r['general']['aggregationLevel'] = array( 'value'=>$l->general->general_aggregation_level, 'source'=>$l->general->general_aggregation_level_source );

    	/*------------------------------------------------------------------------------------------------
    	 * LIFECYCLE
    	 *------------------------------------------------------------------------------------------------*/
        // lifecycle version
        if ( isset( $l->lifecycle->lifecycle_version_lang ) )
        {
            $k['string'] = array( '@attributes'=>array( 'language'=>$l->lifecycle->lifecycle_version_lang ), '@value'=>$l->lifecycle->lifecycle_version );
            $r['lifeCycle']['version'][] =@ $k;
        }
        // lifecycle status
        if ( isset( $l->lifecycle->lifecycle_status ) )
            $r['lifeCycle']['status'] =@ array( 'value'=>$l->lifecycle->lifecycle_status, 'source'=>$l->lifecycle->lifecycle_status_source );
        // lifecycle contributes
        foreach ( $l->lifecycle->contribute as $contribute )
        {
            $k = array();
            $k['role'] = array( 'source'=>$contribute->contribute_role_source, 'value'=>$contribute->contribute_role );
            $k['date'] = array( 'dateTime'=>$contribute->contribute_date );
            foreach ( $contribute->contributesentity as $text )
                $k['entity'][] = array( '@cdata'=>$text['contributes_entity_string'] );
            $r['lifeCycle']['contribute'][] = $k;
        }

        /*------------------------------------------------------------------------------------------------
         * METAMETADATA
         *------------------------------------------------------------------------------------------------*/
        // Metadata language
        if ( isset( $l->metametadata->metametadata_lang ) )
            $r['metaMetadata']['language'] = $l->metametadata->metametadata_lang;
        // metametadata identifiers
        foreach ( $l->metametadata->identifier as $identifier )
            $r['metaMetadata']['identifier'][] = array( 'catalog'=>$identifier->identifier_catalog, 'entry'=>$identifier->identifier_entry );
        // metametadata contributes
        foreach ( $l->metametadata->contribute as $contribute )
        {
            $k = array();
            $k['role'] = array( 'source'=>$contribute->contribute_role_source, 'value'=>$contribute->contribute_role );
            $k['date'] = array( 'dateTime'=>$contribute->contribute_date );
            foreach ( $contribute->contributesentity as $text )
                $k['entity'][] = array( '@cdata'=>$text['contributes_entity_string'] );
            $r['metaMetadata']['contribute'][] = $k;
        }
        // metametadata schemas
        foreach ( $l->metametadata->metametadatasschema as $schema )
            $r['metaMetadata']['metadataSchema'][] = $schema->metametadatas_schema_text;

        /*------------------------------------------------------------------------------------------------
         * TECHNICAL
         *------------------------------------------------------------------------------------------------*/
        foreach ( $l->technical->technicalsformat as $format )
            $r['technical']['format'][] = array( '@value'=>$format->technicals_format_text );
        foreach ( $l->technical->technicalslocation as $location )
            $r['technical']['location'][] = array( '@value'=>$location->technicals_location_text );
        if ( isset( $l->technical->technical_duration ) )
            $r['technical']['duration']['duration'] = $l->technical->technical_duration;
        if ( isset( $l->technical->technical_size ) )
            $r['technical']['size'] = $l->technical->technical_size;
        foreach ( $l->technical->technicalsotherplatformrequirement as $requirement )
        {
            $k = array();
            $k['string'][] = array( '@attributes'=>array( 'language'=>$requirement->technicals_otherplatformrequirement_lang ), '@value'=>$requirement->technicals_otherplatformrequirement_string );
            $r['technical']['otherPlatformRequirements'][] = $k;
        }
        foreach ( $l->technical->technicalsinstallationremark as $installation )
        {
            $k = array();
            $k['string'] = array( '@attributes'=>array( 'language'=>$installation->technicals_installationremark_lang ), '@value'=>$installation->technicals_installationremark_string );
            $r['technical']['installationRemark'][] = $k;
        }
        foreach ( $l->technical->requirement as $requirement )
        {
            $k = array();
            foreach ( $requirement->orcomposite as $or )
            {
                $k['type'] = array( 'source'=>$or['orcomposite_type_source'], 'value'=>$or['orcomposite_type'] );
                $k['name'] = array( 'source'=>$or['orcomposite_name_source'], 'value'=>$or['orcomposite_name'] );
                $k['minimumVersion'] = $or['orcomposite_minimumversion'];
                $k['maximumVersion'] = $or['orcomposite_maximumversion'];
                $r['technical']['requirement']['orComposite'][] = $k;
            }
        }

        /*------------------------------------------------------------------------------------------------
         * EDUCATIONAL
         *------------------------------------------------------------------------------------------------*/
        foreach ( $l->educational as $educational )
        {
            $k = array();

            if ( $educational->educational_interactivitytype )
            {
                $k['interactivityType']['source'] = $educational->educational_interactivitytype_source;
                $k['interactivityType']['value'] = $educational->educational_interactivitytype;
            }

            if ( $educational->educational_interactivitylevel )
            {
                $k['interactivityLevel']['source'] = $educational->educational_interactivitylevel_source;
                $k['interactivityLevel']['value'] = $educational->educational_interactivitylevel;
            }

            if ( $educational->educational_difficulty )
            {
                $k['difficulty']['source'] = $educational->educational_difficulty_source;
                $k['difficulty']['value'] = $educational->educational_difficulty;
            }

            if ( $educational->educational_semanticdensity )
            {
                $k['semanticDensity']['source'] = $educational->educational_semanticdensity_source;
                $k['semanticDensity']['value'] = $educational->educational_semanticdensity;
            }

            if ( $educational->educational_typicallearningtime )
            {
                $k['typicalLearningTime']['duration'] = $educational->educational_typicallearningtime;
            }

            foreach ( $educational->educationalsuserrole as $e )
            {
                $edu = array();
                $edu['source'] = $e->educationals_userrole_source;
                $edu['value'] = $e->educationals_userrole_string;
                $k['intendedEndUserRole'][] = $edu;
            }

            foreach ( $educational->educationalslanguage as $e )
            {
                $edu = array();
                $edu['@value'] = $e->educationals_language_string;
                $k['language'][] = $edu;
            }

            foreach ( $educational->educationalstype as $e )
            {
                $edu = array();
                $edu['source'] = $e->educationals_type_source;
                $edu['value'] = $e->educationals_type_string;
                $k['learningResourceType'][] = $edu;
            }

            foreach ( $educational->educationalscontext as $e )
            {
                $edu = array();
                $edu['source'] = $e->educationals_context_source;
                $edu['value'] = $e->educationals_context_string;
                $k['context'][] = $edu;
            }

            foreach ( $educational->educationalstypicalagerange as $e )
            {
                $edu = array();
                $edu['string']['@value'] = $e->educationals_typicalagerange_string;
                $edu['string']['@attributes']['language'] = $e->educationals_typicalagerange_lang;
                $k['typicalAgeRange'][] = $edu;
            }

            foreach ( $educational->educationalsdescription as $e )
            {
                $edu = array();
                $edu['string']['@value'] = $e->educationals_description_string;
                $edu['string']['@attributes']['language'] = $e->educationals_description_lang;
                $k['description'][] = $edu;
            }

            $r['educational'][] = $k;
        }

        /*------------------------------------------------------------------------------------------------
         * RIGHTS
         *------------------------------------------------------------------------------------------------*/
        if ( $l->right->right_copyright )
            $r['rights']['copyrightAndOtherRestrictions'] =@ array( 'source'=>$l->right->right_copyright_source, 'value'=>$l->right->right_copyright );
        if ( $l->right->right_cost )
            $r['rights']['cost'] =@ array( 'source'=>$l->right->right_cost_source, 'value'=>$l->right->right_cost );
        if ( $l->right->right_description )
            $r['rights']['description']['string'][] =@ array( '@attributes'=>array( 'language'=>$l->right->right_description_lang ), '@value'=>$l->right->right_description );

        /*------------------------------------------------------------------------------------------------
         * RELATIONS
         *------------------------------------------------------------------------------------------------*/
        foreach ( $l->relation as $relation )
        {
            $k = array();

            // Kind
            $k['kind']['source'] = $relation->relation_kind_source;
            $k['kind']['value'] = $relation->relation_kind;
            // Description if exists
            if ( $relation->resource->resource_description )
            {
                $k['resource']['description']['string']['@value'] =@ $relation->resource->resource_description;
                $k['resource']['description']['string']['@attributes']['language'] =@ $relation->resource->resource_description_lang;
            }
            // Identifiers
            foreach ( $relation->resource->identifier as $identifier )
                $k['resource']['identifier'][] = array( 'catalog'=>$identifier->identifier_catalog, 'entry'=>$identifier->identifier_entry );

            $r['relation'][] = $k;
        }

        /*------------------------------------------------------------------------------------------------
         * ANNOTATIONS
         *------------------------------------------------------------------------------------------------*/
        foreach ( $l->annotation as $annotation )
        {
            $k = array();
            if ( $annotation->annotation_entity )
                $k['entity']['@cdata'] = $annotation->annotation_entity;
            if ( $annotation->annotation_date )
                $k['date']['dateTime'] = $annotation->annotation_date;
            $k['description']['string']['@value'] = $annotation->annotation_description;
            $k['description']['string']['@attributes']['language'] = $annotation->annotation_description_lang;
            $r['annotation'][] = $k;
        }

        /*------------------------------------------------------------------------------------------------
         * CLASSIFICATION
         *------------------------------------------------------------------------------------------------*/
        foreach ( $l->classification as $class )
        {
            $k = array();
            if ( $class->classification_purpose )
            {
                $k['purpose']['value'] = $class->classification_purpose;
                if ( $class->classification_purpose_source )
                    $k['purpose']['source'] = $class->classification_purpose_source;
            }
            foreach ( $class->taxonpath as $taxonp )
            {
                $tp = array();
                $tp['source']['string']['@value'] =@ $taxonp->taxonpath_source; 
                if ( $taxonp->taxonpath_source_lang )
                    $tp['source']['string']['@attributes']['language'] =@ $taxonp->taxonpath_source_lang;
                foreach ( $taxonp->taxon as $t )
                {
                    $tx = array();
                    $tp['taxon']['id'] = $t->taxon_id_string;
                    if ( $t->taxon_entry )
                    {
                        $tp['taxon']['entry']['string']['@value'] =@ $t->taxon_entry;
                        if ( $t->taxon_entry_lang )
                            $tp['taxon']['entry']['string']['@attributes']['language'] =@ $t->taxon_entry_lang;
                    }
                }
                $k['taxonPath'][] = $tp;
            }
            $r['classification'][] = $k;
        }

        /*------------------------------------------------------------------------------------------------
         * PRINT XML
         *------------------------------------------------------------------------------------------------*/
		$xml = Array2XML::createXML('lom', $r );
		echo $xml->saveXML();

    	//return $r;
    }
}

/**
 * Array2XML: A class to convert array in PHP to XML
 * It also takes into account attributes names unlike SimpleXML in PHP
 * It returns the XML in form of DOMDocument class for further manipulation.
 * It throws exception if the tag name or attribute name has illegal chars.
 *
 * Author : Lalit Patel
 * Website: http://www.lalit.org/lab/convert-php-array-to-xml-with-attributes
 * License: Apache License 2.0
 *          http://www.apache.org/licenses/LICENSE-2.0
 * Version: 0.1 (10 July 2011)
 * Version: 0.2 (16 August 2011)
 *          - replaced htmlentities() with htmlspecialchars() (Thanks to Liel Dulev)
 *          - fixed a edge case where root node has a false/null/0 value. (Thanks to Liel Dulev)
 * Version: 0.3 (22 August 2011)
 *          - fixed tag sanitize regex which didn't allow tagnames with single character.
 * Version: 0.4 (18 September 2011)
 *          - Added support for CDATA section using @cdata instead of @value.
 * Version: 0.5 (07 December 2011)
 *          - Changed logic to check numeric array indices not starting from 0.
 * Version: 0.6 (04 March 2012)
 *          - Code now doesn't @cdata to be placed in an empty array
 * Version: 0.7 (24 March 2012)
 *          - Reverted to version 0.5
 * Version: 0.8 (02 May 2012)
 *          - Removed htmlspecialchars() before adding to text node or attributes.
 *
 * Usage:
 *       $xml = Array2XML::createXML('root_node_name', $php_array);
 *       echo $xml->saveXML();
 */

class Array2XML {

    private static $xml = null;
	private static $encoding = 'UTF-8';

    /**
     * Initialize the root XML node [optional]
     * @param $version
     * @param $encoding
     * @param $format_output
     */
    public static function init($version = '1.0', $encoding = 'UTF-8', $format_output = true) {
        self::$xml = new \DomDocument($version, $encoding);
        self::$xml->formatOutput = $format_output;
		self::$encoding = $encoding;
    }

    /**
     * Convert an Array to XML
     * @param string $node_name - name of the root node to be converted
     * @param array $arr - aray to be converterd
     * @return DomDocument
     */
    public static function &createXML($node_name, $arr=array()) {
        $xml = self::getXMLRoot();
        $xml->appendChild(self::convert($node_name, $arr));

        self::$xml = null;    // clear the xml node in the class for 2nd time use.
        return $xml;
    }

    /**
     * Convert an Array to XML
     * @param string $node_name - name of the root node to be converted
     * @param array $arr - aray to be converterd
     * @return DOMNode
     */
    private static function &convert($node_name, $arr=array()) {

        //print_arr($node_name);
        $xml = self::getXMLRoot();
        $node = $xml->createElement($node_name);

        if(is_array($arr)){
            // get the attributes first.;
            if(isset($arr['@attributes'])) {
                foreach($arr['@attributes'] as $key => $value) {
                    if(!self::isValidTagName($key)) {
                        throw new \Exception('[Array2XML] Illegal character in attribute name. attribute: '.$key.' in node: '.$node_name);
                    }
                    $node->setAttribute($key, self::bool2str($value));
                }
                unset($arr['@attributes']); //remove the key from the array once done.
            }

            // check if it has a value stored in @value, if yes store the value and return
            // else check if its directly stored as string
            if(isset($arr['@value'])) {
                $node->appendChild($xml->createTextNode(self::bool2str($arr['@value'])));
                unset($arr['@value']);    //remove the key from the array once done.
                //return from recursion, as a note with value cannot have child nodes.
                return $node;
            } else if(isset($arr['@cdata'])) {
                $node->appendChild($xml->createCDATASection(self::bool2str($arr['@cdata'])));
                unset($arr['@cdata']);    //remove the key from the array once done.
                //return from recursion, as a note with cdata cannot have child nodes.
                return $node;
            }
        }

        //create subnodes using recursion
        if(is_array($arr)){
            // recurse to get the node for that key
            foreach($arr as $key=>$value){
                if(!self::isValidTagName($key)) {
                    throw new \Exception('[Array2XML] Illegal character in tag name. tag: '.$key.' in node: '.$node_name);
                }
                if(is_array($value) && is_numeric(key($value))) {
                    // MORE THAN ONE NODE OF ITS KIND;
                    // if the new array is numeric index, means it is array of nodes of the same kind
                    // it should follow the parent key name
                    foreach($value as $k=>$v){
                        $node->appendChild(self::convert($key, $v));
                    }
                } else {
                    // ONLY ONE NODE OF ITS KIND
                    $node->appendChild(self::convert($key, $value));
                }
                unset($arr[$key]); //remove the key from the array once done.
            }
        }

        // after we are done with all the keys in the array (if it is one)
        // we check if it has any text value, if yes, append it.
        if(!is_array($arr)) {
            $node->appendChild($xml->createTextNode(self::bool2str($arr)));
        }

        return $node;
    }

    /*
     * Get the root XML node, if there isn't one, create it.
     */
    private static function getXMLRoot(){
        if(empty(self::$xml)) {
            self::init();
        }
        return self::$xml;
    }

    /*
     * Get string representation of boolean value
     */
    private static function bool2str($v){
        //convert boolean to text value.
        $v = $v === true ? 'true' : $v;
        $v = $v === false ? 'false' : $v;
        return $v;
    }

    /*
     * Check if the tag name or attribute name contains illegal characters
     * Ref: http://www.w3.org/TR/xml/#sec-common-syn
     */
    private static function isValidTagName($tag){
        $pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';
        return preg_match($pattern, $tag, $matches) && $matches[0] == $tag;
    }
}