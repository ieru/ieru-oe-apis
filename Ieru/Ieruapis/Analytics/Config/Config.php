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
                  $this->_routes['GET'][] = array( '/translate/languages', 'controller'=>'AnalyticsAPI#get_languages' );
                  $this->_routes['GET'][] = array( '/translate/providers', 'controller'=>'AnalyticsAPI#get_providers' );

			$this->_routes['GET'][] = array( '/resources/:entry/rating',          'controller'=>'GrnetAdapter#get_rating' );
			$this->_routes['GET'][] = array( '/resources/:entry/ratings',         'controller'=>'GrnetAdapter#get_history' );
			$this->_routes['GET'][] = array( '/resources/:entry/ratings/reviews', 'controller'=>'GrnetAdapter#get_review_history' );
			$this->_routes['GET'][] = array( '/resources/:entry/tags',            'controller'=>'GrnetAdapter#get_tags' );

			$this->_routes['POST'][] = array( '/resources/:entry/rating',         'controller'=>'GrnetAdapter#add_rating' );
		}
		return $this->_routes;
	}

      /**
       * Returns the routes allowed in this API
       *
       * @return array
       */
      public function get_available_languages ()
      {
            return array( 'es', 'en' );
      }

	/**
	 * Returns the routes allowed in this API
	 *
	 * @return array
	 */
	public function get_translation_services ()
	{
		return array( 'microsoft', 'xerox' );
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
			'database'=>'ieru_organic_analytics',
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
			'database'=>'ieru_organic_oauth',
			'username'=>'root',
			'password'=>''
		);
	}

	/**
	 * Retuns an array with all the possible ISO 639-2 lang specification
	 *
	 * @return array An array with the 2 letter ISO language codes
	 */
	public function get_iso_lang ()
	{
		return array(
            'aa' => 'Afar',
            'ab' => 'Abkhaz',
            'ae' => 'Avestan',
            'af' => 'Afrikaans',
            'ak' => 'Akan',
            'am' => 'Amharic',
            'an' => 'Aragonese',
            'ar' => 'Arabic',
            'as' => 'Assamese',
            'av' => 'Avaric',
            'ay' => 'Aymara',
            'az' => 'Azerbaijani',
            'ba' => 'Bashkir',
            'be' => 'Belarusian',
            'bg' => 'Bulgarian',
            'bh' => 'Bihari',
            'bi' => 'Bislama',
            'bm' => 'Bambara',
            'bn' => 'Bengali',
            'bo' => 'Tibetan',
            'br' => 'Breton',
            'bs' => 'Bosnian',
            'ca' => 'Catalan',
            'ce' => 'Chechen',
            'ch' => 'Chamorro',
            'co' => 'Corsican',
            'cr' => 'Cree',
            'cs' => 'Czech',
            'cu' => 'Old Slavonic',
            'cv' => 'Chuvash',
            'cy' => 'Welsh',
            'da' => 'Danish',
            'de' => 'German',
            'dv' => 'Divehi',
            'dz' => 'Dzongkha',
            'ee' => 'Ewe',
            'el' => 'Greek',
            'en' => 'English',
            'eo' => 'Esperanto',
            'es' => 'Spanish',
            'et' => 'Estonian',
            'eu' => 'Basque',
            'fa' => 'Persian',
            'ff' => 'Fula',
            'fi' => 'Finnish',
            'fj' => 'Fijian',
            'fo' => 'Faroese',
            'fr' => 'French',
            'fy' => 'Western Frisian',
            'ga' => 'Irish',
            'gd' => 'Scottish Gaelic',
            'gl' => 'Galician',
            'gn' => 'GuaranÃ­',
            'gu' => 'Gujarati',
            'gv' => 'Manx',
            'ha' => 'Hausa',
            'he' => 'Hebrew',
            'hi' => 'Hindi',
            'ho' => 'Hiri Motu',
            'hr' => 'Croatian',
            'ht' => 'Haitian',
            'hu' => 'Hungarian',
            'hy' => 'Armenian',
            'hz' => 'Herero',
            'ia' => 'Interlingua',
            'id' => 'Indonesian',
            'ie' => 'Interlingue',
            'ig' => 'Igbo',
            'ii' => 'Nuosu',
            'ik' => 'Inupiaq',
            'io' => 'Ido',
            'is' => 'Icelandic',
            'it' => 'Italian',
            'iu' => 'Inuktitut',
            'ja' => 'Japanese (ja)',
            'jv' => 'Javanese (jv)',
            'ka' => 'Georgian',
            'kg' => 'Kongo',
            'ki' => 'Kikuyu',
            'kj' => 'Kwanyama',
            'kk' => 'Kazakh',
            'kl' => 'Kalaallisut',
            'km' => 'Khmer',
            'kn' => 'Kannada',
            'ko' => 'Korean',
            'kr' => 'Kanuri',
            'ks' => 'Kashmiri',
            'ku' => 'Kurdish',
            'kv' => 'Komi',
            'kw' => 'Cornish',
            'ky' => 'Kirghiz, Kyrgyz',
            'la' => 'Latin',
            'lb' => 'Luxembourgish',
            'lg' => 'Luganda',
            'li' => 'Limburgish',
            'ln' => 'Lingala',
            'lo' => 'Lao',
            'lt' => 'Lithuanian',
            'lu' => 'Luba-Katanga',
            'lv' => 'Latvian',
            'mg' => 'Malagasy',
            'mh' => 'Marshallese',
            'mi' => 'Maori',
            'mk' => 'Macedonian',
            'ml' => 'Malayalam',
            'mn' => 'Mongolian',
            'mr' => 'Marathi',
            'ms' => 'Malay',
            'mt' => 'Maltese',
            'my' => 'Burmese',
            'na' => 'Nauru',
            'nb' => 'Norwegian',
            'nd' => 'North Ndebele',
            'ne' => 'Nepali',
            'ng' => 'Ndonga',
            'nl' => 'Dutch',
            'nn' => 'Norwegian Nynorsk',
            'no' => 'Norwegian',
            'nr' => 'South Ndebele',
            'nv' => 'Navajo, Navaho',
            'ny' => 'Chichewa',
            'oc' => 'Occitan',
            'oj' => 'Ojibwe',
            'om' => 'Oromo',
            'or' => 'Oriya',
            'os' => 'Ossetian',
            'pa' => 'Panjabi',
            'pi' => 'Pali',
            'pl' => 'Polish',
            'ps' => 'Pashto, Pushto',
            'pt' => 'Portuguese',
            'qu' => 'Quechua',
            'rm' => 'Romansh',
            'rn' => 'Kirundi',
            'ro' => 'Romanian',
            'ru' => 'Russian',
            'rw' => 'Kinyarwanda',
            'sa' => 'Sanskrit',
            'sc' => 'Sardinian',
            'sd' => 'Sindhi',
            'se' => 'Northern Sami',
            'sg' => 'Sango',
            'si' => 'Sinhala',
            'sk' => 'Slovak',
            'sl' => 'Slovene',
            'sm' => 'Samoan',
            'sn' => 'Shona',
            'so' => 'Somali',
            'sq' => 'Albanian',
            'sr' => 'Serbian',
            'ss' => 'Swati',
            'st' => 'Southern Sotho',
            'su' => 'Sundanese',
            'sv' => 'Swedish',
            'sw' => 'Swahili',
            'ta' => 'Tamil',
            'te' => 'Telugu',
            'tg' => 'Tajik',
            'th' => 'Thai',
            'ti' => 'Tigrinya',
            'tk' => 'Turkmen',
            'tl' => 'Tagalog',
            'tn' => 'Tswana',
            'to' => 'Tonga',
            'tr' => 'Turkish',
            'ts' => 'Tsonga',
            'tt' => 'Tatar',
            'tw' => 'Twi',
            'ty' => 'Tahitian',
            'ug' => 'Uighur',
            'uk' => 'Ukrainian',
            'ur' => 'Urdu',
            'uz' => 'Uzbek',
            've' => 'Venda',
            'vi' => 'Vietnamese',
            'vo' => 'Volapak',
            'wa' => 'Walloon',
            'wo' => 'Wolof',
            'xh' => 'Xhosa',
            'yi' => 'Yiddish',
            'yo' => 'Yoruba',
            'za' => 'Zhuang',
            'zh' => 'Chinese',
            'zu' => 'Zulu',
        );
	}
}