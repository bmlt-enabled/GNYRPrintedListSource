<?php
/**
	\file sas_napdf.class.php
	
	\brief This file creates and dumps a Long Island meeting list in PDF form.
*/
ini_set('display_errors', 1);
ini_set('error_reporting', E_ERROR);
// Get the napdf class, which is used to fetch the data and construct the file.
require_once ( dirname ( __FILE__ ).'/usletter_napdf.class.php' );

/**
	\brief	This creates and manages an instance of the napdf class, and creates
	the PDF file.
*/
class oaasc_napdf extends usletter_napdf
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
		$this->sort_keys = array (	'weekday_tinyint' => true,			///< First, sort by weekday
		                            'start_time' => true,               ///< Next, the meeting start time
									'location_municipality' => true,	///< Next, the town.
									'week_starts' => 2					///< Our week starts on Monday (2)
									);
		
		/// These are the parameters that we send over to the root server, in order to get our meetings.
		$this->out_http_vars = array ('do_search' => 'yes',						///< Do a search
									'bmlt_search_type' => 'advanced',			///< We'll be very specific in our request
									'advanced_service_bodies' => array (		///< We will be asking for meetings in specific Service Bodies.
																		1011	///< OAASC
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
	*/
	function DrawFormats ( $left, $top, $right, $bottom, $formats )
	{
		$y = $top + PDF_MARGIN;

		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->font_size + 2;
		
		$this->napdf_instance->SetFont ( $this->font, 'B', ($fontSize - 3) );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_OAAS_LIST_FORMAT_KEY );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );

		$this->napdf_instance->Cell ( 0, 0, _PDF_OAAS_LIST_FORMAT_KEY );
		$y += PDF_MARGIN;
		
		$count = count ( $formats );
		$w1 = $left + 0.25;
		$fSize = $fontSize - 4;
		
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
    }
    
	/********************************************************************
	*/
	function DrawRearPanel ( $left, $top, $right, $bottom, $formats )
	{
        $this->DrawFormats ( $left, $top, $right, $bottom, $formats );
        		
		$y = $this->napdf_instance->GetY ( ) + 0.05;
		
		$fontFamily = $this->napdf_instance->FontFamily;
		$fontSize = $this->font_size + 4;
		
		$this->napdf_instance->SetLineWidth ( 0.01 );
		$this->napdf_instance->Line ( $left + 0.0625, $y, $right, $y );
		
		$y += 0.1;
	}
	
	/********************************************************************
	*/
	function DrawSubcommittees ( )
		{
		return;
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
			if ( @$na_dom->loadHTML($this->call_curl ( "http://sasna.org/?page_id=421" )) )
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

		$this->napdf_instance->SetFont ( $fontFamily, 'B', ($fontSize - 1.75) );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_OAASNA_LIST_SUBCOMMITTEES );
		
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
        $this->napdf_instance->SetFillColor ( 0 );
        $this->napdf_instance->SetTextColor ( 255 );

		$this->napdf_instance->Rect ( $left, $y, ($right - $left), 0.16, "F" );

		$y += 0.08;

		$this->napdf_instance->SetXY ( $left, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_OAASNA_LIST_SUBCOMMITTEES );
		
		$y += 0.08;
        $this->napdf_instance->SetDrawColor ( 255 );
		$this->napdf_instance->Line ( $left + 0.03, $y, $right - 0.03, $y );
        
		if ( is_array ( $s_array ) && count ( $s_array ) )
			{
			$heading_height = $fontSize + 1;
			$height = ($heading_height / 72) * 1.07;
			
			for ( $c = 0; $c < count ( $s_array ); $c++ )
				{
				$this->napdf_instance->SetFillColor ( 0 );
				$this->napdf_instance->SetTextColor ( 255 );
				$this->napdf_instance->SetFont ( $fontFamily, 'B', $heading_height );
				$this->napdf_instance->Rect ( $left, $y, ($right - $left), $height, "F" );
				$stringWidth = $this->napdf_instance->GetStringWidth ( $s_array[$c]['_name'] );
				$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
				$this->napdf_instance->SetXY ( $cellleft, $y + 0.005 );
				$this->napdf_instance->Cell ( 0, $height, $s_array[$c]['_name'] );
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
	function DrawFrontPanel ( $left, $top, $right, $bottom, $date )
	{
		$inTitleGraphic = "../ny_printed_lists/images/OAASC_Cover_Logo.png";
		
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
		
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_OAAS_LIST );
		
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );

		$this->napdf_instance->Cell ( 0, 0, _PDF_OAAS_LIST );
		$y += 0.2;

		$this->napdf_instance->SetFont ( $this->font, 'B', ($fontSize + 7) );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_OAAS_LIST_BANNER );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );

		$this->napdf_instance->Cell ( 0, 0, _PDF_OAAS_LIST_BANNER );
		$y += 0.2;
		
		$this->napdf_instance->SetFont ( $this->font, 'B', $fontSize + 1 );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_OAAS_LIST_BANNER_2.' '._PDF_OAAS_LIST_BANNER_3 );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_OAAS_LIST_BANNER_2.' '._PDF_OAAS_LIST_BANNER_3 );
		
		$this->napdf_instance->Image ( $inTitleGraphic, ($left + 0.9), 0.9, 1.5, 1.5, 'PNG' );

		$this->napdf_instance->SetFont ( $fontFamily, 'B', ($fontSize + 4.75) );
		
		$y = 2.5;

		$url_string = _PDF_OAAS_LIST_URL;
		$stringWidth = $this->napdf_instance->GetStringWidth ( $url_string );
		$cellleft = $left;
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, $url_string );
		$y += 0.2;
		
		$this->napdf_instance->SetFont ( $fontFamily, 'B', ($fontSize + 4) );
		
		$this->napdf_instance->SetXY ( $left, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_OAAS_LIST_HELPLINES );
		$y += 0.2;

		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_OAAS_LIST_HELPLINE_OAASC );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_OAAS_LIST_HELPLINE_OAASC );
		$y += 0.16;
		
		$this->napdf_instance->SetFont ( $fontFamily, '', ($fontSize + 3) );
		
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_OAAS_LIST_HELPLINE_REGION );
		$cellleft = (($right + $left) / 2) - ($stringWidth / 2);
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_OAAS_LIST_HELPLINE_REGION );
		$y += 0.25;

		$cellleft = $left;
		$this->napdf_instance->SetXY ( $cellleft, $y );
		$st_width = $this->napdf_instance->GetStringWidth ( _PDF_OAAS_LIST_NAME );
		$this->napdf_instance->Cell ( 0, 0, _PDF_OAAS_LIST_NAME );
		$stringWidth = $this->napdf_instance->GetStringWidth ( _PDF_OAAS_LIST_PHONE );
		$this->napdf_instance->SetXY ( $right - $stringWidth, $y );
		$this->napdf_instance->Cell ( 0, 0, _PDF_OAAS_LIST_PHONE );
		$y += PDF_MARGIN;
		
		$this->napdf_instance->SetFont ( $fontFamily, '', $fontSize );

		$this->napdf_instance->SetLineWidth ( 0.02 );
		$this->napdf_instance->Line ( $left + 0.0625, $y, $right, $y );
		
		while ( $y < ($bottom - 0.25) )
			{
			$y += 0.3;
			$this->napdf_instance->Line ( $left + 0.0625, $y, $right, $y );
			}
	}
};
?>