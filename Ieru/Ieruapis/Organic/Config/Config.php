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
			$this->_routes['GET'][]  = array( '/search',        'controller'=>'OrganicAPI#get_search' );
			$this->_routes['GET'][]  = array( '/login',         'controller'=>'OrganicAPI#login' );
			$this->_routes['GET'][]  = array( '/logout',        'controller'=>'OrganicAPI#logout' );
			$this->_routes['POST'][] = array( '/register',      'controller'=>'OrganicAPI#register' );
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

	/**
	 * Get the data for connecting with the database
	 *
	 * @return array The data needed for connecting with the IEEE LOM database
	 */
	public function get_db_info ()
	{
		return array( 
			'host'=>'localhost',
			'database'=>'lomsqldat',
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

      /**
       * Returns the IP of the server of the Analytics API
       *
       * @return string
       */
      public function get_analytics_server_ip ()
      {
            return 'http://lingua.dev';
      }
}