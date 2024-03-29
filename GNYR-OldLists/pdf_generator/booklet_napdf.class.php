<?php
/**
	\file booklet_napdf.class.php
	\version 1.2.16
	\brief This file creates and dumps a generic meeting list in a booklet form.
*/
// Get the napdf class, which is used to fetch the data and construct the file.
require_once ( dirname ( __FILE__ ).'/printableList.class.php' );

/**
	\brief	This creates and manages an instance of the napdf class, and creates
	the PDF file.
*/
class booklet_napdf extends printableList implements IPrintableList
{
	var $weekday_names = null;
	var	$pos = array ( 'start' => 1, 'end' => '', 'count' => 0, 'y' => 0, 'weekday' => 1 );
	var $right_offset = 0;
	var $formats = 0;
	var $special_formats = '';
	
	/********************************************************************
		\brief	The constructor for this class does a lot. It creates the instance of the napdf class, gets the data from the
		server, then sorts it. When the constructor is done, the data is ready to be assembled into a PDF.
		
		If the napdf object does not successfully get data from the server, then it is set to null.
	*/
	function __construct ( $in_http_vars	///< The HTTP parameters we'd like to send to the server.
							)
	{
		include ( dirname ( __FILE__ ).'/booklet_pdf_decls.php' );
		$this->weekday_names = $g_weekday_names;
		$this->page_x = 5.25;		///< The width, in inches, of each page
		$this->page_y = 8;			///< The height, in inches, of each page.
		$this->units = 'in';		///< The measurement units (inches)
		$this->font = 'Times';		///< The font we'll use
		$this->font_size = 8;		///< The font size we'll use
		$this->orientation = 'P';	///< The orientation (portrait)
		/// These are the sort keys, for sorting the meetings before display
		$this->sort_keys = array (	'weekday_tinyint' => true,			///< First, weekday
									'start_time' => true,				///< Finally, the time the meeting starts
									'week_starts' => 1					///< Our week starts on Sunday (1)
									);
		/// These are the parameters that we send over to the root server, in order to get our meetings.
		$this->out_http_vars = array ('do_search' => 'yes',						///< Do a search
									'bmlt_search_type' => 'advanced',
									'advanced_service_bodies' => $in_http_vars['advanced_service_bodies'],
									'advanced_formats' => $in_http_vars['advanced_formats'],
									'advanced_weekdays' => $in_http_vars['advanced_weekdays'],
									'advanced_radius' => $in_http_vars['advanced_radius'],
									'advanced_mapmode' => $in_http_vars['advanced_mapmode'],
									'long_val' => $in_http_vars['long_val'],
									'lat_val' => $in_http_vars['lat_val'],
									'geo_width' => $in_http_vars['geo_width'],
									'geo_width_km' => $in_http_vars['geo_width_km'],
									'long_val' => $in_http_vars['long_val'],
									'lat_val' => $in_http_vars['lat_val'],
									'SearchString' => $in_http_vars['SearchString'],
									'StringSearchIsAnAddress' => $in_http_vars['StringSearchIsAnAddress']
									);

		$this->lang_search = array ( 'en' );
		
		napdf::$sort_callback = 'booklet_napdf::sort_meeting_data_callback';
		
		parent::__construct ( $in_http_vars, $this->lang_search );
	}
	/*************************** INTERFACE FUNCTIONS ***************************/
	/********************************************************************
		\brief This function actually assembles the PDF. It does not output it.
		
		\returns a boolean. true if successful.
	*/
	function AssemblePDF ()
	{
		$ret = false;
		
		if ( $this->napdf_instance instanceof napdf )
			{
			$this->DrawFormatPage ( );
			$left = true;
			while ( !$this->pos['end'] )
				{
				$this->DrawListPage ( $left );
				$left = !$left;
				}
			$ret = true;
			}
		
		return $ret;
	}
	
	/********************************************************************
	*/
	function OutputPDF ()
	{
		$d = date ( "Y_m_d" );
		$this->napdf_instance->Output( "BMLT_PrintableBooklet_$d.pdf", "D" );
	}
	
	/*************************** INTERNAL FUNCTIONS ***************************/
	/********************************************************************
	*/
	private static function sort_cmp_formats ($a, $b) 
	{
	$order_array = array(	0=>"O", 1=>"C",
							2=>"ES", 3=>"B", 4=>"M", 5=>"W", 6=>"GL", 7=>"YP", 8=>"BK", 9=>"IP", 10=>"Pi", 11=>"RF", 12=>"Rr",
							13=>"So", 14=>"St", 15=>"To", 16=>"Tr", 17=>"OE", 18=>"D", 19=>"SD", 20=>"TW", 21=>"IL",
							22=>"BL", 23=>"IW", 24=>"BT", 25=>"SG", 26=>"JT",
							27=>"Ti", 28=>"Sm", 29=>"NS", 30=>"CL", 31=>"CS", 32=>"NC", 33=>"SC", 34=>"CH", 35=>"SL", 36=>"WC" );
	
	if ( in_array ( $a['key_string'], $order_array ) || in_array ( $b['key_string'], $order_array ) )
		{
		return (array_search ( $a['key_string'], $order_array ) < array_search ( $b['key_string'], $order_array )) ? -1 : 1;
		}
	else
		{
		return 0;
		}
	}

	/********************************************************************
		\brief Combines the two languages for a common format display.
		
		\returns a new array, with the format data sorted out. The Spanish
		description is appended to the description by a line feed.
	*/
	private static function sort_formats( $in_format_data	///< An array of format information
										)
	{
		$ret = array();
		
		foreach ( $in_format_data as $format )
			{
			if ( $format['lang'] == 'en' )	// We skip the Spanish, and only work on the English.
				{
				$s_desc = ((isset ( $in_format_data[$format['id'].'_es'] )) ? '('.$in_format_data[$format['id'].'_es']['name_string'].') '.$in_format_data[$format['id'].'_es']['description_string'] : null);
				$r_temp = array (	'key_string' => $format['key_string'],
									'description_string' => '('.$format['name_string'].') '.$format['description_string']
									);
				if ( $s_desc )
					{
					$r_temp['description_string'] .= "\n$s_desc";
					}
				
				array_push ( $ret, $r_temp );
				}
			}
		
		usort ( $ret, 'booklet_napdf::sort_cmp_formats' );
		
		return $ret;
	}

	/********************************************************************
	*/
	private function DrawFormatPage ( )
	{
	include ( dirname ( __FILE__ ).'/booklet_pdf_decls.php' );
	$formats = self::sort_formats ( $this->napdf_instance->format_data );

	$this->napdf_instance->SetFont ( $this->font, '', $this->font_size );
	$heading_height = 9;
	$height = ($heading_height/72) + 0.01;
	$top = 0.125;
	$left = 0.125;
	$bottom = $this->napdf_instance->h - 0.125;
	$right = $this->napdf_instance->w - 0.125;
	$fontFamily = $this->napdf_instance->FontFamily;
	$fontSize = $this->napdf_instance->FontSizePt;
	$fontSizeSmaller = $fontSize - 0.25;
	$fSize = $fontSizeSmaller / 72;
	$this->right_offset = 0.4;
	$space_needed = (($fontSizeSmaller / 72) * 5) + 0.02;
	$y_offset = $bottom - $fSize;
	
	$newpage = 2;
	
	$countmax = count ( $formats );
	
	$this->special_formats = array ( );
		
	for ( $count = 0; $count < $countmax; $count++ )
		{
		$format_code = $formats[$count]['key_string'];
		$format_english = $formats[$count]['description_string'];
		
		if ( isset ( $this->special_formats[$format_code] ) )
			{
			$this->special_formats[$format_code] = $format_english;
			$format_code = null;
			}
		
		if ( $format_code )
			{
			if ( $newpage )
				{
				$this->napdf_instance->AddPage();
				$this->napdf_instance->SetFont ( $fontFamily, 'B', 7 );
				$this->napdf_instance->Line ( $left, $y_offset - 0.1, $right, $y_offset - 0.1 );				
				$this->napdf_instance->SetXY ( $right, $y_offset );
				$this->napdf_instance->Cell ( 0, 0, _PDF_PAGE." ".$this->napdf_instance->PageNo(), 0, 0, "R" );
				$this->napdf_instance->SetFont ( $fontFamily, '', $fontSize );
				$this->napdf_instance->SetFillColor ( 0 );
				$this->napdf_instance->SetTextColor ( 255 );
				$header = ""._PDF_LEGEND_HEADER."";
				if ( $newpage != 2 )
					{
					$header .= _PDF_CONTD;
					}
				
				$this->napdf_instance->SetFont ( $fontFamily, 'B', $heading_height );
				$stringWidth = $this->napdf_instance->GetStringWidth ( $header );
				$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
				$this->napdf_instance->Rect ( $left, $top, ($right - $left), $height, "F" );
				$this->napdf_instance->SetXY ( $cellleft, $top + 0.01 );
				$this->napdf_instance->Cell ( 0, $heading_height/72, $header );
				$this->napdf_instance->SetFillColor ( 255 );
				$this->napdf_instance->SetTextColor ( 0 );
				$newpage = "";
				$this->napdf_instance->SetY ( $top + $height );
				}
			else
				{
				$this->napdf_instance->Line ( $left, $this->napdf_instance->GetY() + 0.01, $right, $this->napdf_instance->GetY() + 0.01 );
				$this->napdf_instance->SetY ( $this->napdf_instance->GetY() + 0.02 );
				}
			
			$y = $this->napdf_instance->GetY();
			
			$this->napdf_instance->SetFont ( $fontFamily, 'B', $fontSizeSmaller );
			$this->napdf_instance->SetLeftMargin ( $left );
			$this->napdf_instance->SetXY ( $left, $y );
			$this->napdf_instance->MultiCell ( $column_width, $fSize, utf8_decode ( $format_code ), 0, "L" );
	
			$this->napdf_instance->SetFont ( $fontFamily, '', $fontSizeSmaller );
			$this->napdf_instance->SetLeftMargin ( $this->right_offset );
			$this->napdf_instance->SetXY ( $this->right_offset, $y );
			$this->napdf_instance->MultiCell ( $column_width, $fSize, utf8_decode ( $format_english ), 0, "L" );
			
			if ( ($this->napdf_instance->GetY() + 0.01 + $space_needed) >= $y_offset )
				{
				$newpage = 1;
				}
			}
		}
	
	if ( ($this->napdf_instance->GetY() + 0.3 + $fSize/72) > $y_offset )
		{
		$this->napdf_instance->AddPage();
		$y = $y_offset;
		}
	else
		{
		$y = $this->napdf_instance->GetY() + 0.3;
		}
	
	$this->napdf_instance->Line ( $left, $y - 0.1, $right, $y - 0.1 );
	$this->napdf_instance->SetXY ( $left, $y );
	$this->napdf_instance->SetFont ( $fontFamily, 'I', $fontSize );
	$this->napdf_instance->Cell ( 0, $fSize/72, _DEFAULT_DURATION );
	$this->napdf_instance->SetFont ( $fontFamily, '', $fontSize );
	$this->napdf_instance->SetLeftMargin ( $left );
	}
	
	/********************************************************************
	*/
	private function DrawListPage ( $in_left	///< If this is true, it is the left side. If false, it is the right side column.
									)
	{
	include ( dirname ( __FILE__ ).'/booklet_pdf_decls.php' );
	$meetings =& $this->napdf_instance->meeting_data;
	
	$count_max = count ( $meetings );
	
	$this->napdf_instance->SetFont ( $this->font, '', $this->font_size );
	$fontFamily = $this->napdf_instance->FontFamily;
	$fontSize = $this->napdf_instance->FontSizePt;
	
	$column_width = (($this->napdf_instance->w - 0.25) / 2) - 0.05;
	
	$top = 0.125;
	$left = $in_left ? 0.125 : (0.125 + $column_width + 0.1);
	$bottom = $this->napdf_instance->h - 0.125;
	$right = $left + $column_width;
	$this->right_offset = 0;
	
	$heading_height = 9;
	$height = ($heading_height/72) + 0.01;
	$gap2 = 0.02;
	
	$fSize = $fontSize / 70;
	$fSizeSmall = ($fontSize - 1) / 70;

	$height_one_meeting = ($fSize * 6) + $gap2;
	
	$y_offset = $bottom - $fSize;
	
	$current_day = " ";
	
	$extra_height = $height + 0.05;

	if ( $in_left )
		{
		$this->napdf_instance->AddPage();
		}
		
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
		$meeting = $meetings[intval($this->pos['count'])];
		$this->napdf_instance->SetLeftMargin ( $left );
		
		if ( $this->pos['start'] || ($current_day != $meeting['weekday_tinyint']) )
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
			
			if ( $current_day != $meeting['weekday_tinyint'] )
				{
				$this->pos['weekday'] = $meeting['weekday_tinyint'];
				$current_day = $this->pos['weekday'];
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
			$this->napdf_instance->Line ( $left, $this->pos['y'], $right, $this->pos['y'] );
			}
		
		$this->napdf_instance->SetFillColor ( 255 );
		$this->napdf_instance->SetTextColor ( 0 );
		
		$this->napdf_instance->SetFont ( $fontFamily, 'B', $fontSize );
		
		$cell_top = $this->pos['y'] + $gap2;
		$this->napdf_instance->SetXY ( $left, $cell_top );
		
		$display_string = $meeting['meeting_name'];
			
		$display_string .= " (".$this->RearrangeFormats ( $meeting['formats'] ).")";

		$this->napdf_instance->SetX ( $left );

		$this->napdf_instance->MultiCell ( $column_width, $fSize, utf8_decode ( $display_string ), 0, "L" );
		
		$this->napdf_instance->SetFont ( $fontFamily, '', $fontSize );
		
		$display_string = self::translate_time ( $meeting['start_time'] );
		if ( $meeting['duration_time'] && ('01:30:00' != $meeting['duration_time']) )
			{
			$display_string .= " (".self::translate_duration ( $meeting['duration_time'] ).")";
			}
		
		$this->napdf_instance->SetX ( $left );
		$this->napdf_instance->MultiCell ( $column_width, $fSize, utf8_decode ( $display_string ), 0, "L" );

		$display_string = '';
		
		if ( trim ( $meeting['location_city_subsection'] ) )
			{
			$display_string .= trim ( $meeting['location_city_subsection'] );
			}
		
		if ( trim ( $meeting['location_neighborhood'] ) )
			{
			$display_string .= ' ('.trim ( $meeting['location_neighborhood'] ).')';
			}
		
		if ( $display_string )
			{
			$display_string .= ', ';
			}
		
		$display_string .= $meeting['location_municipality'];
		
		if ( $display_string )
			{
			$display_string .= ', ';
			}
		
		$display_string .= $meeting['location_province'];
		
		$this->napdf_instance->SetX ( $left );
		$this->napdf_instance->MultiCell ( $column_width, $fSize, utf8_decode ( $display_string ), 0, "L" );
		
		$display_string = '';
		
		if ( trim ( $meeting['location_text'] ) )
			{
			$display_string .= trim ( $meeting['location_text'] );
			}
		
		if ( trim ( $meeting['location_info'] ) )
			{
			if ( $display_string )
				{
				$display_string .= ', ';
				}

			$display_string .= " (".trim ( $meeting['location_info'] ).")";
			}
		
		if ( $display_string )
			{
			$display_string .= ', ';
			}
		
		$display_string .= $meeting['location_street'];
		
		$this->napdf_instance->SetX ( $left );
		$this->napdf_instance->MultiCell ( $column_width, $fSize, utf8_decode ( $display_string ), 0, "L" );
		
		$desc = $this->Get_Special_Format_Text ( $meeting['formats'] );
		
		if ( trim ( $meeting['description_string'] ) )
			{
			if ( $desc )
				{
				$desc .= ", ";
				}
			$desc = trim ( $meeting['description_string'] );
			}
		
		if ( trim ( $meeting['comments'] ) )
			{
			if ( $desc )
				{
				$desc .= ", ";
				}
			$desc .= trim ( $meeting['comments'] );
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
			
			if ( $current_day != $next_meeting['weekday_tinyint'] )
				{
				$extra_height = $height + 0.05;
				}
			else
				{
				$extra_height = 0;
				}
			}
		}
	
	if ( !$in_left || $this->pos['end'] )
		{
		$this->napdf_instance->SetFont ( $fontFamily, 'B', 7 );
		$this->napdf_instance->SetXY ( $right, $y_offset );
		$this->napdf_instance->Cell ( 0, 0, _PDF_PAGE." ".$this->napdf_instance->PageNo(), 0, 0, "R" );
		$this->napdf_instance->SetFont ( $fontFamily, '', $fontSize );
		}
	}

	/********************************************************************
	*/
	private function Get_Special_Format_Text ( $inFormats )
	{
	$ret = "";
	
	$format_array = split ( ",", $inFormats );

	foreach ( $format_array as $format )
		{
		if ( $this->special_formats[$format] )
			{
			if ( $ret )
				{
				$ret .= ", ";
				}
			
			$ret .= $this->special_formats[$format];
			}
		}
	
	return $ret;
	}

	/********************************************************************
	*/
	private function RearrangeFormats ( $inFormats )
	{
	$inFormats = split ( ",", $inFormats );
	
	if ( !in_array ( "C", $inFormats ) && !in_array ( "O", $inFormats ) )
		{
		array_push ( $inFormats, "C" );
		}
	
	if ( !$ignore_bk && !in_array ( "BK", $inFormats ) && ((in_array ( "BT", $inFormats ) || in_array ( "IW", $inFormats ) || in_array ( "JT", $inFormats ) || in_array ( "SG", $inFormats ))) )
		{
		array_push ( $inFormats, "BK" );
		}
	
	for ( $c = 0; $c < count ( $inFormats ); $c++ )
		{
		if ( array_key_exists ( $inFormats[$c], $this->special_formats ) )
			{
			$inFormats[$c] = null;
			unset ( $inFormats[$c] );
			}
		}
	
	uasort ( $inFormats, 'booklet_napdf::sort_cmp' );
	
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
	
	return join ( " ", $inFormats );
	}

	/********************************************************************
	*/
	private static function sort_cmp ($a, $b) 
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
	private static function translate_time ( $in_time_string ) 
	{
		$split = split ( ":", $in_time_string );
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
	private static function translate_duration ( $in_time_string ) 
	{
		$t = split ( ":", $in_time_string );
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
	
	/**
		\brief	This is a static callback function to be used for sorting the multi-dimensional meeting_data
				array. It uses the sort_order_keys array to determine the sort.
				
		\returns an integer. -1 if a < b, 0 if a == b, or 1 if a > b.
	*/
	static function sort_meeting_data_callback (&$in_a,		///< The first meeting array to compare
												&$in_b		///< The second meeting array to compare
												)
	{
		$ret = 0;
		
		if ( is_array ( $in_a ) && is_array ( $in_b ) && is_array ( napdf::$sort_order_keys ) )
			{
			// We reverse the array, in order to sort from least important to most important.
			$sort_keys = array_reverse ( napdf::$sort_order_keys, true );

			foreach ( $sort_keys as $key => $value )
				{
				if ( isset ( $in_a[$key] ) && isset ( $in_b[$key] ) )
					{
					$val_a = trim ( $in_a[$key] );
					$val_b = trim ( $in_b[$key] );

					if ( ('weekday_tinyint' == $key) && (napdf::$week_starts > 1) && (napdf::$week_starts < 8) )
						{
						$val_a -= napdf::$week_starts;

						if ( $val_a < 0 )
							{
							$val_a += 8;
							}
						else
							{
							$val_a += 1;
							}
						
						$val_b -= napdf::$week_starts;
						
						if ( $val_b < 0 )
							{
							$val_b += 8;
							}
						else
							{
							$val_b += 1;
							}
						}

					// We know a few keys already, and we can determine how the sorting goes from there.
					switch ( $key )
						{
						case 'start_time':
						case 'duration_time':
							$val_a = strtotime ( $val_a );
							$val_b = strtotime ( $val_b );
						case 'weekday_tinyint':
						case 'id_bigint':
						case 'shared_group_id_bigint':
						case 'service_body_bigint':
							$val_a = intval ( $val_a );
							$val_b = intval ( $val_b );
						case 'longitude':
						case 'latitude':
							if ( $val_a > $val_b )
								{
								$ret = 1;
								}
							elseif ( $val_b > $val_a )
								{
								$ret = -1;
								}
						break;
						
						default:
							// We ignore blank values
							if ( strlen ( $val_a ) && strlen ( $val_b ) )
								{
								$tmp = strcmp ( strtolower ( $val_a ), strtolower ( $val_b ) );
								
								if ( $tmp != 0 )
									{
									$ret = $tmp;
									}
								}
						break;
						}
					}
				
				if ( !$value )
					{
					$ret = -$ret;
					}
				}
			}
		
		return $ret;
	}
};
?>