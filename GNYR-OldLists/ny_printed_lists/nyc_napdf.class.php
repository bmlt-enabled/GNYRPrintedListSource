<?php
/**
	\file nyc_napdf.class.php
	
	\brief This file creates and dumps a New York City meeting list in PDF form.
*/
ini_set('display_errors', 1);
ini_set('error_reporting', E_ERROR);
// Get the napdf class, which is used to fetch the data and construct the file.
require_once ( dirname ( __FILE__ ).'/../pdf_generator/printableList.class.php' );
require_once ( dirname ( __FILE__ ).'/pdf_decls.php' );

/**
	\brief	This creates and manages an instance of the napdf class, and creates
	the PDF file.
*/
class nyc_napdf extends printableList implements IPrintableList
{
	var $weekday_names = array ( "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday" );
	var	$pos = array ( 'start' => 1, 'end' => '', 'count' => 0, 'y' => 0, 'weekday' => 1 );
	var $formats = 0;
	
	/********************************************************************
		\brief	The constructor for this class does a lot. It creates the instance of the napdf class, gets the data from the
		server, then sorts it. When the constructor is done, the data is ready to be assembled into a PDF.
		
		If the napdf object does not successfully get data from the server, then it is set to null.
	*/
	function __construct ( $in_http_vars	///< The HTTP parameters we'd like to send to the server.
							)
	{
		$this->page_x = 11;			///< The width, in inches, of each page
		$this->page_y = 8.5;			///< The height, in inches, of each page.
		$this->units = 'in';		///< The measurement units (inches)
		$this->font = 'Helvetica';	///< The font we'll use
		$this->font_size = 12;		///< The font size we'll use
		$this->orientation = 'L';	///< The orientation (portrait)
		/// These are the sort keys, for sorting the meetings before display
		$this->sort_keys = array (	'weekday_tinyint' => true,			///< First, weekday
									'start_time' => true,				///< Next, the time the meeting starts
									'location_neighborhood' => true,	///< Finally, the neighborhood.
									'week_starts' => 1					///< Our week starts on Sunday (1)
									);
		/// These are the parameters that we send over to the root server, in order to get our meetings.
		$this->out_http_vars = array ('do_search' => 'yes',						///< Do a search
									'bmlt_search_type' => 'advanced',			///< We'll be very specific in our request
									'location_province' => 'NY',	///< New York State.
									'meeting_key' => array ( 'location_city_subsection',
															'location_municipality',
															'location_sub_province'
															),
									'meeting_key_value' => 'Manhattan'
									);
		
		parent::__construct ( $in_http_vars );
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
			$page_margins = 0.35;

			$meeting_data =& $this->napdf_instance->meeting_data;
			
			if ( $meeting_data )
				{
				// Calculate the overall layout of the list
				
				// The front and back panels are quarter page panels.
				$panelpage['margin'] = $page_margins;
				$panelpage['height'] = $this->napdf_instance->h - ($panelpage['margin'] * 2);
				$panelpage['width'] = ($this->napdf_instance->w / 2) - ($panelpage['margin'] * 2);
				
				// List pages are half page panels.
				$listpage['margin'] = $page_margins;
				$listpage['height'] = $this->napdf_instance->h - ($listpage['margin'] * 2);
				$listpage['width'] = $this->napdf_instance->w - ($listpage['margin'] * 2);
				
				// These are the actual drawing areas.
				
				// The panel that is up front of the folded list.
				$frontpanel_x_offset = $panelpage['width'] + ($panelpage['margin'] * 3);
				$frontpanel_max_x_offset = $frontpanel_x_offset + $panelpage['width'];
				$frontpanel_y_offset = $panelpage['margin'];
				$frontpanel_max_y_offset = $frontpanel_y_offset + $panelpage['height'];
				
				// The panel that is on the back of the folded list.
				$backpanel_x_offset = $panelpage['margin'];
				$backpanel_max_x_offset = $backpanel_x_offset + $panelpage['width'];
				$backpanel_y_offset = $panelpage['margin'];
				$backpanel_max_y_offset = $backpanel_y_offset + $panelpage['height'];
				
				// Each page is separated by a vertical line.
				$vertical_separator_x = ($this->napdf_instance->w / 2);
				$vertical_separator_y = $page_margins;
				$vertical_separator_y_2 = $this->napdf_instance->h - $page_margins;
				
				// The front page has half dedicated to a single list panel.
				$frontlist_x_offset = $frontpanel_max_x_offset + $panelpage['margin'] + $listpage['margin'];
				$frontlist_max_x_offset = $frontlist_x_offset + $listpage['width'];
				$frontlist_y_offset = $listpage['margin'];
				$frontlist_max_y_offset = $frontlist_y_offset + $listpage['height'];
				
				// The back page has two list panels.
				$backlist_page_1_x_offset = $listpage['margin'];
				$backlist_page_1_max_x_offset = $backlist_page_1_x_offset + $listpage['width'];
				$backlist_page_1_y_offset = $listpage['margin'];
				$backlist_page_1_max_y_offset = $backlist_page_1_y_offset + $listpage['height'];
				
				$backlist_page_2_x_offset = $backlist_page_1_max_x_offset + ($listpage['margin'] * 2);
				$backlist_page_2_max_x_offset = $backlist_page_2_x_offset + $listpage['width'];
				$backlist_page_2_y_offset = $listpage['margin'];
				$backlist_page_2_max_y_offset = $backlist_page_2_y_offset + $listpage['height'];
				
				global $columns, $maxwidth, $fSize, $y;
				$maxwidth = $listpage['width'] + 1;
				$columns = "";
				$fSize = $this->napdf_instance->FontSizePt;
								
				foreach ( $meeting_data as &$meeting )
					{
					if ( isset ( $meeting['location_text'] ) && isset ( $meeting['location_street'] ) )
					    {
					    $meeting['location'] = $meeting['location_text'].', '.$meeting['location_street'];
					    }
					}
				
				$this->napdf_instance->AddPage ( );
				$this->DrawRearPanel ( $backpanel_x_offset, $backpanel_y_offset, $backpanel_max_x_offset, $backpanel_max_y_offset, $this->napdf_instance->format_data );

				$inPrinter_Date = date ( '\R\e\v\i\s\e\d F, Y' );

				$this->DrawFrontPanel ( $frontpanel_x_offset, $frontpanel_y_offset, $frontpanel_max_x_offset, $frontpanel_max_y_offset, $inPrinter_Date );
				
				$this->napdf_instance->AddPage ( );
				
				$this->DrawListPage ( 0 );
				$this->DrawListPage ( 1 );
				$this->DrawListPage ( 2 );
				$this->DrawListPage ( 3 );
				
				$this->napdf_instance->AddPage ( );

				$this->DrawListPage ( 0 );
				$this->DrawListPage ( 1 );
				$this->DrawListPage ( 2 );
				$this->DrawListPage ( 3 );
				
				$this->napdf_instance->AddPage ( );
				
				$this->DrawListPage ( 0 );
				$this->DrawListPage ( 1 );
				$this->DrawListPage ( 2 );
				$this->DrawListPage ( 3 );
				
				$this->DrawSubcommittees ( );
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
		$this->napdf_instance->Output( "NYC_PrintableList_$d.pdf", "I" );
	}
	
	/*************************** INTERNAL FUNCTIONS ***************************/
	
	/********************************************************************
	*/
	private function DrawListPage ( $in_column	///< If this is true, it is the left side. If false, it is the right side column.
									)
	{
		include_once ( dirname ( __FILE__ ).'/pdf_decls.php' );
		$meetings =& $this->napdf_instance->meeting_data;
		
		$count_max = count ( $meetings );
		
		$this->napdf_instance->SetFont ( $this->font, '', $this->font_size - 5 );
		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->napdf_instance->FontSizePt;
		
		$column_width = (($this->napdf_instance->w - 0.5) / 4) - 0.05;
		
		$top = 0.125;
		$left = 0.125 + (($column_width + 0.1) * $in_column);
		$bottom = $this->napdf_instance->h - 0.125;
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
				$this->napdf_instance->Line ( $left, $this->pos['y'], $right, $this->pos['y'] );
				}
			
			$this->napdf_instance->SetFillColor ( 255 );
			$this->napdf_instance->SetTextColor ( 0 );
			
			$cell_top = $this->pos['y'] + $gap2;
			$this->napdf_instance->SetXY ( $left, $cell_top );
			
			$this->napdf_instance->SetFont ( $fontFamily, '', $fontSize );
			
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
	private function DrawRearPanel ( $left, $top, $right, $bottom, $formats )
	{
		$y = $top + 0.125;

		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->napdf_instance->FontSizePt;
		
		$this->napdf_instance->SetFont ( $this->font, 'B', ($fontSize - 3) );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_SASNA_LIST_FORMAT_KEY );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );

		$this->napdf_instance->Cell ( 0, 0, _PDF_SASNA_LIST_FORMAT_KEY );
		$y += 0.125;
		
		$count = count ( $formats );
		$w1 = $left + 0.25;
		$fSize = $fontSize - 4.5;
		
		$this->napdf_instance->SetY ( $y );

		foreach ( $formats as $format )
			{
			$this->napdf_instance->SetFont ( $this->font, 'B', $fSize );
			$this->napdf_instance->SetLeftMargin ( $left );
			$str = $format['key_string'];
			$this->napdf_instance->SetX ( $left );
			$this->napdf_instance->Cell ( 0, 0.13, $str );
			$this->napdf_instance->SetFont ( $this->font, '', $fSize );
			$str = $format['description_string'];
			$this->napdf_instance->SetLeftMargin ( $w1 );
			$this->napdf_instance->SetX ( $w1 );
			$this->napdf_instance->MultiCell ( ($right - $w1), 0.13, $str );
			$this->napdf_instance->SetY ( $this->napdf_instance->GetY ( ) + 0.01 );
			}
		
		$y = $this->napdf_instance->GetY ( ) + 0.1;
		
		$this->napdf_instance->SetLineWidth ( 0.01 );
		$this->napdf_instance->Line ( $left + 0.0625, $y, $right, $y );
        
        $left += 0.75;
		$y = $this->napdf_instance->GetY ( ) + 0.25;
		
		$this->napdf_instance->SetFont ( $fontFamily, 'B', $fontSize * 0.75 );
		
		$this->napdf_instance->SetXY ( $left, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_CORRECTIONS1 );
		
		$this->napdf_instance->SetFont ( $fontFamily, '', $fontSize * 0.75 );

		$x = $left + 0.25;
		$y = $this->napdf_instance->GetY ( ) + 0.17;			
		$this->napdf_instance->SetXY ( $x, $y );

		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_CORRECTIONS2 );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_CORRECTIONS3 );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_CORRECTIONS4 );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_CORRECTIONS5 );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$y = $this->napdf_instance->GetY ( );			
				
		$x = $left;
		$y = $this->napdf_instance->GetY ( ) + 0.1;
		$this->napdf_instance->SetXY ( $x, $y );

		$this->napdf_instance->SetFont ( $fontFamily, 'B', $fontSize * 0.75 );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_CORRECTIONS1A );
		
		$this->napdf_instance->SetFont ( $fontFamily, '', $fontSize * 0.75 );

		$x = $left + 0.25;
		$y = $this->napdf_instance->GetY ( ) + 0.17;			
		$this->napdf_instance->SetXY ( $x, $y );

		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_CORRECTIONS2A );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_CORRECTIONS3A );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_CORRECTIONS4A );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$y = $this->napdf_instance->GetY ( );			
	}
	/********************************************************************
	*/
	private function DrawSubcommittees ( )
		{		
		$column_width = (($this->napdf_instance->w - 0.5) / 4) - 0.05;
		
		$top = 0.125;
		$left = 0.125 + (($column_width + 0.1) * 3);
		$right = ($this->napdf_instance->w - 0.25) - $left;
		$y = 0.25;
		
		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->napdf_instance->FontSizePt + 2;
		
		$this->napdf_instance->SetFont ( $fontFamily, 'B', $fontSize * 0.75 );
		
		$this->napdf_instance->SetXY ( $left, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_SUBCOMMITTEE1 );
		
		$this->napdf_instance->SetFont ( $fontFamily, '', $fontSize * 0.75 );

		$x = $left + 0.25;
		$y = $this->napdf_instance->GetY ( ) + 0.17;			
		$this->napdf_instance->SetXY ( $x, $y );

		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_SUBCOMMITTEE2 );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_SUBCOMMITTEE7 );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_SUBCOMMITTEE3 );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_SUBCOMMITTEE4 );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_SUBCOMMITTEE5 );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_SUBCOMMITTEE6 );
		$y = $this->napdf_instance->GetY ( ) + 0.25;			

		$this->napdf_instance->SetFont ( $fontFamily, 'B', $fontSize * 0.75 );
		
		$this->napdf_instance->SetXY ( $left, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_SUBCOMMITTEE1A );
		
		$this->napdf_instance->SetFont ( $fontFamily, '', $fontSize * 0.75 );

		$x = $left + 0.25;
		$y = $this->napdf_instance->GetY ( ) + 0.17;			
		$this->napdf_instance->SetXY ( $x, $y );

		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_SUBCOMMITTEE2A );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_SUBCOMMITTEE3A );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_SUBCOMMITTEE4A );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_SUBCOMMITTEE5A );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_SUBCOMMITTEE6A );
		$y = $this->napdf_instance->GetY ( ) + 0.14;			
		$this->napdf_instance->SetXY ( $x, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_SUBCOMMITTEE6B );
		}
	
	/********************************************************************
	*/
	private function DrawFrontPanel ( $left, $top, $right, $bottom, $date )
	{
		$inTitleGraphic = "../ny_printed_lists/images/NYC_Cover_Logo.jpg";
		$gnyrQR = "../ny_printed_lists/images/newyorkna.org.gif";
		$nycascQR = "../ny_printed_lists/images/nycasc.org.gif";
		
		$y = $top + 0.125;

		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->napdf_instance->FontSizePt;
		
		$this->napdf_instance->SetFont ( $this->font, 'B', ($fontSize - 4) );
		
		$stringWidth = $this->napdf_instance->GetStringWidth ( $date );
		
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );

		$this->napdf_instance->Cell ( 0, 0, $date );
		$y += 0.2;
		
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_NYC_LIST );
		
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );

		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST );
		$y += 0.2;

		$this->napdf_instance->SetFont ( $this->font, 'B', ($fontSize + 4) );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_NYC_LIST_BANNER );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );

		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_BANNER );
		$y += 0.21;
		
		$this->napdf_instance->SetFont ( $this->font, 'B', ($fontSize + 1) );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_NYC_LIST_BANNER_2.' '._PDF_NYC_LIST_BANNER_3 );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_BANNER_2.' '._PDF_NYC_LIST_BANNER_3 );
		
		$this->napdf_instance->Image ( $inTitleGraphic, ($left + 0.75), 1.25, (($right - $left) - 1.5), 0, 'JPG' );
		$this->napdf_instance->Image ( $nycascQR, ($left + 0.125), 1.3, 0.5, 0, 'GIF' );
		$this->napdf_instance->Image ( $gnyrQR, ($right - 0.625), 1.3, 0.5, 0, 'GIF' );
		
		$this->napdf_instance->SetFont ( $this->font, '', ($fontSize - 2) );

		$this->napdf_instance->SetXY ( $left + 0.1, 1.85 );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_QR_A );

		$this->napdf_instance->SetXY ( $right - 0.72, 1.85 );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_QR_B );

		$this->napdf_instance->SetFont ( $fontFamily, 'B', ($fontSize + 1) );
		
		$y = 3.2;
		$cellleft = $left;

		$this->napdf_instance->SetXY ( $cellleft + 0.42, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_HELPLINE_REGION );
		$y += 0.2;

		$this->napdf_instance->SetFont ( $fontFamily, 'B', ($fontSize + 1) );
		
		$this->napdf_instance->SetFont ( $fontFamily, 'B', ($fontSize + 2) );
		$url_string = _PDF_NYC_LIST_URL;
		
		$stringWidth = $this->napdf_instance->GetStringWidth ( $url_string );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );

		$this->napdf_instance->Cell ( 0, 0, $url_string );
		$y += 0.26;

		$cellleft = $left;
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$st_width = $this->napdf_instance->GetStringWidth ( _PDF_NYC_LIST_NAME );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_NAME );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_NYC_LIST_PHONE );
		$this->napdf_instance->SetXY ( $right - $stringWidth, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NYC_LIST_PHONE );
		$y += 0.125;
		
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
	private function RearrangeFormats ( $inFormats )
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
		
		uasort ( $inFormats, 'nyc_napdf::sort_cmp' );
		
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
	*/
	private static function break_meetings_by_day ( $in_meetings_array ) 
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
	private static function translate_duration ( $in_time_string ) 
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
		\brief This is a function that returns the results of an HTTP call to a URI.
		It is a lot more secure than file_get_contents, but does the same thing.
		
		\returns a string, containing the response. Null if the call fails to get any data.
		
		\throws an exception if the call fails.
	*/
	private static function call_curl ( $in_uri,				///< A string. The URI to call.
										$in_post = true,		///< If false, the transaction is a GET, not a POST. Default is true.
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
//		curl_setopt ( $resource, CURLOPT_FOLLOWLOCATION, true );
		
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
			// Cram as much info into the exception as possible.
			throw new Exception ( "curl failure calling $in_uri, ".curl_error ( $resource ).", ".curl_errno ( $resource ) );
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
};
?>