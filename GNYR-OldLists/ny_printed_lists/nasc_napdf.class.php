<?php
/**
	\file nasc_napdf.class.php
	
	\brief This file creates and dumps a Long Island meeting list in PDF form.
*/
ini_set('display_errors', 1);
ini_set('error_reporting', E_ERROR);
// Get the napdf class, which is used to fetch the data and construct the file.
require_once ( dirname ( __FILE__ ).'/tabloid_napdf2.class.php' );

/**
	\brief	This creates and manages an instance of the napdf class, and creates
	the PDF file.
*/
class nasc_napdf extends tabloid_napdf
{
	/********************************************************************
		\brief	The constructor for this class does a lot. It creates the instance of the napdf class, gets the data from the
		server, then sorts it. When the constructor is done, the data is ready to be assembled into a PDF.
		
		If the napdf object does not successfully get data from the server, then it is set to null.
	*/
	function __construct ( $in_http_vars	///< The HTTP parameters we'd like to send to the server.
							)
	{
		$this->font_size = 8;		///< The font size we'll use
		$this->sort_keys = array (	'weekday_tinyint' => true,			///< First, sort by weekday
		                            'start_time' => true,               ///< Next, the meeting start time
									'location_municipality' => true,	///< Next, the town.
									'week_starts' => 2					///< Our week starts on Monday (2)
									);
		
		/// These are the parameters that we send over to the root server, in order to get our meetings.
		$this->out_http_vars = array ('do_search' => 'yes',						///< Do a search
									'bmlt_search_type' => 'advanced',			///< We'll be very specific in our request
									'advanced_service_bodies' => array (		///< We will be asking for meetings in specific Service Bodies.
																		1001,	///< SSAASC
																		1002,	///< NASC
																		1003,	///< ELIASC
																		1004,	///< SSSAC
																		1067    ///< NSLI
																		)
									);
		
		parent::__construct ( $in_http_vars );
	}
	/*************************** INTERFACE FUNCTIONS ***************************/
	
	/********************************************************************
	*/
	function OutputPDF ()
	{
		$d = date ( "Y_m_d" );
		$this->napdf_instance->Output( "NASC_PrintableList_$d.pdf", "I" );
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
		
		uasort ( $inFormats, 'nasc_napdf::sort_cmp' );
		
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
		$ret = false;
		if ( $this->napdf_instance instanceof napdf )
			{
			$page_margins = 0.25;

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
// 				$this->DrawRearPanel ( $backpanel_x_offset, $backpanel_y_offset, $backpanel_max_x_offset, $backpanel_max_y_offset );

				$inPrinter_Date = date ( '\R\e\v\i\s\e\d F, Y' );

				$this->DrawListPage ( 0 );
				$this->DrawListPage ( 1 );
				$this->DrawFrontPanel ( $frontpanel_x_offset, $frontpanel_y_offset, $frontpanel_max_x_offset, $frontpanel_max_y_offset, $inPrinter_Date, $this->napdf_instance->format_data );
				
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
	function DrawFormats ( $left, $top, $right, $bottom, $formats )
	{
	    $maxY = 0;
		$y = $top + PDF_MARGIN;

		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->font_size + 1;
		
		$one_turd = ceil ( count ( $formats ) / 3 );
		$turd = $one_turd;
		$width = ($right - $left) / 3;
		
		$this->napdf_instance->SetFont ( $this->font, 'B', ($fontSize - 1) );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_NASC_LIST_FORMAT_KEY );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );

		$this->napdf_instance->Cell ( 0, 0, _PDF_NASC_LIST_FORMAT_KEY );
		$y += PDF_MARGIN;
		
		$count = count ( $formats );
		$left += 0.25;
		$w1 = $left + 0.15;
		$fSize = $fontSize - 3;
		
		$this->napdf_instance->SetY ( $y );
        
        $count = 0;
        
		foreach ( $formats as $format )
			{
			if ( $count == $turd )
			    {
			    $turd += $count;
		        $this->napdf_instance->SetY ( $y );
		        $left += $width;
		        $w1 += $width;
		        $right = $left + $width;
			    }
			$count++;
			$this->napdf_instance->SetFont ( $this->font, 'B', $fSize );
			$this->napdf_instance->SetLeftMargin ( $left );
			$str = $format['key_string'];
			$this->napdf_instance->SetX ( $left );
			$this->napdf_instance->Cell ( 0, 0.13, $str );
			$this->napdf_instance->SetFont ( $this->font, '', $fSize );
			$str = $format['name_string'];
			$this->napdf_instance->SetLeftMargin ( $w1 );
			$this->napdf_instance->SetX ( $w1 );
			$this->napdf_instance->MultiCell ( ($right - $w1), 0.13, $str );
			
			$maxY = max ( $maxY, $this->napdf_instance->GetY() );
			}
		
		$this->napdf_instance->SetY ( $maxY + 0.05 );
    }
    
	/********************************************************************
	*/
	function DrawRearPanel ( $left, $top, $right, $bottom, $formats )
	{
	}
	
	/********************************************************************
	*/
	/********************************************************************
	*/
	function DrawSubcommittees ( )
		{
		$y = $this->napdf_instance->GetY();
		
		if ( $y > PDF_MARGIN )
		    {
		    $y += PDF_MARGIN;
		    }
		$column_width = (($this->napdf_instance->w - 0.5) / 4) - PDF_MARGIN;
		$left = PDF_MARGIN + (($column_width + 0.25) * 3);
		$bottom = $this->napdf_instance->h - PDF_MARGIN;
		$right = $left + $column_width;
		
		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->font_size;

		$s_array = array();
		$na_dom = new DOMDocument;
		if ( $na_dom )
			{
			if ( @$na_dom->loadHTML($this->call_curl ( "http://mail.qchron.com/nasc.html" )) )
				{
				$div_contents = $na_dom->getElementByID ( "meeting_times" );
				
				if ( $div_contents )
					{
					$p_list = $div_contents->getElementsByTagName ( "p" );
					if ( $p_list && $p_list->length )
						{
						for ( $i = 0; $i < $p_list->length; $i++ )
							{
							$the_item = $p_list->item($i);
							if ( $the_item )
								{
								$a = null;
								
								if ( "first" == $the_item->getAttribute ( "class" ) )
									{
									$p_list2 = $the_item->getElementsByTagName ( 'b' );
									
									if ( !$p_list2 || !$p_list2->item(0) )
									    {
									    $p_list2 = $the_item->getElementsByTagName ( 'strong' );
									    }
									    
									if ( $p_list2 && $p_list2->item(0) && $p_list2->item(0)->nodeValue )
									    {
                                        $a['_name'] = $p_list2->item(0)->nodeValue;
                                        $a['_description'] = '';
                                        
                                        while ( $p_list->item($i + 1) && ("first" != $p_list->item($i + 1)->getAttribute ( "class" )) )
                                            {
                                            if ( $a['_description'] )
                                                {
                                                $a['_description'] .= "\n";
                                                }
                                            $a['_description'] .= $p_list->item(++$i)->nodeValue;
                                            }
                                        }
									}
								
								if ( $a )
								    {
								    array_push ( $s_array, $a );
								    }
								}
							}
						}
					}
				}
			}

		if ( is_array ( $s_array ) && count ( $s_array ) )
			{
			$heading_height = $fontSize + 1;
			$height = ($heading_height / 72) * 1.07;
			
            $this->napdf_instance->SetFillColor ( 0 );
            $this->napdf_instance->SetTextColor ( 255 );
            $this->napdf_instance->Rect ( $left, $y, ($right - $left), $height, "F" );
            $fSize = $heading_height;
            $this->napdf_instance->SetFont ( $fontFamily, 'B', $fSize );
            $displayString = _PDF_NASC_LIST_SUBCOMMITTEE_HEADING;
            $stringWidth = $this->napdf_instance->GetStringWidth ( $displayString ) + 0.1;
            $cellleft = (($right + $left) - $stringWidth) / 2;
            $this->napdf_instance->SetXY ( $cellleft, $y + 0.005 );
            $this->napdf_instance->Cell ( 0, $height, $displayString );
			$y += $height + .01;
			
			for ( $c = 0; $c < count ( $s_array ); $c++ )
				{
				$this->napdf_instance->SetFillColor ( 0 );
				$this->napdf_instance->SetTextColor ( 255 );
				$this->napdf_instance->Rect ( $left, $y, ($right - $left), $height, "F" );
				$done = false;
				$fSize = $heading_height;
				do
				    {
				    $this->napdf_instance->SetFont ( $fontFamily, 'B', $fSize );
                    $stringWidth = $this->napdf_instance->GetStringWidth ( $s_array[$c]['_name'] ) + 0.1;
                    $cellleft = (($right + $left) / 2) - ($stringWidth / 2);
                    
                    if ( ($cellleft > $left) && (($cellleft + $stringWidth) < $right) )
                        {
                        $this->napdf_instance->SetXY ( $cellleft, $y + 0.005 );
                        $this->napdf_instance->Cell ( 0, $height, $s_array[$c]['_name'] );
                        $done = true;
                        }
                    else
                        {
                        $fSize -= 0.1;
                        }
                    } while ( !$done );
                    
				$y += $height + .01;

				$this->napdf_instance->SetTextColor ( 0 );
				$this->napdf_instance->SetFont ( $fontFamily, '', ($fontSize) );
				$this->napdf_instance->SetLeftMargin ( $left );
				$this->napdf_instance->SetXY ( $left, $y );
				$this->napdf_instance->MultiCell ( ($right - $left), ($fontSize) / 72, $s_array[$c]['_description'], 0, "L" );
				$y = $this->napdf_instance->GetY ( ) + 0.1;
				}
			}
		}
	
	/********************************************************************
	*/
	function DrawFrontPanel ( $left, $top, $right, $bottom, $date, $formats )
	{
		$inTitleGraphic = dirname(__FILE__)."/images/ELI_Cover_Logo.png";
		$nawsQR = dirname(__FILE__)."/images/NAWSMeetingsQR.png";
		$bmltQR = dirname(__FILE__)."/images/BMLTMeetingsAppQR.png";
		$graphicSize = 1.5;
		
		$y = $top + PDF_MARGIN;

		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->font_size;
		
		$this->napdf_instance->SetFont ( $this->font, 'B', $fontSize + 4 );
        $displayString = _PDF_NASC_LIST_MAIN_TITLE_TOP;
		$stringWidth = $this->napdf_instance->GetStringWidth ( $displayString );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $displayString );
		$y += 0.13;
        
		$this->napdf_instance->SetFont ( $this->font, 'B', $fontSize + 1 );
        $displayString = _PDF_NASC_LIST_MAIN_TITLE_MIDDLE;
		$stringWidth = $this->napdf_instance->GetStringWidth ( $displayString );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $displayString );
		$y += 0.13;
        
		$this->napdf_instance->SetFont ( $this->font, 'B', $fontSize + 4 );
        $displayString = _PDF_NASC_LIST_MAIN_TITLE_BOTTOM;
		$stringWidth = $this->napdf_instance->GetStringWidth ( $displayString );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $displayString );
		$y += 0.2;
        
		$this->napdf_instance->SetFont ( $this->font, 'B', $fontSize + 2 );
        $displayString = _PDF_NASC_LIST_SUB_TITLE;
		$stringWidth = $this->napdf_instance->GetStringWidth ( $displayString );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $displayString );
		$y += 0.15;
        
		$this->napdf_instance->SetFont ( $this->font, 'IB', $fontSize - 1 );
		$stringWidth = $this->napdf_instance->GetStringWidth ( $date );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $date );
		$y += 0.1;
        
		$cellleft = (($right + $left) - $graphicSize) / 2.0;
		$this->napdf_instance->Image ( $inTitleGraphic, $cellleft, $y, $graphicSize, 0, 'PNG' );
		$this->napdf_instance->Image ( $nawsQR, ($left + PDF_MARGIN), 1.3, 0.75, 0, 'PNG' );
		$this->napdf_instance->Image ( $bmltQR, ($right - (PDF_MARGIN + 0.75)), 1.3, 0.75, 0, 'PNG' );
		
		$this->napdf_instance->SetFont ( $this->font, 'IB', $fontSize - 2 );
		$l = $left + PDF_MARGIN;
		$r = $l + 0.66;
        $displayString = _PDF_NASC_LIST_QR_LABEL_NAWS;
		$stringWidth = $this->napdf_instance->GetStringWidth ( $displayString );
		$cellleft = (($r + $l) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, 2.125 );
		$this->napdf_instance->Cell ( 0, 0, $displayString );
		
		$r = $right - PDF_MARGIN;
		$l = $r - 0.84;
        $displayString = _PDF_NASC_LIST_QR_LABEL_BMLT;
		$stringWidth = $this->napdf_instance->GetStringWidth ( $displayString );
		$cellleft = (($r + $l) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, 2.125 );
		$this->napdf_instance->Cell ( 0, 0, $displayString );
		
		$y += ($graphicSize + 0.15);

		$this->napdf_instance->SetFont ( $this->font, 'B', $fontSize + 2 );
        $displayString = _PDF_NASC_LIST_HELPLINE;
		$stringWidth = $this->napdf_instance->GetStringWidth ( $displayString );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $displayString );
		$y += 0.15;

		$this->napdf_instance->SetFont ( $this->font, 'B', $fontSize + 2 );
        $displayString = _PDF_NASC_LIST_WEBSITE;
		$stringWidth = $this->napdf_instance->GetStringWidth ( $displayString );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $displayString );
		$y += 0.1;
        
		$this->DrawFormats ( $left, $y, $right, $bottom, $formats );
		$y = $this->napdf_instance->GetY();
		$y += 0.1;
		
		$this->napdf_instance->SetFont ( $this->font, 'B', $fontSize + 4 );
		
		$cellleft = $left;
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$st_width = $this->napdf_instance->GetStringWidth ( _PDF_NASC_LIST_NAME );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NASC_LIST_NAME );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_NASC_LIST_PHONE );
		$this->napdf_instance->SetXY ( $right - $stringWidth, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_NASC_LIST_PHONE );
		$y += PDF_MARGIN;
		
		$this->napdf_instance->SetFont ( $fontFamily, '', $fontSize );

		$this->napdf_instance->SetLineWidth ( 0.02 );
		$this->napdf_instance->Line ( $left + 0.0625, $y, $right, $y );
		
		while ( $y < ($bottom - 0.5) )
			{
			$y += 0.3;
			$this->napdf_instance->Line ( $left + 0.0625, $y, $right, $y );
			}
		
		$y += 0.125;
		$this->napdf_instance->SetFont ( $this->font, 'I', $fontSize - 1 );
		$displayString = _PDF_NASC_LIST_DISCLAIMER_LINE_1;
		$stringWidth = $this->napdf_instance->GetStringWidth ( $displayString );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $displayString );
		$y += 0.1;
		$displayString = _PDF_NASC_LIST_DISCLAIMER_LINE_2;
		$stringWidth = $this->napdf_instance->GetStringWidth ( $displayString );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $displayString );
		$y += 0.1;
		$displayString = _PDF_NASC_LIST_DISCLAIMER_LINE_3;
		$stringWidth = $this->napdf_instance->GetStringWidth ( $displayString );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $displayString );
	}
};
?>