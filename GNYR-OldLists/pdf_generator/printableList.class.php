<?php
/**
	\file printableList.class.php
	
	\brief This file contains the exported interface for subclasses
*/
/**
	This is the interface, describing the exported functions.
*/
require_once ( dirname ( __FILE__ ).'/napdf.class.php' );
interface IPrintableList
{
	function AssemblePDF ();
	function OutputPDF ();
};

/**
	This is the base class for use by specialized printing classes.
*/
class printableList
{
	var	$page_x = 8.5;		///< The width, in inches, of each page
	var	$page_y = 11;		///< The height, in inches, of each page.
	var	$units = 'in';		///< The measurement units (inches)
	var	$orientation = 'P';	///< The orientation (portrait)
	/// These are the sort keys, for sorting the meetings before display
	var $sort_keys = array ();
	/// These are the parameters that we send over to the root server, in order to get our meetings.
	var $out_http_vars = array ();
	/// This contains the instance of napdf that we use to extract our data from the server, and to hold onto it.
	var	$napdf_instance = null;
	var $font = 'Times';	///< The font we'll use
	var $font_size = 9;		///< The font size we'll use
	
	/**
		\brief	The constructor for this class does a lot. It creates the instance of the napdf class, gets the data from the
		server, then sorts it. When the constructor is done, the data is ready to be assembled into a PDF.
		
		If the napdf object does not successfully get data from the server, then it is set to null.
	*/
	protected function __construct ( $in_http_vars,	///< The HTTP parameters we'd like to send to the server.
									 $in_lang_search = null	///< An array of language enums, used to extract the correct format codes.
									)
	{
		$this->napdf_instance =& napdf::MakeNAPDF ( $this->page_x, $this->page_y, $this->out_http_vars, $this->units, $this->orientation, $this->sort_keys, $in_lang_search );
		if ( !($this->napdf_instance instanceof napdf) )
			{
			$this->napdf_instance = null;
			}
	}
};
?>