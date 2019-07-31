<?php
	/**
		\file satellite_server/standalone/index.php
		
		\brief This file demonstrates a "bare bones" BMLT Satellite Server implementation,
		using the BMLT_Satellite class.
		
		It is absurdly simple to use the BMLT_Satellite class to implement a standalone PHP
		BMLT satellite server.
	*/
	
	/* The first thing that you need to do is to load the class, and intercept any AJAX calls. */
	
	/* We fetch the file that contains the client class. */
	include ( dirname ( __FILE__ ).'/BMLT_Satellite.class.php' );
	
	/* Create the BMLT Satellite object. Die if it fails. */
	if ( !(($bmlt_instance = BMLT_Satellite::MakeBMLT()) instanceof BMLT_Satellite) )
		{
		die ( 'The BMLT object could not be created' );
		}
	
	/*	This is optional, but should be done if you plan to support mobile phones.
		If you don't do this, then you need to provide your own DOCTYPE and <html> element, like so:
		
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	*/
	echo ( $bmlt_instance->Execute ( 'doctype' ) );
?><head>
		<!-- This is the character encoding meta tag. You should always provide it for validation. -->
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		
		<!-- These should be provided for implicit inline scripting and styles. -->
		<meta http-equiv="Content-Script-Type" content="text/javascript" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		
		<!-- This is necessary for Google Maps. It tells IE 8 to act like IE7. -->
		<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
		
		<title>Standalone BMLT Satellite Server</title>
		
		<!-- These styles are simple "Wipe the slate clean" styles. -->
		<style type="text/css">
			/* <![CDATA[ */
			/**
				This is just some "blanket" CSS that sets things to a common margin and padding (none).
			*/
			*{ margin: 0; padding: 0 }
			
			/**
				This ensures that the map will fill the page in all browsers.
			*/
			body, html{ width:100%; height:100% }
			/**
				This restricts the map to a 640-pixel square. Adjust the height and width to your taste.
				Making them 100% should fill the container.
			*/
			#c_comdef_search_results_map_container_div
				{
					position:relative;
					width: 640px;
					height: 640px;
				}
			/* ]]> */
		</style>
		
		<!-- This is where all the head scripts and styles are obtained from the root server. -->
		<?php echo ( $bmlt_instance->Execute ( 'head' ) ); ?>
	</head>
	<body>
		<!-- This is where the body text is obtained from the server. -->
		<?php echo ( $bmlt_instance->Execute ( ) ); ?>
	</body>
</html>
