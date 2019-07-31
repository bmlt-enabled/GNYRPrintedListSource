<?php
/**
	\file sas_napdf.class.php
	
	\brief This file creates and dumps a Rockland meeting list in PDF form.
*/
ini_set('display_errors', 1);
ini_set('error_reporting', E_ERROR);
// Get the napdf class, which is used to fetch the data and construct the file.
require_once ( dirname ( __FILE__ ).'/usletter2_napdf.class.php' );

define ( "_PDF_RAS_LIST_HELPLINE_REGION", "Regional Helpline: (212) 929-NANA (6262)" );
define ( "_PDF_RAS_LIST_ROOT_URI", "https://bmlt.newyorkna.org/main_server/" );
define ( "_PDF_RAS_LIST_DIRECTIONS", "Directions to Meeting Venues" );
define ( "_PDF_RAS_LIST_DIRECTIONS_CONT", "Directions to Meeting Venues (Continued)" );
define ( "_PDF_RAS_LIST_MEETINGS_DIRECTIONS_URI", "http://newyorkna.org/Events_and_Meetings/rasc_meeting_directions.html" );
define ( "_PDF_RAS_LIST_SUBCOMMITTEES_URI", "http://newyorkna.org/Events_and_Meetings/rasc_subcommittee_meetings.html" );
define ( "_PDF_RAS_LIST_SUBCOMMITTEES", "Rockland Area Service Committee Meetings" );
define ( "_PDF_RAS_LIST", "Meeting List Produced by the Rockland Area Service Committee" );
define ( "_PDF_RAS_LIST_BANNER", "Narcotics Anonymous Meetings" );
define ( "_PDF_RAS_LIST_BANNER_2", "In" );
define ( "_PDF_RAS_LIST_BANNER_3", "Rockland County, New York" );
define ( "_PDF_RAS_LIST_URL", "Web Site: http://rocklandna.org" );
define ( "_PDF_RAS_LIST_EMAIL", "Email: rocklandnyna@gmail.com" );
define ( "_PDF_RAS_LIST_SUGG_HEADER", "Some Basic Suggestions:" );
define ( "_PDF_RAS_LIST_SUGG_1", "Make 90 meetings in 90 days. If that sounds like a lot, make a meeting a day and the 90 will take care of itself." );
define ( "_PDF_RAS_LIST_SUGG_2", "Get a sponsor. A sponsor is another recovering addict just like you with a working knowledge of the 12 steps and 12 traditions of NA." );
define ( "_PDF_RAS_LIST_SUGG_3", "Get phone numbers. Dial them, don't file them." );
define ( "_PDF_RAS_LIST_SUGG_4", "Get involved.  Get a commitment such as helping set up before the meeting or cleaning up when the meeting is over. " );
define ( "_PDF_RAS_LIST_SUGG_5", "Come early and stay late." );
define ( "_PDF_RAS_LIST_SUGG_6", "Join a home group. A home group is a meeting that you attend regularly where people get to know you and you to know them. " );
define ( "_PDF_RAS_LIST_SUGG_7", "Most importantly, don't pick up!" );
define ( "_PDF_RAS_LIST_SP_HEADER", "The Serenity Prayer:" );
define ( "_PDF_RAS_LIST_SP", "God, grant me\nSERENITY to accept the things I cannot Change,\nCOURAGE to change the things I can, and\nWISDOM to know the difference." );
define ( "_PDF_RAS_LIST_NS", "All Meetings are Non-Smoking, Including Electronic Cigarettes and Vaping." );

/**
	\brief	This creates and manages an instance of the napdf class, and creates
	the PDF file.
*/
class rasc_napdf extends usletter2_napdf
{
	/********************************************************************
		\brief	The constructor for this class does a lot. It creates the instance of the napdf class, gets the data from the
		server, then sorts it. When the constructor is done, the data is ready to be assembled into a PDF.
		
		If the napdf object does not successfully get data from the server, then it is set to null.
	*/
	function __construct ( $in_http_vars	///< The HTTP parameters we'd like to send to the server.
							)
	{
		$this->font_size = 9;		///< The font size we'll use
        $this->page_sections = 3;     ///< The number of sections for the page.
		$this->sort_keys = array (	'weekday_tinyint' => true,			///< First, sort by weekday
		                            'start_time' => true,               ///< Next, the meeting start time
									'location_municipality' => true,	///< Next, the town.
									'week_starts' => 1					///< Our week starts on Sunday (1)
									);
		
		/// These are the parameters that we send over to the root server, in order to get our meetings.
		$this->out_http_vars = array (  'services' => array (   ///< We will be asking for meetings in specific Service Bodies.
													            1012	///< RASC
											                ),
										'sort_key' => 'time'        
									);
        
		parent::__construct ( _PDF_RAS_LIST_ROOT_URI, $in_http_vars );
	}
	
	/********************************************************************
	*/
	function OutputPDF ()
	{
		$d = date ( "Y_m_d" );
		$this->napdf_instance->Output( "Rockland_PrintableList_$d.pdf", "I" );
	}

	/********************************************************************
		\brief This function actually assembles the PDF. It does not output it.
		
		\returns a boolean. true if successful.
	*/
	function AssemblePDF ()
	{
		$ret = false;
		if ( $this->napdf_instance instanceof napdf2 )
			{
			$page_margins = 0.25;

			$meeting_data =& $this->napdf_instance->meeting_data;
			
			if ( $meeting_data )
				{
				// Calculate the overall layout of the list
                $leftgutterAdjust = -0.1; // This is a small left-shift for most printers.
				
				// The front and back panels are quarter page panels.
				$panelpage['margin'] = $page_margins;
				$panelpage['height'] = $this->napdf_instance->h - ($panelpage['margin'] * 2);
				$panelpage['width'] = ($this->napdf_instance->w / $this->page_sections) - ($panelpage['margin'] * ($this->page_sections - 2));
				
				// List pages are half page panels.
				$listpage['margin'] = $page_margins;
				$listpage['height'] = $panelpage['height'];
				$listpage['width'] = $panelpage['width'];
				
				// These are the actual drawing areas.
				
				// The panel that is on the back of the folded list.
				$backpanel_x_offset = $panelpage['margin'] + $panelpage['width'];
				$backpanel_max_x_offset = $backpanel_x_offset + $panelpage['width'];

				// The panel that is up front of the folded list.
				$frontpanel_x_offset = $backpanel_max_x_offset + ($panelpage['margin'] * 2);
				$frontpanel_max_x_offset = $frontpanel_x_offset + $panelpage['width'] - $panelpage['margin'];
				$frontpanel_y_offset = $panelpage['margin'];
				$frontpanel_max_y_offset = $frontpanel_y_offset + $panelpage['height'];
				
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
				$fSize = $this->font_size;
								
				foreach ( $meeting_data as &$meeting )
					{
					if ( isset ( $meeting['location_text'] ) && isset ( $meeting['location_street'] ) )
					    {
					    $meeting['location'] = $meeting['location_text'].', '.$meeting['location_street'];
					    }
					}
				
				$this->napdf_instance->AddPage ( );
				// $this->DrawFoldGuides ( $panelpage['margin'] );
				$this->DrawRearPanel ( $panelpage['margin'] + $leftgutterAdjust, $panelpage['margin'], $panelpage['height'], $panelpage['width'] + $leftgutterAdjust, $panelpage['margin'] );

				$this->napdf_instance->SetFont ( $this->font, 'B', $this->font_size + 1 );
				$inPrinter_Date = date ( '\R\e\v\i\s\e\d F, Y' );

				$this->DrawFrontPanel ( $frontpanel_x_offset + $leftgutterAdjust, $frontpanel_y_offset, $frontpanel_max_x_offset + $leftgutterAdjust, $frontpanel_max_y_offset, $inPrinter_Date );
				
				$this->napdf_instance->AddPage ( );
				$this->DrawFoldGuides ( $panelpage['margin'] );

				$this->font_size += 1.5;

				$starting_left = $listpage['margin'] + $leftgutterAdjust;
				$starting_right = $listpage['width'];

				$pages = 0;

				while ( !$this->pos['end'] )
					{
					$pages += 1;
					$this->DrawListPage ( $starting_left, $listpage['margin'], $starting_right, $listpage['height'], $listpage['margin'] );
					$starting_left = ($starting_right + ($listpage['margin'] * 2));
					$starting_right = $starting_left + $listpage['width'] - $listpage['margin'];
					}

                $y = (($pages > 2) ? $this->napdf_instance->GetY() : 0)  + $listpage['margin'];
   		        $this->DrawFormats ( $frontpanel_x_offset + $leftgutterAdjust, $y, $frontpanel_max_x_offset + $leftgutterAdjust, $frontpanel_max_y_offset, $this->napdf_instance->format_data, false, true );
				$this->font_size -= 1.5;

				$this->DrawSubcommittees ( $frontpanel_x_offset + $leftgutterAdjust, $this->napdf_instance->GetY(), $frontpanel_max_x_offset + $leftgutterAdjust, $frontpanel_max_y_offset );
				$this->DrawSuggestions ( $frontpanel_x_offset + $leftgutterAdjust, $this->napdf_instance->GetY(), $frontpanel_max_x_offset + $leftgutterAdjust, $frontpanel_max_y_offset );
				}

			$ret = true;
			}
		
		return $ret;
	}
	
	/*************************** INTERFACE FUNCTIONS ***************************/
    
	/********************************************************************
	*/
	function DrawRearPanel ( $left, $top, $bottom, $columnWidth, $columnMargin )
	{
		$parsed_file = $this->GetParsedHTMLFile(_PDF_RAS_LIST_MEETINGS_DIRECTIONS_URI);
		if ( is_array ( $parsed_file ) && count ( $parsed_file) )
			{
			$y = $top;
			$right = $columnWidth;

			$fontFamily = $this->font;
			$fontSize = $this->font_size;

			$this->napdf_instance->SetFont ( $fontFamily, 'B', $fontSize );
			$headerString = _PDF_RAS_LIST_DIRECTIONS;

			$stringWidth = $this->napdf_instance->GetStringWidth ( $headerString );
			
			$cellleft = (($right + $left) - $stringWidth) / 2;
			
	        $this->napdf_instance->SetFillColor ( 0 );
	        $this->napdf_instance->SetTextColor ( 255 );

			$this->napdf_instance->Rect ( $left, $y, ($right - $left), 0.16, "F" );

			$y += 0.08;

			$this->napdf_instance->SetXY ( $cellleft, $y );
			$this->napdf_instance->Cell ( 0, 0, $headerString );
			$heading_height = $fontSize + 1;
			$height = ($heading_height / 72) * 1.07;
		
			$y += 0.08;
	        $this->napdf_instance->SetDrawColor ( 255 );
	        $this->napdf_instance->SetLineWidth ( 0.01 );
			$this->napdf_instance->Line ( $left + 0.03, $y, $right - 0.03, $y );
			$averageHeight = 0;
			
			foreach ( $parsed_file as $venue )
				{
				$starting_y = $y;

				$this->napdf_instance->SetFont ( $fontFamily, 'B', $fontSize - 1 );
				$this->napdf_instance->SetFillColor ( 0 );
				$this->napdf_instance->SetTextColor ( 255 );
				$this->napdf_instance->Rect ( $left, $y, ($right - $left), $height, "F" );
				$stringWidth = $this->napdf_instance->GetStringWidth ( $venue['header'] );
				$cellleft = (($right + $left) - $stringWidth) / 2;
				$this->napdf_instance->SetXY ( $cellleft, $y + 0.005 );
				$this->napdf_instance->Cell ( 0, $height, $venue['header'] );

				foreach ( $venue['body'] as $line )
					{
					$y += $height + .01;

					$this->napdf_instance->SetTextColor ( 0 );
					$this->napdf_instance->SetFont ( $fontFamily, '', ($fontSize) );
					$this->napdf_instance->SetLeftMargin ( $left );
					$this->napdf_instance->SetXY ( $left, $y );
					$this->napdf_instance->MultiCell ( ($right - $left), ($fontSize) / 72, $line, 0, "L" );
					}

				$y = $this->napdf_instance->GetY ( ) + 0.08;

				$unitHeight = $y - $starting_y;
				if ( 0 == $averageHeight )
					{
					$averageHeight = $unitHeight;
					}
				else
					{
					$averageHeight = ($averageHeight + $unitHeight) / 2;
					}

				if ( ($bottom - $y) < $averageHeight )
					{
					$y = $top;
					$left += $columnWidth + $columnMargin;
					$right += $columnWidth + $columnMargin;
					$this->napdf_instance->SetFont ( $fontFamily, 'B', $fontSize );
					$headerString = _PDF_RAS_LIST_DIRECTIONS_CONT;

					$stringWidth = $this->napdf_instance->GetStringWidth ( $headerString );
					
					$cellleft = (($right + $left) - $stringWidth) / 2;
					
			        $this->napdf_instance->SetFillColor ( 0 );
			        $this->napdf_instance->SetTextColor ( 255 );

					$this->napdf_instance->Rect ( $left, $y, ($right - $left), 0.16, "F" );

					$y += 0.08;

					$this->napdf_instance->SetXY ( $cellleft, $y );
					$this->napdf_instance->Cell ( 0, 0, $headerString );
					$heading_height = $fontSize + 1;
					$height = ($heading_height / 72) * 1.07;
				
					$y += 0.08;
			        $this->napdf_instance->SetDrawColor ( 255 );
	        		$this->napdf_instance->SetLineWidth ( 0.01 );
					$this->napdf_instance->Line ( $left + 0.03, $y, $right - 0.03, $y );
					}
				}
			}
        $this->DrawSerenityPrayer ( $left, $this->napdf_instance->GetY(), $right, $bottom );
		$fontFamily = $this->napdf_instance->FontFamily;
		$this->napdf_instance->SetFont ( $fontFamily, 'I', 7 );
	    $display = _PDF_RAS_LIST_NS;
		$stringWidth = $this->napdf_instance->GetStringWidth ( $display );
		$cellleft = (($right + $left) - $stringWidth) / 2;
		$this->napdf_instance->SetXY ( $cellleft, $bottom );        
	    $this->napdf_instance->Cell ( 0, 0, $display );
	}
	
	/********************************************************************
	*/
	function DrawSubcommittees ( $left, $y, $right, $bottom )
		{
		$y += 0.125;

		$parsed_file = $this->GetParsedHTMLFile(_PDF_RAS_LIST_SUBCOMMITTEES_URI);

		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->font_size;

		$this->napdf_instance->SetFont ( $fontFamily, 'B', ($fontSize) );
		$headerString = _PDF_RAS_LIST_SUBCOMMITTEES;

		$stringWidth = $this->napdf_instance->GetStringWidth ( $headerString );
		
		$cellleft = (($right + $left) - $stringWidth) / 2;
		
        $this->napdf_instance->SetFillColor ( 0 );
        $this->napdf_instance->SetTextColor ( 255 );

		$this->napdf_instance->Rect ( $left, $y, ($right - $left), 0.16, "F" );

		$y += 0.08;

		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $headerString );
		
		$y += 0.08;
        $this->napdf_instance->SetDrawColor ( 255 );
	   	$this->napdf_instance->SetLineWidth ( 0.01 );
		$this->napdf_instance->Line ( $left + 0.03, $y, $right - 0.03, $y );
        
		if ( is_array ( $parsed_file ) && count ( $parsed_file ) )
			{
			$heading_height = $fontSize + 1;
			$height = ($heading_height / 72) * 1.07;
			
			foreach ( $parsed_file as $subcommittee )
				{
				$this->napdf_instance->SetFont ( $fontFamily, 'B', $fontSize - 1 );
				$this->napdf_instance->SetFillColor ( 0 );
				$this->napdf_instance->SetTextColor ( 255 );
				$this->napdf_instance->Rect ( $left, $y, ($right - $left), $height, "F" );
				$stringWidth = $this->napdf_instance->GetStringWidth ( $subcommittee['header'] );
				$cellleft = (($right + $left) - $stringWidth) / 2;
				$this->napdf_instance->SetXY ( $cellleft, $y + 0.005 );
				$this->napdf_instance->Cell ( 0, $height, $subcommittee['header'] );

				foreach ( $subcommittee['body'] as $line )
					{
					$y += $height + .01;

					$this->napdf_instance->SetTextColor ( 0 );
					$this->napdf_instance->SetFont ( $fontFamily, '', ($fontSize) );
					$this->napdf_instance->SetLeftMargin ( $left );
					$this->napdf_instance->SetXY ( $left, $y );
					$this->napdf_instance->MultiCell ( ($right - $left), ($fontSize) / 72, $line, 0, "L" );
					}
				$y = $this->napdf_instance->GetY ( ) + 0.1;
				}
			}
		}
	
	/********************************************************************
	*/
	function DrawSuggestions ( $left, $y, $right, $bottom )
		{
		$y += 0.125;
		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->font_size;

		$this->napdf_instance->SetFont ( $fontFamily, 'B', ($fontSize) );
		$headerString = _PDF_RAS_LIST_SUGG_HEADER;

		$stringWidth = $this->napdf_instance->GetStringWidth ( $headerString );
		
		$cellleft = (($right + $left) - $stringWidth) / 2;
		
        $this->napdf_instance->SetFillColor ( 0 );
        $this->napdf_instance->SetTextColor ( 255 );

		$this->napdf_instance->Rect ( $left, $y, ($right - $left), 0.16, "F" );

		$y += 0.08;

		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $headerString );
		
		$y += 0.08;
        $this->napdf_instance->SetFillColor ( 255 );
        $this->napdf_instance->SetTextColor ( 0 );
		$this->napdf_instance->SetFont ( $fontFamily, 'I', ($fontSize) );
		
		$y = $this->napdf_instance->GetY() + 0.1;
        $suggestion = _PDF_RAS_LIST_SUGG_1;
        $this->napdf_instance->SetXY ( $left, $y );
        $this->napdf_instance->MultiCell ( ($right - $left), ($fontSize) / 72, $suggestion, 0, "L" );
		
		$y = $this->napdf_instance->GetY() + 0.1;
        $suggestion = _PDF_RAS_LIST_SUGG_2;
        $this->napdf_instance->SetXY ( $left, $y );
        $this->napdf_instance->MultiCell ( ($right - $left), ($fontSize) / 72, $suggestion, 0, "L" );
		
		$y = $this->napdf_instance->GetY() + 0.1;
        $suggestion = _PDF_RAS_LIST_SUGG_3;
        $this->napdf_instance->SetXY ( $left, $y );
        $this->napdf_instance->MultiCell ( ($right - $left), ($fontSize) / 72, $suggestion, 0, "L" );
		
		$y = $this->napdf_instance->GetY() + 0.1;
        $suggestion = _PDF_RAS_LIST_SUGG_4;
        $this->napdf_instance->SetXY ( $left, $y );
        $this->napdf_instance->MultiCell ( ($right - $left), ($fontSize) / 72, $suggestion, 0, "L" );
		
		$y = $this->napdf_instance->GetY() + 0.1;
        $suggestion = _PDF_RAS_LIST_SUGG_5;
        $this->napdf_instance->SetXY ( $left, $y );
        $this->napdf_instance->MultiCell ( ($right - $left), ($fontSize) / 72, $suggestion, 0, "L" );
		
		$y = $this->napdf_instance->GetY() + 0.1;
        $suggestion = _PDF_RAS_LIST_SUGG_6;
        $this->napdf_instance->SetXY ( $left, $y );
        $this->napdf_instance->MultiCell ( ($right - $left), ($fontSize) / 72, $suggestion, 0, "L" );
		
		$y = $this->napdf_instance->GetY() + 0.1;
        $suggestion = _PDF_RAS_LIST_SUGG_7;
        $this->napdf_instance->SetXY ( $left, $y );
        $this->napdf_instance->MultiCell ( ($right - $left), ($fontSize) / 72, $suggestion, 0, "L" );
		}
	
	/********************************************************************
	*/
	function DrawSerenityPrayer ( $left, $y, $right, $bottom )
		{
		$y += 0.5;
		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->font_size;

		$this->napdf_instance->SetFont ( $fontFamily, 'B', ($fontSize + 1) );
		$headerString = _PDF_RAS_LIST_SP_HEADER;

		$stringWidth = $this->napdf_instance->GetStringWidth ( $headerString );
		
		$cellleft = (($right + $left) - $stringWidth) / 2;
		
        $this->napdf_instance->SetFillColor ( 0 );
        $this->napdf_instance->SetTextColor ( 255 );

		$this->napdf_instance->Rect ( $left, $y, ($right - $left), 0.16, "F" );

		$y += 0.08;

		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $headerString );
		
		$y += 0.08;
        $this->napdf_instance->SetFillColor ( 255 );
        $this->napdf_instance->SetTextColor ( 0 );
		$this->napdf_instance->SetFont ( $fontFamily, 'I', $fontSize );
		
		$y = $this->napdf_instance->GetY() + 0.1;
        $this->napdf_instance->SetXY ( $left, $y );
        $this->napdf_instance->MultiCell ( ($right - $left), ($fontSize) / 50, _PDF_RAS_LIST_SP, 0, "L" );
		}
	
	/********************************************************************
	*/
	function DrawFrontPanel ( $left, $top, $right, $bottom, $date )
	{
		$inTitleGraphic = "../ny_printed_lists/images/RASC_Cover_Logo.png";
		
		$y = $top + PDF_MARGIN;

		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->font_size - 1.5;
		
		$this->napdf_instance->SetFont ( $this->font, 'B', $fontSize - 1 );
		
		$stringWidth = $this->napdf_instance->GetStringWidth ( $date );
		
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );

		$this->napdf_instance->Cell ( 0, 0, $date );
		$y += 0.1;
		
		$this->napdf_instance->SetFont ( $this->font, 'B', $fontSize - 0.5 );
		
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_RAS_LIST );
		
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );

		$this->napdf_instance->Cell ( 0, 0, _PDF_RAS_LIST );
		$y += 0.2;

		$this->napdf_instance->SetFont ( $this->font, 'B', ($fontSize + 7) );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_RAS_LIST_BANNER );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );

		$this->napdf_instance->Cell ( 0, 0, _PDF_RAS_LIST_BANNER );
		$y += 0.2;
		
		$this->napdf_instance->SetFont ( $this->font, 'B', $fontSize + 1 );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_RAS_LIST_BANNER_2.' '._PDF_RAS_LIST_BANNER_3 );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_RAS_LIST_BANNER_2.' '._PDF_RAS_LIST_BANNER_3 );
		
		$this->napdf_instance->Image ( $inTitleGraphic, ($left + 1.0), 1.0, 1.25, 1.25, 'PNG' );

		$this->napdf_instance->SetFont ( $fontFamily, 'B', ($fontSize + 4.75) );
		
		$y = 2.5;

		$url_string = _PDF_RAS_LIST_URL;
		$stringWidth = $this->napdf_instance->GetStringWidth ( $url_string );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $url_string );
		$y += 0.2;

		$url_string = _PDF_RAS_LIST_EMAIL;
		$stringWidth = $this->napdf_instance->GetStringWidth ( $url_string );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $url_string );
		$y += 0.2;
		
		$this->napdf_instance->SetFont ( $fontFamily, '', ($fontSize + 3) );
		
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_RAS_LIST_HELPLINE_REGION );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_RAS_LIST_HELPLINE_REGION );

		$this->napdf_instance->SetFont ( $this->font, 'B', $this->font_size + 1 );
		
		$this->DrawPhoneList( $left, $this->napdf_instance->GetY(), $right, $bottom );
	}
};
?>