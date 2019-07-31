<?php
	/**
		\file satellite_server/standalone/config.inc
		
		\brief This contains the basic configuration for the standalone BMLT Satellite Server.
		
		\version 1.5.3
	*/
	$root_server_root = "http://bmlt.newyorkna.org/main_server/";	///< This is URI to the root server (Default is our stable demonstration server).
	$gkey_my = "ABQIAAAABCC8PsaKPPEsC3k649kYPRRbaXL1ek5ixmK3SPbne69xsQMD3xQBVlDFbaiMVMvm6escOnPWLJGthQ";	///< This is the localhost Google API key. You need to get your own, and change it (Default is for magshare.net).
	$support_old_browsers = false;		///< If this is true, then we support older browsers, which adds an extra call to the file. Default is false.
	$bmlt_initial_view = '';			///< This is the initial view of the Basic Search. It can be 'text', 'map', 'advanced', 'advanced_text', 'advanced_map', or '', which means the root server decides ('advanced' is the same, but for the advanced screen). Default is ''.
	$preset_service_bodies = array ();	///< An array of preset Service body IDs, used to "precheck" the advanced tab Service bodies. The contents need to be integers, corresponding to the IDs of the Service Bodies.
	/// These are the settings for the center of the initial map view. The zoom is the Google Maps zoom.
	$map_center_latitude = 41.5;
	$map_center_longitude = -73.5;
	$map_zoom = 8;
	$lang_enum = null;					///< This is the language for this satellite.
	$use_local_pdf_generator = false;	///< If this is set to true, then there must be a local pdf_generator directory (used for custom PDFs).
	
	/// Debug stuff only. You can (and should) delete all this, and not worry.
	if ( preg_match ( '|localhost|', $_SERVER['SERVER_NAME'] ) )
		{
		$root_server_root = "http://localhost/magshare.org/public_html/projects/BMLT-Root-Server/main_server/";	///< In case of testing on a local machine, we use a local root server.
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
		}
	else
		{
		if ( preg_match ( '|/trunk/|', $_SERVER['REQUEST_URI'] ) )
			{
			$root_server_root = "http://bmlt.magshare.net/trunk/main_server/";	///< If we are in the trunk, then we use the trunk server.
			ini_set('display_errors', 1);
			ini_set('error_reporting', E_ALL);
			}
		
		if ( preg_match ( '|magshare\.org|', $_SERVER['SERVER_NAME'] ) )
			{
			$gkey_my = "ABQIAAAABCC8PsaKPPEsC3k649kYPRTayKsye0hTYG-iMuljzAHNM4JcxhSlV55ZKpjgC9b-QsLtlkYPMO6omg";	///< Our test server may be at magshare.org
			}
		}
?>