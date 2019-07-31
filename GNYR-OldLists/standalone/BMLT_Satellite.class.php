<?php
/***********************************************************************/
/** 	\file	BMLT_satellite.class.php

	\version 1.5.4

	\brief	This is a class that implements a BMLT Satellite/Client server.
	
	This is a "SINGLETON" pattern class, which means that it allows only
	one instance of the class to be in existence, and all references to the
	class go to that instance.
	
	It handles communications with the root server, and outputs the appropriate
	XHTML through a couple of simple functions.
*/
/**************************************************************************/
/**
	\class	BMLT_Satellite
	
	\brief	This is the implementation of a standalone BMLT satellite server.
	
	It is meant to be instantiated in a simple PHP file, with a few calls to
	the BMLT_Satellite::Execute() function to deliver the XHTML to be displayed.
*/
class BMLT_Satellite
	{
	/***********************************************************************/
	/// This is static stuff that comprises the way the class is accessed.
	/***********************************************************************/

	/// Data Members
	static private	$bmlt_instance = null;	///< This will be the only instance of this class.

	/// Functions
	/***********************************************************************/
	/**
		\brief This is how clients will instantiate the BMLT. Either a new
		instance is created, or we get the current one.
	*/
	static function MakeBMLT (	$is_csv = false,		///< If true, then this object will be used for CSV data.
								$in_http_vars = null	///< These contain alternatives to the $_GET and/or $_POST parameters. Default is null.
							)
		{
		if ( !$in_http_vars )
			{
			$in_http_vars = array_merge_recursive ( $_GET, $_POST );
			}
		
		// If an instance does not already exist, we instantiate a new one.
		if ( !(self::$bmlt_instance instanceof BMLT_Satellite) )
			{
			// Include our configuration directives and variables.
			include_once ( dirname ( __FILE__ )."/config.inc" );
			
			if ( is_array ( $preset_service_bodies ) && count ( $preset_service_bodies ) )
				{
				// We do it this way, just to make sure we're adding to the correct place. The idea is to integrate smoothly into however the user is specifying.
				if ( is_array ( $in_http_vars ) && count ( $in_http_vars ) )
					{
					// We allow the user to override the preference
					if ( !isset ( $in_http_vars['preset_service_bodies'] ) )
						{
						$in_http_vars['preset_service_bodies'] = $preset_service_bodies;
						}
					}
				else
					{
					if ( !isset ( $_GET['preset_service_bodies'] ) && !isset ( $_POST['preset_service_bodies'] ) )
						{
						$in_http_vars = array_merge_recursive ( $_GET, $_POST );
						$in_http_vars['preset_service_bodies'] = $preset_service_bodies;
						}
					}
				}
			
			if ( !isset ( $in_http_vars['search_spec_map_center'] ) )
				{
				$in_http_vars['search_spec_map_center'] = "$map_center_latitude,$map_center_longitude,$map_zoom";
				}

			// When we create a new instance, we load it with our configuration.
			self::$bmlt_instance = new BMLT_Satellite ( $root_server_root, $gkey_my, $support_old_browsers, $bmlt_initial_view, $is_csv, $in_http_vars, $lang_enum );
			}
		
		return self::$bmlt_instance;
		}
	
	/***********************************************************************/
	/**
		\brief see if we are dealing with a mobile browser that uses a small screen and limited bandwidth.
		
		\returns a Boolean. True if the browser is one that should get the special version of our site.
	*/
	static function is_mobile ( $in_http_vars = null	///< The HTTP GET and POST variables. If not supplied, we try GET first, then POST.
								)
		{
		$ret = false;
		
		if ( !preg_match ( "|/pdf_generator/|", $_SERVER['REQUEST_URI'] ) ) // Make sure mobiles get PDF, as well.
		    {
            $ret = isset ( $in_http_vars['simulate_iphone'] ) || preg_match ( '/ipod/i', $_SERVER['HTTP_USER_AGENT'] ) || preg_match ( '/iphone/i', $_SERVER['HTTP_USER_AGENT'] );
            
            if ( !$ret )
                {
                $ret = isset ( $in_http_vars['simulate_android'] ) || preg_match ( '/android/i', $_SERVER['HTTP_USER_AGENT'] );
                }
        
            if ( !$ret )
                {
                $ret = isset ( $in_http_vars['simulate_blackberry'] ) || preg_match ( '/blackberry/i', $_SERVER['HTTP_USER_AGENT'] );
                }
        
            if ( !$ret )
                {
                $ret = isset ( $in_http_vars['simulate_opera_mini'] ) || preg_match ( "/opera\s+mini/i", $_SERVER['HTTP_USER_AGENT'] );
                }
            }
		
		return $ret;
		}
	
	/**
		\brief This is a function that returns the results of an HTTP call to a URI.
		It is a lot more secure than file_get_contents, but does the same thing.
		
		\returns a string, containing the response. Null if the call fails to get any data.
	*/
	static function call_curl ( $in_uri,				///< A string. The URI to call.
								$in_post = false,		///< If false, the transaction is a GET, not a POST. Default is true.
								&$http_status = null	///< Optional reference to a string. Returns the HTTP call status.
								)
		{
		$ret = null;
		// If the curl extension isn't loaded, we try one backdoor thing. Maybe we can use file_get_contents.
		if ( !extension_loaded ( 'curl' ) )
			{
			if ( ini_get ( 'allow_url_fopen' ) )
				{
				$ret = file_get_contents ( $in_uri );
				}
			}
		else
			{
			// Create a new cURL resource.
			$resource = curl_init();
			
			// If we will be POSTing this transaction, we split up the URI.
			if ( $in_post )
				{
				$spli = explode ( "?", $in_uri, 2 );
				
				if ( is_array ( $spli ) && count ( $spli ) )
					{
					$in_uri = $spli[0];
					$in_params = $spli[1];
					// Convert query string into an array using parse_str(). parse_str() will decode values along the way.
					parse_str($in_params, $temp);
					
					// Now rebuild the query string using http_build_query(). It will re-encode values along the way.
					// It will also take original query string params that have no value and appends a "=" to them
					// thus giving them and empty value.
					$in_params = http_build_query($temp);
				
					curl_setopt ( $resource, CURLOPT_POST, true );
					curl_setopt ( $resource, CURLOPT_POSTFIELDS, $in_params );
					}
				}
			
			// Set url to call.
			curl_setopt ( $resource, CURLOPT_URL, $in_uri );
			
			// Make curl_exec() function (see below) return requested content as a string (unless call fails).
			curl_setopt ( $resource, CURLOPT_RETURNTRANSFER, true );
			
			// By default, cURL prepends response headers to string returned from call to curl_exec().
			// You can control this with the below setting.
			// Setting it to false will remove headers from beginning of string.
			// If you WANT the headers, see the Yahoo documentation on how to parse with them from the string.
			curl_setopt ( $resource, CURLOPT_HEADER, false );
			
			// Allow  cURL to follow any 'location:' headers (redirection) sent by server (if needed set to true, else false- defaults to false anyway).
			// Disabled, because some servers disable this for security reasons.
//			curl_setopt ( $resource, CURLOPT_FOLLOWLOCATION, true );
			
			// Set maximum times to allow redirection (use only if needed as per above setting. 3 is sort of arbitrary here).
			curl_setopt ( $resource, CURLOPT_MAXREDIRS, 3 );
			
			// Set connection timeout in seconds (very good idea).
			curl_setopt ( $resource, CURLOPT_CONNECTTIMEOUT, 10 );
			
			// Direct cURL to send request header to server allowing compressed content to be returned and decompressed automatically (use only if needed).
			curl_setopt ( $resource, CURLOPT_ENCODING, 'gzip,deflate' );
			
			// Execute cURL call and return results in $content variable.
			$content = curl_exec ( $resource );
			
			// Check if curl_exec() call failed (returns false on failure) and handle failure.
			if ( $content === false )
				{
				// Cram as much info into the error message as possible.
				die ( '<pre>curl failure calling $in_uri, '.curl_error ( $resource )."\n".curl_errno ( $resource ).'</pre>' );
				}
			else
				{
				// Do what you want with returned content (e.g. HTML, XML, etc) here or AFTER curl_close() call below as it is stored in the $content variable.
			
				// You MIGHT want to get the HTTP status code returned by server (e.g. 200, 400, 500).
				// If that is the case then this is how to do it.
				$http_status = curl_getinfo ($resource, CURLINFO_HTTP_CODE );
				}
			
			// Close cURL and free resource.
			curl_close ( $resource );
			
			// Maybe echo $contents of $content variable here.
			if ( $content !== false )
				{
				$ret = $content;
				}
			}
		
		return $ret;
		}

	/***********************************************************************/
	/// All this stuff is dynamic stuff that applies directly to the instance.
	/***********************************************************************/
	
	/// Data members
	var	$root_server_root = '';			///< The root server URI.
	var	$root_server_uri = '';			///< The root server URI, with the API entrypoint added.
	var	$gkey = '';						///< The Google Maps API Key.
	var	$support_old_browsers = true;	///< true, if we will support older, non-JavaScript browsers.
	var	$bmlt_initial_view = '';		///< Specifies the initial Basic Search view ('text', 'map' or '', which is the root server decides).
	var	$http_vars = '';				///< This is the combined GET and POST HTTP parameters.
	var	$params = '';					///< This is a parameter list that is appended to URIs.
	var	$ajax_call = false;				///< true, if the object needs to execute an ajax call immediately.
	var	$lang_enum = null;				///< Set this to a desired language (If null, the server decides -null is default).
	var $csv_call = false;				///< If this is true, then this instance will be used for CSV. Default is false.
	
	/// Functions
	/***********************************************************************/
	/**
		\brief	We make the constructor private, so this class isn't instantiated on its own.
	*/
	private function __construct (	$in_root_server_root,		///< This is the root server main_server URI. Ignored if $in_csv_call is true.
									$in_gkey,					///< This is the Google Maps API Key to be used. Ignored if $in_csv_call is true.
									$in_support_old_browsers,	///< If this is true, then the 'supports_ajax' check will be made. Ignored if $in_csv_call is true.
									$in_bmlt_initial_view,		///< This can be 'text' or 'map'. It determines the initial view of the Basic Search. Ignored if $in_csv_call is true.
									$in_csv_call = false,		///< If this is true, then this instance will be used for CSV. Default is false.
									$in_http_vars = null,		///< These contain alternatives to the $_GET and/or $_POST parameters. Default is null.
									$in_lang_enum = null		///< Set this to a desired language (If null, the server decides -null is default).
								)
		{
		$this->http_vars = array ( $_GET, $_POST );
		if ( !isset ( $this->http_vars['advanced_search_mode'] ) || !$this->http_vars['advanced_search_mode'] )
			{
			unset ( $this->http_vars['result_type_advanced'] );
			}
		
		if ( is_array ( $in_http_vars ) && count ( $in_http_vars ) )
			{
			if ( !isset ( $in_http_vars['advanced_search_mode'] ) || !$in_http_vars['advanced_search_mode'] )
				{
				unset ( $in_http_vars['result_type_advanced'] );
				}

			foreach ( $in_http_vars as $key => $value )
				{
				if ( isset ( $key ) && !isset ( $this->http_vars[$key] ) )
					{
					if ( !isset ( $value ) )
						{
						$value = null;
						}
					
					$this->http_vars[$key] = $value;
					}
				}
			}
		
		// Set up our internal data members.
		$this->csv_call = $in_csv_call;
		$this->root_server_root = $in_root_server_root;
		$this->root_server_uri = $this->root_server_root.'client_interface/'.(($this->csv_call == true) ? 'csv' : 'xhtml').'/index.php';
		$this->gkey = $in_gkey;
		$this->support_old_browsers = $in_support_old_browsers;
		$this->bmlt_initial_view = $in_bmlt_initial_view;
		$this->ajax_call = false;
		$this->lang_enum = $in_lang_enum;
		
		if ( self::is_mobile ( $this->http_vars ) )
			{
			header ( 'Location: '.$this->root_server_root );
			}
		
		// If there is no particular call for a function, we default to the search form.
		if (	!(isset ( $this->http_vars['redirect_ajax'] ) && $this->http_vars['redirect_ajax'])
			&&	!$this->csv_call
			&&	!isset ( $this->http_vars['search_form'] )
			&&	!isset ( $this->http_vars['result_type_advanced'] )
			&&	!isset ( $this->http_vars['single_meeting_id'] )
			&&	!isset ( $this->http_vars['do_search'] )
			&&	!isset ( $this->http_vars['search_form'] )
			)
			{
			// Default to the search form
			$this->http_vars['search_form'] = true;
			}
		
		// These are basic settings for a satellite call.
		$this->http_vars['script_name'] = $_SERVER['SCRIPT_NAME'];
		$this->http_vars['satellite'] = $_SERVER['SCRIPT_NAME'];
		$this->http_vars['satellite_standalone'] = 1;
		
		// If we don't support old browsers, we assume that we can handle AJAX.
		if ( !$this->support_old_browsers )
			{
			$this->http_vars['supports_ajax'] = 'yes';
			$this->http_vars['no_ajax_check'] = 'yes';
			}
		else
			{
			// Otherwise, we make sure that the server does an AJAX check.
			unset ( $this->http_vars['no_ajax_check'] );
			}
		
		$this->http_vars['start_view'] = $this->bmlt_initial_view;

		$this->http_vars['gmap_key'] = $this->gkey;
		
		if ( isset ( $this->lang_enum ) && $this->lang_enum )
			{
			$this->http_vars['lang_enum'] = $this->lang_enum;
			}
		
		// We build a parameter list string to append to our cURL calls.
		$this->params = '';
		
		foreach ( $this->http_vars as $key => $value )
			{
			if ( $key != 'switcher' )	// We don't propagate switcher.
				{
				// If the value is an array, we handle it differently.
				if ( is_array ( $value ) )
					{
					foreach ( $value as $val )
						{
						$this->params .= '&'.urlencode ( $key );
						// If a nested array, well, we just join it with commas.
						if ( is_array ( $val ) )
							{
							$val = join ( ",", $val );
							}
						// The key needs the brackets to indicate an array value.
						$this->params .= "%5B%5D=". urlencode ( $val );
						}
					
					// Stop the process here.
					$key = null;
					}
				
				// If we have a key, we add that here.
				if ( isset ( $key ) )
					{
					$this->params .= '&'.urlencode ( $key );
					
					// We only add value if its called for.
					if ( $value )
						{
						$this->params .= "=". urlencode ( $value );
						}
					}
				}
			}
		
		// Okay, at this point, we're loaded for bear. We have our HTTP variables sorted out,
		// and a set of parameters ready to slap onto any outgoing URIs.
		
		// If we need to do an AJAX call, that has to be done right away. We actually just kill the whole shebang, right here.
		if ( isset ( $this->http_vars['redirect_ajax'] ) && $this->http_vars['redirect_ajax'] )
			{
			$this->ajax_call = true;
			die ( $this->Execute() );
			}
		elseif ( !$this->csv_call && isset ( $this->http_vars['result_type_advanced'] ) && $this->http_vars['result_type_advanced'] && (($this->http_vars['result_type_advanced'] == 'booklet') || ($this->http_vars['result_type_advanced'] == 'listprint')))
			{
			die ( $this->Execute() );
			}
		}
	
	/***********************************************************************/
	/**
		\brief Performs the function necessary to provide the relevant content.
		
		This is the meat of this little class. It needs to be called in order to
		fetch the relevant data from the root server, and output it to the browser.
		
		\returns a string, containing the XHTML to be displayed.
	*/
	function Execute ( $in_phase = null,		/**< A string, containing a particular execution phase.
													- 'head'
														This is a request to return the XHTML header stuff
														If the $this->csv_call data member is set, then this is ignored.
												
													- 'csv'
														This means that a speacial comma-separated-values call will be made.
														In this case, the $this->http_vars parameter will contain the necessary search criteria.
														If the $this->csv_call data member is set, then this is not necessary.
												*/
						$in_http_vars = null	///< These contain alternatives to the $_GET and/or $_POST parameters. Default is null.
					)
		{
		$content = '';

		if ( $this->csv_call && ('csv' != $in_phase) && ('csv_formats' != $in_phase) )
			{
			$in_phase = 'csv';
			}
		
		// If we have special instructions for the object, they are given here.
		if ( is_array ( $in_http_vars ) && count ( $in_http_vars ) )
			{
			if ( !isset ( $in_http_vars['advanced_search_mode'] ) || !$in_http_vars['advanced_search_mode'] )
				{
				unset ( $in_http_vars['result_type_advanced'] );
				}
			
			if ( isset ( $this->lang_enum ) && $this->lang_enum )
				{
				$this->http_vars['lang_enum'] = $this->lang_enum;
				}

			$this->http_vars = $in_http_vars;
			// We build a parameter list string to append to our cURL calls.
			$this->params = '';
			
			foreach ( $this->http_vars as $key => $value )
				{
				if ( $key != 'switcher' )	// We don't propagate switcher..
					{
					// If the value is an array, we handle it differently.
					if ( is_array ( $value ) )
						{
						foreach ( $value as $val )
							{
							$this->params .= '&'.urlencode ( $key );
							// If a nested array, well, we just join it with commas.
							if ( is_array ( $val ) )
								{
								$val = join ( ",", $val );
								}
							// The key needs the brackets to indicate an array value.
							$this->params .= "%5B%5D=". urlencode ( $val );
							}
						
						// Stop the process here.
						$key = null;
						}
					
					// If we have a key, we add that here.
					if ( isset ( $key ) )
						{
						$this->params .= '&'.urlencode ( $key );
						
						// We only add value if its called for.
						if ( $value )
							{
							$this->params .= "=". urlencode ( $value );
							}
						}
					}
				}
			}
		
		// If we are in an AJAX callback, we get the AJAX data right now. It will return JSON data, not XHTML.
		if ( $this->ajax_call )
			{
			$uri = "$this->root_server_uri?switcher=RedirectAJAX$this->params";
			$content = self::call_curl ( $uri );
			}
		else
			{
			switch ( $in_phase )
				{
				case 'csv':		// This is used for the special CSV call. If you don't know what it is, don't worry.
					// We simply call the CSV return directly, with the given parameters.
					$uri = "$this->root_server_uri?switcher=GetSearchResults$this->params";
					$content .= self::call_curl ( $uri );
				break;
				
				case 'csv_formats':		// This is used for the special CSV call. If you don't know what it is, don't worry.
					// We simply call the CSV return directly, with the given parameters.
					$uri = "$this->root_server_uri?switcher=GetFormats$this->params";
					$content .= self::call_curl ( $uri );
				break;
				
				case 'simple_formats':		// This is used for the special "simple" call for getting the formats. If you don't know what it is, don't worry.
					// We simply call the CSV return directly, with the given parameters.
					$uri = str_replace("/xhtml","/simple",$this->root_server_uri)."?switcher=GetFormats$this->params";
					$content .= self::call_curl ( $uri );
				break;
				
				case 'simple_meetings':		// This is used for the special "simple" call. If you don't know what it is, don't worry.
					// We simply call the CSV return directly, with the given parameters.
					$uri = str_replace("/xhtml","/simple",$this->root_server_uri)."?switcher=GetSearchResults$this->params";
					$content .= self::call_curl ( $uri );
				break;
				
				case 'doctype':	// This generates the appropriate DOCTYPE and <html> element for the implementation.
					$content = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
					// In the future, we'll be adding more doctypes here.
				break;
				
				case 'head':	// Sent out to the <head> element.
					// If we don't support old browsers, we style the noscript.
					if ( !$this->support_old_browsers )
						{
						$content = '<style type="text/css">.bmlt_no_js {text-align:center;font-weight:bold;font-size: large;color:red;}</style>';
						}
					
					$uri = "$this->root_server_uri?switcher=GetHeaderXHTML$this->params";
					$content .= self::call_curl ( $uri );
				break;
				
				default:	// Sent out to the <body> element.
					if ( isset ( $this->http_vars['result_type_advanced'] ) && ($this->http_vars['result_type_advanced'] == 'booklet') )
						{
						if ( $use_local_pdf_generator )
							{
							$uri = ".";
							}
						else
							{
							$uri = $this->root_server_root."local_server";
							}
					
						$uri .= "/pdf_generator/?list_type=booklet$this->params";
						
						header ( "Location: $uri" );
						die();
						}
					elseif ( isset ( $this->http_vars['result_type_advanced'] ) && ($this->http_vars['result_type_advanced'] == 'listprint') )
						{
						if ( $use_local_pdf_generator )
							{
							$uri = ".";
							}
						else
							{
							$uri = $this->root_server_root."local_server";
							}
					
						$uri .= "/pdf_generator/?list_type=listprint$this->params";
						
						header ( "Location: $uri" );
						die();
						}
					else
						{
						// If we don't support old browsers, we send out a noscript.
						if ( !$this->support_old_browsers )
							{
							$content = '<noscript class="no_js"><div>This Meeting Search will not work because your browser does not support JavaScript. However, you can use the <a href="'.htmlspecialchars ( $this->root_server_root ).'">main server</a>.</div></noscript>';
							}
						if ( isset ( $this->http_vars['single_meeting_id'] ) && $this->http_vars['single_meeting_id'] )
							{
							// If only one meeting is being displayed, we do so here.
							$uri = "$this->root_server_uri?switcher=GetOneMeeting$this->params";
							$content .= self::call_curl ( $uri );
							}
						elseif ( isset ( $this->http_vars['do_search'] ) )
							{
							// If a search was done, we display that here.
							$uri = "$this->root_server_uri?switcher=GetSearchResults$this->params";
							$content .= self::call_curl ( $uri );
							}
						if ( isset ( $this->http_vars['search_form'] ) )
							{
							// Just put up the search specification form.
							$uri = "$this->root_server_uri?switcher=GetSimpleSearchForm$this->params";
							$content .= self::call_curl ( $uri );
							}
						}
				break;
				}
			}
		
		return $content;
		}
	};
?>
