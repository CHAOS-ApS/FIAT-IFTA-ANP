<?php
/**
 * @package WP DKA
 * @version 1.0
 */

/**
 * Class that manages CHAOS data specific to
 * Dansk Kulturarv and registers attributes
 * for WPChaosObject
 */
class WPDKAObject {

	const DKA_SCHEMA_GUID = '00000000-0000-0000-0000-000063c30000';
	const DKA2_SCHEMA_GUID = '5906a41b-feae-48db-bfb7-714b3e105396';
	const DKA_CROWD_SCHEMA_GUID = '';
	const DKA_CROWD_LANGUAGE = 'da';
	const FREETEXT_LANGUAGE = 'da';
	public static $ALL_SCHEMA_GUIDS = array(self::DKA_SCHEMA_GUID, self::DKA2_SCHEMA_GUID);

	/**
	 * Construct
	 */
	public function __construct() {

		//add_action('admin_init',array(&$this,'check_chaosclient'));

		
			// Define the free-text search filter.
			$this->define_attribute_filters();
			
			// Define a filter for object creation.
			$this->define_object_construction_filters();

			add_filter('widgets_init',array(&$this,'register_widgets'));


	}

	const TYPE_VIDEO = 'video';
	const TYPE_AUDIO = 'audio';
	const TYPE_IMAGE = 'image';
	const TYPE_IMAGE_AUDIO = 'image-audio';
	const TYPE_UNKNOWN = 'unknown';

	/**
	 * Determine type of a CHAOS object based
	 * on the included file formats
	 * @param  WPChaosObject $object 
	 * @return string
	 */
	public static function determine_type($object) {

		$format_types = array();
		
		foreach($object->Files as $file) {
			//FormatID = 10 is thumbnai format. We do not want that here.
			if($file->FormatID != 10) {
				$format_types[$file->FormatType] = 1;
			}
			
		}

		//Video format
		if(isset($format_types['Video']))
			return self::TYPE_VIDEO;

		if(isset($format_types['Audio'])) {
			//Image audio format
			if(isset($format_types['Image']))
				return self::TYPE_IMAGE_AUDIO;
			//Audio format
			return self::TYPE_AUDIO;
		}
		
		//Image format
		if(isset($format_types['Image']))
			return self::TYPE_IMAGE;

		//Fallback
		return self::TYPE_UNKNOWN;
	}

	/**
	 * Define attributes to be used on a WPChaosObject
	 * with XML content
	 * @return void 
	 */
	public function define_attribute_filters() {
		// Registering namespaces.
		\CHAOS\Portal\Client\Data\Object::registerXMLNamespace('dka', 'http://www.danskkulturarv.dk/DKA.xsd');
		\CHAOS\Portal\Client\Data\Object::registerXMLNamespace('dka2', 'http://www.danskkulturarv.dk/DKA2.xsd');
		\CHAOS\Portal\Client\Data\Object::registerXMLNamespace('xhtml', 'http://www.w3.org/1999/xhtml');

		//object->title
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'title', function($value, $object) {
			return $value . $object->metadata(
				array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
				array('/dka2:DKA/dka2:Title/text()', '/DKA/Title/text()')
			);
		}, 10, 2);

		//object->organization
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'organization', function($value, $object) {

			$organizations = array(
				'The Royal Library: The National Library of Denmark and Copenhagen University Library' => array(
					'slug' => 'KB',
					'title' => 'Det Kongelige Bibliotek'
				)
			);

			$organization = $object->metadata(
					array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
					array('/dka2:DKA/dka2:Organization/text()', '/DKA/Organization/text()')
			);

			if(isset($organizations[$organization]))
				$organization = $organizations[$organization]['title'];

			return $value . $organization;
		}, 10, 2);

		//object->description
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'description', function($value, $object) {
			return $value . $object->metadata(
					array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
					array('/dka2:DKA/dka2:Description/text()', '/DKA/Description/text()')
			);
		}, 10, 2);

		//object->published
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'published', function($value, $object) {
			$time = $object->metadata(
					array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
					array('/dka2:DKA/dka2:FirstPublishedDate/text()', '/DKA/FirstPublishedDate/text()')
			);
			//Format date according to WordPress
			$time = date_i18n(get_option('date_format'),strtotime($time));
			return $value . $time;
		}, 10, 2);

		//object->type
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'type', function($value, $object) {
			return $value . WPDKAObject::determine_type($object);
		}, 10, 2);

		//object->thumbnail
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'thumbnail', function($value, $object) {

			foreach($object->Files as $file) {
				//FormatID = 10 is thumbnail format. This is what we want here
				if($file->FormatID == 10) {
					return $value . $file->URL;
				}
			}

			//Fallback
			return $value . 'http://placekitten.com/202/145';

		}, 10, 2);
	}
	
	public function define_object_construction_filters() {
		add_action(WPChaosObject::CHAOS_OBJECT_CONSTRUCTION_ACTION, function(WPChaosObject $object) {
			if(!$object->has_metadata(WPDKAObject::DKA_CROWD_SCHEMA_GUID)) {
				// The object has not been extended with the crowd matadata schema.
				$objectGUID = $object->GUID;
				$metadataXML = new SimpleXMLElement("<?xml version='1.0' encoding='UTF-8' standalone='yes'?><dkac:DKACrowd xmlns:dkac='http://www.danskkulturarv.dk/DKA.crowd.xsd' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'></dkac:DKACrowd>");
				//$metadataXML->registerXPathNamespace('dkac', 'http://www.danskkulturarv.dk/DKA.crowd.xsd');
				$metadataXML->addChild('Views', '0');
				$metadataXML->addChild('Shares', '0');
				$metadataXML->addChild('Likes', '0');
				$metadataXML->addChild('Ratings', '0');
				$metadataXML->addChild('AccumulatedRate', '0');
				$metadataXML->addChild('Slug', WPDKAObject::generateSlug($object));
				$metadataXML->addChild('Tags');
				
				// TODO: Set this metadata schema, when it's created in the service.
				//var_dump(htmlentities($metadataXML->asXML()));
				// TODO: Check that the XML validates against the schema.
				/*
				WPChaosClient::instance()->Metadata()->Set(
					$objectGUID,
					WPDKAObject::DKA_CROWD_SCHEMA_GUID,
					WPDKAObject::DKA_CROWD_LANGUAGE,
					null,
					$metadataXML
				);
				*/
			}
		}, 10, 1);
	}
	
	/**
	 * Generate a slug from a chaos object.
	 * @param \CHAOS\Portal\Client\Data\Object $object The object to generate the slug from.
	 * @return string The slug generated - prepended with a nummeric postfix to prevent douplicates.
	 */
	public static function generateSlug(\CHAOS\Portal\Client\Data\Object $object) {
		$title = apply_filters(WPChaosClient::OBJECT_FILTER_PREFIX.'title', "", $object);
		
		$postfix = 0;
		$slug_base = sanitize_title_with_dashes($title);
		
		// Check if this results in dublicates.
		do {
			if($postfix == 0) {
				$slug = $slug_base; // Not needed
			} else {
				$slug = "$slug_base-$postfix";
			}
			$postfix++; // Try the next
		} while(self::getObjectFromSlug($slug) != null); // Until no object is returned.
		
		return $slug;
	}
	
	/**
	 * Gets an object from the CHAOS Service from an alphanummeric, lowercase slug.
	 * @param string $slug The slug to search for.
	 * @throws \RuntimeException If an error occurs in the service.
	 * @return NULL|\CHAOS\Portal\Client\Data\Object The object matching the slug.
	 */
	public static function getObjectFromSlug($slug) {
		// TODO: Use this instead, when DKA-Slug is added to the index.
		// $response = WPChaosClient::instance()->Object()->Get("DKA-Slug:'$slug'");
		
		$response = WPChaosClient::instance()->Object()->GetSearchSchema($slug, self::DKA_CROWD_SCHEMA_GUID, self::DKA_CROWD_LANGUAGE, null, 0, 1);
		if(!$response->WasSuccess()) {
			throw new \RuntimeException("Couldn't get object from slug: ".$response->Error()->Message());
		} elseif (!$response->MCM()->WasSuccess()) {
			throw new \RuntimeException("Couldn't get object from slug: ".$response->MCM()->Error()->Message());
		} else {
			$count = $response->MCM()->TotalCount();
			if($count == 0) {
				return null;
			} elseif ($count > 1) {
				warn("CHAOS returned more than one ($count) object for this slug: ". htmlentities($slug));
			}
			$result = $response->MCM()->Results();
			return new \CHAOS\Portal\Client\Data\Object($result[0]);
		}
	}

	/**
	 * Register widgets in WordPress
	 * @return  void
	 */
	public function register_widgets() {
		register_widget( 'WPDKAObjectPlayerWidget' );
	}

}
//Instantiate
new WPDKAObject();

//eol