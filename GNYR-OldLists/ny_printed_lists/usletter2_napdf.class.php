<?php
/**
	\file tabloid_napdf.class.php
	
	\brief This file creates and dumps a tabloid meeting list in PDF form.
*/
ini_set('display_errors', 1);
ini_set('error_reporting', E_ERROR);
// Get the napdf class, which is used to fetch the data and construct the file.
require_once ( dirname ( __FILE__ ).'/../pdf_generator/printableList2.class.php' );
require_once ( dirname ( __FILE__ ).'/pdf_decls.php' );

/**
	\brief	This creates and manages an instance of the napdf class, and creates
	the PDF file.
*/
define ( 'PDF_MARGIN', 0.125 );

class usletter2_napdf extends printableList2
{
	/********************************************************************
		\brief	The constructor for this class does a lot. It creates the instance of the napdf class, gets the data from the
		server, then sorts it. When the constructor is done, the data is ready to be assembled into a PDF.
		
		If the napdf object does not successfully get data from the server, then it is set to null.
	*/
	function __construct ( 	$inRootURI,		///< The Root Server URI.
							$in_http_vars	///< The HTTP parameters we'd like to send to the server.
							)
	{
		$this->page_x = 8.5;			///< The width, in inches, of each page
        $this->page_y = 11;     ///< The height, in inches, of each page.
		$this->units = 'in';		///< The measurement units (inches)
		$this->font = 'Helvetica';	///< The font we'll use
		$this->orientation = 'L';	///< The orientation (landscape)

		parent::__construct ( $inRootURI, $in_http_vars );
	}
};
?>