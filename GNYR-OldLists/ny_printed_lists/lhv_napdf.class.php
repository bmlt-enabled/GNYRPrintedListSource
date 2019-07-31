<?php
/**
	\file lhv_napdf.class.php
	
	\brief This file creates and dumps a Lower Hudson Valley meeting list in PDF form.
*/
ini_set('display_errors', 1);
ini_set('error_reporting', E_ERROR);
// Get the napdf class, which is used to fetch the data and construct the file.
require_once ( dirname ( __FILE__ ).'/usletter_napdf.class.php' );

/**
	\brief	This creates and manages an instance of the napdf class, and creates
	the PDF file.
*/
class lhv_napdf extends usletter_napdf
{
	/********************************************************************
		\brief	The constructor for this class does a lot. It creates the instance of the napdf class, gets the data from the
		server, then sorts it. When the constructor is done, the data is ready to be assembled into a PDF.
		
		If the napdf object does not successfully get data from the server, then it is set to null.
	*/
	function __construct ( $in_http_vars	///< The HTTP parameters we'd like to send to the server.
							)
	{
		$this->font_size = 13;		///< The font size we'll use
		$this->sort_keys = array (	'weekday_tinyint' => true,			///< First, sort by weekday
		                            'start_time' => true,               ///< Next, the meeting start time
									'location_municipality' => true,	///< Next, the town.
									'week_starts' => 2					///< Our week starts on Monday (2)
									);
		
		/// These are the parameters that we send over to the root server, in order to get our meetings.
		$this->out_http_vars = array ('do_search' => 'yes',						///< Do a search
									'bmlt_search_type' => 'advanced',			///< We'll be very specific in our request
									'advanced_service_bodies' => array (		///< We will be asking for meetings in specific Service Bodies.
																		1045	///< LHV
																		)
									);
		
		parent::__construct ( $in_http_vars );
	}
	/********************************************************************
	*/
	function OutputPDF ()
	{
		$d = date ( "Y_m_d" );
		$this->napdf_instance->Output( "OpenArms_PrintableList_$d.pdf", "I" );
	}
	
	/*************************** INTERFACE FUNCTIONS ***************************/
	/********************************************************************
		\brief This function actually assembles the PDF. It does not output it.
		
		\returns a boolean. true if successful.
	*/
	function AssemblePDF ()
	{
		$ret = false;
	    $this->blockColor = array ( 20, 128, 10 );
	    $this->blockTextColor = array ( 255, 255, 255 );
	    
		if ( $this->napdf_instance instanceof napdf )
			{
			$this->page_margins = PDF_MARGIN;

			$meeting_data =& $this->napdf_instance->meeting_data;
			
			if ( $meeting_data )
				{
				// Calculate the overall layout of the list
				$this->columns = PDF_COLUMNS;
				
		        $this->column_width = (($this->napdf_instance->w - ($this->page_margins * 2)) / 3) - ($this->page_margins * 2);
		        
				// The front and back panels are third page panels.
				$panelpage['margin'] = $listpage['margin'] = $this->page_margins;
				$panelpage['height'] = $listpage['height'] = $this->napdf_instance->h - ($panelpage['margin'] * 2);
				$panelpage['width'] = $listpage['width'] = $this->column_width;
				
				// These are the actual drawing areas.
				
				// The panel that is on the back of the folded list.
				$backpanel_y_offset = $panelpage['margin'];
				$backpanel_max_y_offset = $backpanel_y_offset + $panelpage['height'];

				$backpanel_1x_offset = $left + $panelpage['margin'];
				$backpanel_max_1x_offset = $backpanel_1x_offset + $panelpage['width'];
				$backpanel_2x_offset = $backpanel_max_1x_offset + ($panelpage['margin'] * 2);
				$backpanel_max_2x_offset = $backpanel_2x_offset + $panelpage['width'];
				$backpanel_3x_offset = $backpanel_max_2x_offset + ($panelpage['margin'] * 2);
				$backpanel_max_3x_offset = $backpanel_3x_offset + $panelpage['width'];
				
				// The panel that is up front of the folded list.
				$frontpanel_x_offset = $backpanel_max_3x_offset + ($panelpage['margin'] * 3);
				$frontpanel_max_x_offset = $frontpanel_x_offset + $panelpage['width'];
				$frontpanel_y_offset = $panelpage['margin'];
				$frontpanel_max_y_offset = $frontpanel_y_offset + $panelpage['height'];
				
				// The front page has half dedicated to a single list panel.
				$frontlist_x_offset = $frontpanel_max_x_offset + $panelpage['margin'];
				$frontlist_max_x_offset = $frontlist_x_offset + $listpage['width'];
				$frontlist_y_offset = $listpage['margin'];
				$frontlist_max_y_offset = $frontlist_y_offset + $listpage['height'];
				
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
				$this->DrawListPage ( 0 );
				$extra_page = false;
				
				if ( !$this->pos['end'] )
				    {
				    $extra_page = true;
				    $this->DrawListPage ( 1 );
				    }
				
				if ( !$this->pos['end'] )
				    {
				    $this->DrawListPage ( 2 );
				    }
				    
                $this->font_size = 10;
                $this->DrawFormats ( $backpanel_3x_offset, $frontpanel_y_offset, $backpanel_max_3x_offset, $frontpanel_max_y_offset, $this->napdf_instance->format_data );
                
				$inPrinter_Date = date ( '\R\e\v\i\s\e\d F, Y' );

				$this->napdf_instance->AddPage ( );
				$this->DrawRearPage1 ( $backpanel_1x_offset, $backpanel_y_offset, $backpanel_max_1x_offset, $backpanel_max_y_offset );
				$this->DrawRearPage2 ( $backpanel_2x_offset, $backpanel_y_offset, $backpanel_max_2x_offset, $backpanel_max_y_offset );
				$this->DrawFrontPanel ( $backpanel_3x_offset, $backpanel_y_offset, $backpanel_max_3x_offset, $backpanel_max_y_offset, $inPrinter_Date );
				}
			$ret = true;
			}
		
		return $ret;
	}

	/********************************************************************
	*/
	function DrawFormats ( $left, $top, $right, $bottom, $formats )
	{
		$y = $top + PDF_MARGIN;

		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->font_size + 2;
		
        $this->napdf_instance->SetFillColor ( $this->blockColor[0], $this->blockColor[1], $this->blockColor[2] );
        $this->napdf_instance->SetTextColor ( $this->blockTextColor[0], $this->blockTextColor[1], $this->blockTextColor[2] );
        
		$this->napdf_instance->SetFont ( $this->font, 'B', ($fontSize - 3) );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_LHV_LIST_FORMAT_KEY );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
        $height = PDF_MARGIN * 1.5;
        
		$this->napdf_instance->Rect ( $left, $top, ($right - $left), $height, "F" );

		$this->napdf_instance->SetXY ( $cellleft, $y - 0.02 );

		$this->napdf_instance->Cell ( 0, 0, _PDF_LHV_LIST_FORMAT_KEY );
		$y += PDF_MARGIN;
		
        $this->napdf_instance->SetFillColor ( 255 );
        $this->napdf_instance->SetTextColor ( 0 );
        
		$count = count ( $formats );
		$w1 = $left + 0.25;
		$fSize = $fontSize - 5;
		
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
			$y = $this->napdf_instance->GetY ( ) + 0.01;
			$this->napdf_instance->SetY ( $y );
			}
		
		$y += 0.05;
		
		$this->napdf_instance->SetY ( $y );
		$heading_height = ($fontSize + 1);
		$height = ($heading_height/72) + 0.03;
		
        $this->napdf_instance->SetFillColor ( $this->blockColor[0], $this->blockColor[1], $this->blockColor[2] );
        $this->napdf_instance->SetTextColor ( $this->blockTextColor[0], $this->blockTextColor[1], $this->blockTextColor[2] );
        $this->napdf_instance->SetFont ( $fontFamily, 'B', $heading_height );
        $this->napdf_instance->Rect ( $left, $y, ($right - $left), $height, "F" );
        $stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_LHV_LIST_HEADER );
        $cellleft = (($right + $left) / 2) - ($stringWidth / 2);
        $y += 0.02;
        $this->napdf_instance->SetXY ( $cellleft, $y );
        $this->napdf_instance->Cell ( 0, $heading_height/72, _PDF_LHV_LIST_HEADER );
        
        $this->napdf_instance->SetFillColor ( 255 );
        $this->napdf_instance->SetTextColor ( 0 );
		$y += 0.4;
		$this->napdf_instance->SetY ( $y );
		
		$this->napdf_instance->SetFont ( $fontFamily, '', $fontSize );

		$this->napdf_instance->SetLineWidth ( 0.01 );
		$this->napdf_instance->Line ( $left + 0.0625, $y, $right, $y );
		
		while ( $y < ($bottom - 0.25) )
			{
			$y += 0.2;
			$this->napdf_instance->Line ( $left + 0.0625, $y, $right, $y );
			}
    }
    
	/********************************************************************
	*/
	function DrawRearPage1 ( $left, $top, $right, $bottom )
	{
		$y = $top + PDF_MARGIN;
		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->font_size - 1.5;
		$fSize = ($fontSize + 1) / 70;
		$this->napdf_instance->SetXY ( $left, $y );
		$this->napdf_instance->SetFont ( $this->font, 'B', $fontSize );
        $this->napdf_instance->Cell ( 0, $heading_height/72, _PDF_LHV_LIST_HIW_HEADER );
        $y += 0.1;
		$this->napdf_instance->SetFont ( $this->font, 'I', $fontSize );
		$this->napdf_instance->SetXY ( $left, $y );
		$this->napdf_instance->MultiCell ( $this->column_width, $fSize, utf8_decode ( _PDF_LHV_LIST_HIW_TEXT1 ), 0, "J" );
		$y = $this->napdf_instance->GetY() + 0.1;
		$this->napdf_instance->SetXY ( $left, $y );
		$this->napdf_instance->MultiCell ( $this->column_width, $fSize, utf8_decode ( _PDF_LHV_LIST_HIW_TEXT2 ), 0, "J" );
		$y = $this->napdf_instance->GetY() + 0.1;
		$this->napdf_instance->SetXY ( $left, $y );
		$this->napdf_instance->MultiCell ( $this->column_width, $fSize, utf8_decode ( _PDF_LHV_LIST_HIW_TEXT3 ), 0, "J" );
	}
    
	/********************************************************************
	*/
	function DrawRearPage2 ( $left, $top, $right, $bottom )
	{
		$y = $top + PDF_MARGIN;
		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->font_size;
		$fSize = ($fontSize + 1) / 70;
		$this->napdf_instance->SetXY ( $left, $y );
		$this->napdf_instance->SetFont ( $this->font, 'B', $fontSize );
        $this->napdf_instance->Cell ( 0, $heading_height/72, _PDF_LHV_LIST_WIAA_HEADER );
        $y += 0.1;
		$this->napdf_instance->SetFont ( $this->font, 'I', $fontSize );
		$this->napdf_instance->SetXY ( $left, $y );
		$this->napdf_instance->MultiCell ( $this->column_width, $fSize, utf8_decode ( _PDF_LHV_LIST_WIAA_TEXT ), 0, "L" );
		$y = $this->napdf_instance->GetY() + 0.1;

        $this->napdf_instance->SetFillColor ( $this->blockColor[0], $this->blockColor[1], $this->blockColor[2] );
        $this->napdf_instance->SetTextColor ( $this->blockTextColor[0], $this->blockTextColor[1], $this->blockTextColor[2] );
        $this->napdf_instance->Rect ( $left, $y, ($right - $left), 0.2, "F" );
		$this->napdf_instance->SetFont ( $this->font, 'B', $fontSize + 1 );
        $y += 0.1;
        
        $this->napdf_instance->SetXY ( $left, $y );
        $this->napdf_instance->Cell ( 0, 0, _PDF_LHV_LIST_SAYING_1 );
        
		$y += 0.2;
        $this->napdf_instance->SetFillColor ( 255 );
        $this->napdf_instance->SetTextColor ( 0 );
		
		$this->napdf_instance->SetFont ( $this->font, 'I', $fontSize );
		$this->napdf_instance->SetXY ( $left + 0.15, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_LHV_LIST_SAYING_2 );
		
		$y += 0.15;
		$this->napdf_instance->SetXY ( $left + 0.15, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_LHV_LIST_SAYING_3 );
		
		$y += 0.15;
		$this->napdf_instance->SetXY ( $left + 0.15, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_LHV_LIST_SAYING_4 );
		
		$y += 0.15;
		$this->napdf_instance->SetXY ( $left + 0.15, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_LHV_LIST_SAYING_5 );
		
		$y += 0.15;
		$this->napdf_instance->SetXY ( $left + 0.15, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_LHV_LIST_SAYING_6 );
		
		$y += 0.15;
		$this->napdf_instance->SetXY ( $left + 0.15, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_LHV_LIST_SAYING_7 );
		
		$this->napdf_instance->SetFont ( "Times", 'I', $fontSize + 5 );
		$y += 0.2;
		$fSize = ($fontSize + 6.5) / 70;
		$this->napdf_instance->SetXY ( $left, $y );
		$this->napdf_instance->MultiCell ( $this->column_width, $fSize, utf8_decode ( _PDF_LHV_LIST_REQUIREMENT ), 0, "L" );
	}
	
	/********************************************************************
	*/
	function DrawFrontPanel ( $left, $top, $right, $bottom, $date )
	{
		$inTitleGraphic = "../ny_printed_lists/images/LHV_Cover_Logo.png";
		
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
		
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_LHV_LIST );
		
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );

		$this->napdf_instance->Cell ( 0, 0, _PDF_LHV_LIST );
		$y += 0.2;

		$this->napdf_instance->SetFont ( $this->font, 'B', ($fontSize + 7) );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_LHV_LIST_BANNER );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );

		$this->napdf_instance->Cell ( 0, 0, _PDF_LHV_LIST_BANNER );
		$y += 0.2;
		
		$this->napdf_instance->SetFont ( $this->font, 'B', $fontSize + 1 );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_LHV_LIST_BANNER_2.' '._PDF_LHV_LIST_BANNER_3 );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_LHV_LIST_BANNER_2.' '._PDF_LHV_LIST_BANNER_3 );
		
		$this->napdf_instance->Image ( $inTitleGraphic, ($left + 0.9), 0.9, 1.5, 1.5, 'PNG' );
		
		$y = 2.5;
		
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_LHV_LIST_HELPLINE );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_LHV_LIST_HELPLINE );
        $y += 0.15;
		
		$fSize = ($fontSize + 1.5) / 70;
		$this->napdf_instance->SetXY ( $left, $y );
		$this->napdf_instance->MultiCell ( $this->column_width, $fSize, utf8_decode ( _PDF_LHV_LIST_SUBCOMMITTEE_MEETING ), 0, "C" );
        $y = $this->napdf_instance->GetY() + 0.05;
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->SetFont ( $this->font, 'I', $fontSize );
		$this->napdf_instance->Cell ( 0, 0, _PDF_LHV_LIST_SUBCOMMITTEE_MEETING2 );
	}
};
?>