<?php
/**
	\file printableList.class.php
	
	\brief This file contains the exported interface for subclasses
*/
	define ( '_PDF_FORMAT_KEY', 'Format Legend' );
	define ( '_PDF_PRINT_MARGIN', 0.125 );
	define ( "_PDF_LIST_NAME", "Name" );
    define ( "_PDF_LIST_PHONE", "Phone #" );

/**
	This is the interface, describing the exported functions.
*/
require_once ( dirname ( __FILE__ ).'/napdf2.class.php' );
interface IPrintableList
{
	function AssemblePDF ();
	function OutputPDF ();
};

/**
	This is the base class for use by specialized printing classes.
*/
class printableList2
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
	var $weekday_names = array ( "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday" );
	var	$pos = array ( 'start' => 1, 'end' => '', 'count' => 0, 'y' => 0, 'weekday' => 1 );
	var $formats = 0;
	
	/**
		\brief	The constructor for this class does a lot. It creates the instance of the napdf class, gets the data from the
		server, then sorts it. When the constructor is done, the data is ready to be assembled into a PDF.
		
		If the napdf object does not successfully get data from the server, then it is set to null.
	*/
	protected function __construct ( 	$inRootURI,		///< The Root Server URI.
										$in_http_vars,	///< The HTTP parameters we'd like to send to the server.
									 	$in_lang_search = null	///< An array of language enums, used to extract the correct format codes.
									)
	{
		$this->napdf_instance =& napdf2::MakeNAPDF ( $inRootURI, $this->page_x, $this->page_y, $this->out_http_vars, $this->units, $this->orientation, $this->sort_keys, $in_lang_search );
		if ( !($this->napdf_instance instanceof napdf2) )
			{
			$this->napdf_instance = null;
			}
	}

	/*************************** INTERFACE FUNCTIONS ***************************/
	/********************************************************************
	*/
	function DrawFormats ( $left, $y, $right, $bottom, $formats, $useDescription = true, $twoColumns = false )
	{
        function sortFormats ( $a, $b )
        {            
            return ($a['key_string'] < $b['key_string']) ? -1 : (($a['key_string'] > $b['key_string']) ? 1 : 0);
        }
        
        usort ( $formats, 'sortFormats' );

		$fontFamily = $this->napdf_instance->FontFamily;
		$font_size = $this->font_size;
		
		$headerString = _PDF_FORMAT_KEY;

		$this->napdf_instance->SetFont ( $fontFamily, 'B', $font_size - 2 );
		$stringWidth = $this->napdf_instance->GetStringWidth ( $headerString );
		
		$cellleft = (($right + $left) - $stringWidth) / 2;
		
        $this->napdf_instance->SetFillColor ( 0 );
        $this->napdf_instance->SetTextColor ( 255 );

		$this->napdf_instance->Rect ( $left, $y, ($right - $left), 0.18, "F" );

		$y += 0.08;

		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $headerString );

        $this->napdf_instance->SetFillColor ( 255 );
        $this->napdf_instance->SetTextColor ( 0 );

		$y += _PDF_PRINT_MARGIN;
		
		$count = count ( $formats );
		$fSize = $this->font_size - 2;
		
		$this->napdf_instance->SetY ( $y );

		$column_width = $right - $left;
		$column_width /= ($twoColumns ? 2.0 : 1.0);
		$left_margin = $left;
		$first_right_column_index = $twoColumns ? intval ( ($count + 1) / 2 ) : -1;
		$index = 0;
		$maxY = 0;

		foreach ( $formats as $format )
			{
			if ( $index++ == $first_right_column_index )
				{
				$left_margin = $left + $column_width;
				$this->napdf_instance->SetY ( $y );
				}
			$this->napdf_instance->SetFont ( $fontFamily, 'B', $fSize );
			$this->napdf_instance->SetLeftMargin ( $left_margin );
			$str = $format['key_string'];
			$this->napdf_instance->SetX ( $left_margin );
			$this->napdf_instance->Cell ( 0, 0.13, $str );
			$this->napdf_instance->SetFont ( $fontFamily, '', $fSize );
			$str = $format[($useDescription ? 'description_string' : 'name_string')];
			$this->napdf_instance->SetLeftMargin ( $left_margin + (_PDF_PRINT_MARGIN * 2.5) );
			$this->napdf_instance->SetX ( $left_margin + (_PDF_PRINT_MARGIN * 2.5) );
			$this->napdf_instance->MultiCell ( $column_width - (_PDF_PRINT_MARGIN * 2.5), 0.13, $str );
			$this->napdf_instance->SetY ( $this->napdf_instance->GetY ( ) + 0.01 );
			$maxY = max ( $maxY, $this->napdf_instance->GetY ( ) );
			}
		
		$this->napdf_instance->SetY ( $maxY );
    }
	
    
	/********************************************************************
	*/
	function DrawPhoneList ( $left, $top, $right, $bottom )
	{
		$y = $top;

		$y += 0.25;
		
		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->font_size - 1.5;
        $this->napdf_instance->SetFillColor ( 255 );
        $this->napdf_instance->SetTextColor ( 0 );
	    $this->napdf_instance->SetDrawColor ( 0 );

		$cellleft = $left;
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$st_width = $this->napdf_instance->GetStringWidth ( _PDF_LIST_NAME );
		$this->napdf_instance->Cell ( 0, 0, _PDF_LIST_NAME );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_LIST_PHONE );
		$this->napdf_instance->SetXY ( $right - $stringWidth, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_LIST_PHONE );
		$y += _PDF_PRINT_MARGIN;
        		
		$this->napdf_instance->SetFont ( $fontFamily, '', $fontSize );

		$this->napdf_instance->SetLineWidth ( 0.02 );
		$this->napdf_instance->Line ( $left + 0.0625, $y, $right, $y );

		while ( $y < ($bottom - 0.25) )
			{
			$y += 0.3;
			$this->napdf_instance->Line ( $left + 0.0625, $y, $right, $y );
			}
	}

	/********************************************************************
	*/
	function GetParsedHTMLFile ( $inURI )
	{
		$ret = array();
		$na_dom = new DOMDocument;
		if ( $na_dom )
			{
			if ( @$na_dom->loadHTML($this->napdf_instance->call_curl ( $inURI )) )
				{
				$div_list = $na_dom->getElementsByTagName ( "div" );
				if ( $div_list && $div_list->length )
					{
					for ( $i = 0; $i < $div_list->length; $i++ )
						{
						$the_item = $div_list->item($i);
						if ( $the_item )
							{
							$retObject = array('header' => '', 'body' => array());
							$header_list = $the_item->getElementsByTagName ( "h1" );
							if ( $header_list && $header_list->length )
								{
								$retObject['header'] = $header_list->item(0)->nodeValue;
								}
							$body_list = $the_item->getElementsByTagName ( "p" );
							if ( $body_list && $body_list->length )
								{
								for ( $i2 = 0; $i2 < $body_list->length; $i2++ )
									{
									$bodyItem = $body_list->item($i2);
									array_push ( $retObject['body'], $bodyItem->nodeValue );
									}
								}

							array_push ( $ret, $retObject );
							}
						}
					}
				}
			}

		return $ret;
	}
	
	/********************************************************************
	*/
	function DrawListPage ( $left, $top, $right, $bottom )
	{
		include_once ( dirname ( __FILE__ ).'/pdf_decls.php' );

		$meetings =& $this->napdf_instance->meeting_data;
		
		$count_max = count ( $meetings );
		
		$this->napdf_instance->SetFont ( $this->font, '', $this->font_size - 5 );
		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->font_size - 1.5;
		
		$column_width = $right - $left;
				
		$this->napdf_instance->SetXY ( $left, $top );
		
		$bottom = $this->napdf_instance->h - _PDF_PRINT_MARGIN;
		$right = $left + $column_width;
		
		$heading_height = 9;
		$height = ($heading_height/72) + 0.01;
		$gap2 = 0.02;
		
		$fSize = $fontSize / 70;
		$fSizeSmall = ($fontSize - 1) / 70;
	
		$height_one_meeting = ($fSize * 6) + $gap2;
		
		$y_offset = $bottom - $fSize;
		
		$current_day = " ";
		
		$extra_height = $height + 0.05;
			
		$this->pos['y'] = $top;
		$watermark_pos = 1.0;
		
		if ( $this->pos['start'] )
			{
			$this->pos['count'] = 0;
			}
		
		while ( !$this->pos['end'] && (($this->pos['y'] + $height_one_meeting + $extra_height + ($fSizeSmall * 2)) < ($y_offset - 0.1)) )
			{
			$extra = 0;
			$contd = "";
			$desc = '';
			
			$meeting = $meetings[intval($this->pos['count'])];
			$this->napdf_instance->SetLeftMargin ( $left );
			
			if ( $this->pos['start'] || (isset ( $meeting['weekday_tinyint'] ) && ($current_day != $meeting['weekday_tinyint']) ) )
				{
				$this->napdf_instance->SetFillColor ( 0 );
				$this->napdf_instance->SetTextColor ( 255 );
				if ( $this->pos['start'] )
					{
					$this->pos['start'] = "";
					}
				else
					{
					if ( ($this->pos['y'] == $top) && ($this->pos['weekday'] == $meeting['weekday_tinyint']) )
						{
						$contd = _PDF_CONTD;
						}
					}
				
				if ( isset ( $meeting['weekday_tinyint'] ) && ($current_day != $meeting['weekday_tinyint']) )
					{
					$this->pos['weekday'] = $meeting['weekday_tinyint'];
					$current_day = $this->pos['weekday'];
					}
				
				if ( $current_day < 1 )
				    {
				    $current_day = 1;
				    }
				    
				$header = $this->weekday_names[$current_day - 1];
				
				$header .= $contd;
				
				$this->napdf_instance->SetFont ( $fontFamily, 'B', $heading_height );
				$this->napdf_instance->Rect ( $left, $this->pos['y'], ($right - $left), $height, "F" );
				$stringWidth = $this->napdf_instance->GetStringWidth ( $header );
				$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
				$this->napdf_instance->SetXY ( $cellleft, $this->pos['y'] + 0.005 );
				$this->napdf_instance->Cell ( 0, $heading_height/72, $header );
				$this->pos['y'] += ($height);
				}
			else
				{
	    		$this->napdf_instance->SetDrawColor ( 0 );
				$this->napdf_instance->Line ( $left, $this->pos['y'], $right, $this->pos['y'] );
				}
			
			$this->napdf_instance->SetFillColor ( 255 );
			$this->napdf_instance->SetTextColor ( 0 );
			
			$cell_top = $this->pos['y'] + $gap2;
			$this->napdf_instance->SetXY ( $left, $cell_top );
			
			$this->napdf_instance->SetFont ( $fontFamily, 'B', $fontSize );
			$this->napdf_instance->MultiCell ( $column_width, $fSize, utf8_decode ( $meeting['location_municipality'] ), 0, "L" );
			
			$display_string = '';
			
			if ( isset ( $meeting['start_time'] ) )
			    {
			    $display_string = self::translate_time ( $meeting['start_time'] );
			    }
			    
			if ( isset ( $meeting['duration_time'] ) && $meeting['duration_time'] && ('01:30:00' != $meeting['duration_time']) )
				{
				$display_string .= " (".self::translate_duration ( $meeting['duration_time'] ).")";
				}
			
			$this->napdf_instance->MultiCell ( $column_width, $fSize, utf8_decode ( $display_string ), 0, "L" );
			
			$this->napdf_instance->SetFont ( $fontFamily, 'B', $fontSize );
			
			$this->napdf_instance->SetX ( $left );
			$display_string = isset ( $meeting['meeting_name'] ) ? $meeting['meeting_name'] : '';
			
			if ( isset ( $meeting['formats'] ) )
			    {
			    $display_string .= " (".$this->RearrangeFormats ( $meeting['formats'] ).")";
			    }
	
			$this->napdf_instance->SetX ( $left );
	
			$this->napdf_instance->MultiCell ( $column_width, $fSize, utf8_decode ( $display_string ), 0, "L" );
			
			$this->napdf_instance->SetFont ( $fontFamily, '', $fontSize );
			
			if ( isset ( $meeting['location_neighborhood'] ) && $meeting['location_neighborhood'] )
				{
				$display_string = $meeting['location_neighborhood'];
				$this->napdf_instance->SetX ( $left );
				$this->napdf_instance->MultiCell ( $column_width, $fSize, utf8_decode ( $display_string ), 0, "L" );
				}
			
			$display_string = '';
			
			if ( isset ( $meeting['location_text'] ) && $meeting['location_text'] )
				{
				$display_string .= $meeting['location_text'];
				}
			
			if ( isset ( $meeting['location_info'] ) && $meeting['location_info'] )
				{
				if ( $display_string )
					{
					$display_string .= ', ';
					}
	
				$display_string .= " (".$meeting['location_info'].")";
				}
			
			if ( $display_string )
				{
				$display_string .= ', ';
				}
			
			$display_string .= isset ( $meeting['location_info'] ) ? $meeting['location_street'] : '';
			
			$this->napdf_instance->SetX ( $left );
			$this->napdf_instance->MultiCell ( $column_width, $fSize, utf8_decode ( $display_string ), 0, "L" );
			
			if ( isset ( $meeting['description_string'] ) && $meeting['description_string'] )
				{
				if ( $desc )
					{
					$desc .= ", ";
					}
				$desc = $meeting['description_string'];
				}
			
			if ( isset ( $meeting['comments'] ) && $meeting['comments'] )
				{
				if ( $desc )
					{
					$desc .= ", ";
					}
				$desc .= $meeting['comments'];
				}
			
			$desc = preg_replace ( "/[\n|\r]/", ", ", $desc );
			$desc = preg_replace ( "/,\s*,/", ",", $desc );
			$desc = stripslashes ( stripslashes ( $desc ) );
	
			if ( $desc )
				{
				$extra = ($fSizeSmall * 3);
				$this->napdf_instance->SetFont ( $fontFamily, 'I', $fontSize - 1 );
				$this->napdf_instance->SetX ( $left );
				$this->napdf_instance->MultiCell ( $column_width, $fSizeSmall, utf8_decode ( $desc ), 0, "L" );
				}
			
			if ( !isset ( $yMax ) )
			    {
			    $yMax = 0;
			    }
			    
			$yMax = max ( $yMax, $this->napdf_instance->GetY ( ) ) + $gap2;
			$this->pos['y'] = $yMax;
			$this->pos['count']++;
			
			if ( $this->pos['count'] == $count_max )
				{
				$this->pos['end'] = 1;
				}
			else
				{
				$next_meeting = $meetings[intval($this->pos['count'])];
				
				if ( isset ( $next_meeting['weekday_tinyint'] ) && ($current_day != $next_meeting['weekday_tinyint']) )
					{
					$extra_height = $height + 0.05;
					}
				else
					{
					$extra_height = 0;
					}
				}
			}
	}

	/********************************************************************
	*/
	function RearrangeFormats ( $inFormats )
	{
		$inFormats = explode ( ",", $inFormats );
		
		if ( !in_array ( "C", $inFormats ) && !in_array ( "O", $inFormats ) )
			{
			array_push ( $inFormats, "C" );
			}
		
		if ( !in_array ( "BK", $inFormats ) && ((in_array ( "BT", $inFormats ) || in_array ( "IW", $inFormats ) || in_array ( "JT", $inFormats ) || in_array ( "SG", $inFormats ))) )
			{
			array_push ( $inFormats, "BK" );
			}
		
		uasort ( $inFormats, 'sas_napdf::sort_cmp' );
		
		$tFormats = $inFormats;
		
		$inFormats = array();
		
		foreach ( $tFormats as $format )
			{
			$format = trim ( $format );
			if ( $format )
				{
				array_push ( $inFormats, $format );
				}
			}
		
		return join ( ",", $inFormats );
	}

	/********************************************************************
		\brief This function actually assembles the PDF. It does not output it.
		
		\returns a boolean. true if successful.
	*/
	function AssemblePDF ()
	{
		return false;
	}
	
	/********************************************************************
	*/
	function OutputPDF ()
	{
	}

	/********************************************************************
	*/
	static function break_meetings_by_day ( $in_meetings_array ) 
	{
		$last_day = -1;
		$meetings_day = array();
		
		foreach ( $in_meetings_array as $meeting )
			{
			if ( $meeting['weekday_tinyint'] != $last_day )
				{
				$last_day = $meeting['weekday_tinyint'] -1;
				}
			
			$meetings_day[$last_day][] = $meeting;
			}
		
		return $meetings_day;
	}

	/********************************************************************
	*/
	static function sort_cmp ($a, $b) 
	{
		$order_array = array(	0=>"O", 1=>"C",
								2=>"ES", 3=>"B", 4=>"M", 5=>"W", 6=>"GL", 7=>"YP", 8=>"BK", 9=>"IP", 10=>"Pi", 11=>"RF", 12=>"Rr",
								13=>"So", 14=>"St", 15=>"To", 16=>"Tr", 17=>"OE", 18=>"D", 19=>"SD", 20=>"TW", 21=>"IL",
								22=>"BL", 23=>"IW", 24=>"BT", 25=>"SG", 26=>"JT",
								27=>"Ti", 28=>"Sm", 29=>"NS", 30=>"CL", 31=>"CS", 32=>"NC", 33=>"SC", 34=>"CH", 35=>"SL", 36=>"WC" );
		
		if ( in_array ( $a, $order_array ) || in_array ( $b, $order_array ) )
			{
			return (array_search ( $a, $order_array ) < array_search ( $b, $order_array )) ? -1 : 1;
			}
		else
			{
			return 0;
			}
	}

	/********************************************************************
	*/
	static function translate_time ( $in_time_string ) 
	{
		$split = explode ( ":", $in_time_string );
		if ( $in_time_string == "12:00:00" )
			{
			return "Noon";
			}
		elseif ( ($split[0] == "23") && (intval ( $split[1] ) > 45) )
			{
			return "Midnight";
			}
		else
			{
			return date ( "g:i A", strtotime ( $in_time_string ) );
			}
	}

	/********************************************************************
	*/
	static function translate_duration ( $in_time_string ) 
	{
		$t = explode ( ":", $in_time_string );
		$hours = intval ( $t[0] );
		$minutes = intval ( $t[1] );
		
		$ret = '';
		
		if ( $hours )
			{
			$ret .= "$hours hour";
			
			if ( $hours > 1 )
				{
				$ret .= "s";
				}
				
			if ( $minutes )
				{
				$ret .= " and ";
				}
			}
		
		if ( $minutes )
			{
			$ret .= "$minutes minutes";
			}
		
		return $ret;
	}
    
	/********************************************************************
	*/
	function DrawFoldGuides ( $inMargins )
	{
		$size = (($this->orientation == 'L') ? $this->page_y : $this->page_x) / $this->page_sections;
	    $this->napdf_instance->SetDrawColor ( 200 );

		for ( $index = 1; $index < $this->page_sections; $index++ )
			{
			$cellleft = $size * $index;
			$celltop = $inMargins ;
			$cellBottom = (($this->orientation == 'L') ? $this->page_x : $this->page_y) - $inMargins;

			$this->napdf_instance->SetLineWidth ( 0.005 );
			$this->napdf_instance->Line ( $cellleft, $celltop, $cellleft, $cellBottom );
			}
	    
	    $this->napdf_instance->SetDrawColor ( 0 );
	}
};
?>