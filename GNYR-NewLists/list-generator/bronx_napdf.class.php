<?php
/**
	\file sas_napdf.class.php
	
	\brief This file creates and dumps a Rockland meeting list in PDF form.
*/
// Get the napdf class, which is used to fetch the data and construct the file.
require_once (dirname (__FILE__).'/flex_napdf.class.php');

define ("_BRONX_LIST_HELPLINE", "Regional Helpline: (212) 929-NANA (6262)");
define ("_BRONX_LIST_SUBCOMMITTEE_HEADER", "COMMITTEE MEETINGS");
define ("_BRONX_LIST_ROOT_URI", "https://bmlt.newyorkna.org/main_server/");
define ("_BRONX_LIST_CREDITS", "Meeting List Printed by the Bronx Area");
define ("_BRONX_LIST_URL", "Web Site: newyorkna.org");
define ("_BRONX_LIST_FOOTER", "NA meetings are 90 minutes (an hour and a half) long, unless otherwise noted (in parentheses).");
define ("_BRONX_LIST_BANNER_1", "NA Meetings");
define ("_BRONX_LIST_BANNER_2", "In");
define ("_BRONX_LIST_BANNER_3", "The Bronx");
define ("_BRONX_DATE_FORMAT", '\R\e\v\i\s\e\d F, Y');
define ("_BRONX_FILENAME_FORMAT", 'Printable_PDF_NA_Meeting_List_%s.pdf');
define ("_BRONX_IMAGE_POSIX_PATH", isset($in_http_vars['color']) ? 'images/BlueNALogo.png' : 'images/NALogo.png');
define ("_BRONX_WEEK_STARTS", 2);
define ("_BRONX_VARIABLE_FONT", 10);

/**
	\brief	This creates and manages an instance of the napdf class, and creates
	the PDF file.
*/
class bronx_napdf extends flex_napdf {
	/********************************************************************
		\brief	The constructor for this class does a lot. It creates the instance of the napdf class, gets the data from the
		server, then sorts it. When the constructor is done, the data is ready to be assembled into a PDF.
		
		If the napdf object does not successfully get data from the server, then it is set to null.
	*/
	function __construct (  $in_http_vars	///< The HTTP parameters we'd like to send to the server.
					        ) {
		$this->helpline_string = _BRONX_LIST_HELPLINE;       ///< This is the default string we use for the Helpline.
		$this->credits_string = _BRONX_LIST_CREDITS;         ///< The credits for creation of the list.
		$this->web_uri_string = _BRONX_LIST_URL;             ///< The Web site URI.
		$this->banner_1_string = _BRONX_LIST_BANNER_1;       ///< The First Banner String.
		$this->banner_2_string = _BRONX_LIST_BANNER_2;       ///< The Second Banner String.
		$this->banner_3_string = _BRONX_LIST_BANNER_3;       ///< The Third Banner String.
		$this->week_starts_1_based_int = _BRONX_WEEK_STARTS; ///< The Day of the week (1-based integer, with 1 as Sunday) that our week starts.
		$this->variable_font_size = _BRONX_VARIABLE_FONT;    ///< The variable font starting point.
		$this->image_path_string = _BRONX_IMAGE_POSIX_PATH;  ///< The POSIX path to the image, relative to this file.
		$this->filename = sprintf(_BRONX_FILENAME_FORMAT, date ("Y_m_d"));  ///< The output name for the file.
		$this->root_uri = _BRONX_LIST_ROOT_URI;                  ///< This is the default Root Server URL.
		$this->date_header_format_string = _BRONX_DATE_FORMAT;   ///< This is the default string we use for the date attribution line at the top.

		$this->font = 'Helvetica';	    ///< The font we'll use.
        
		$this->sort_keys = array (	'weekday_tinyint' => true,			///< First, sort by weekday
		                            'start_time' => true,               ///< Next, the meeting start time
									'location_municipality' => true,	///< Next, the town.
									'week_starts' => $this->week_starts_1_based_int ///< Our week starts on this day
									);
		
		/// These are the parameters that we send over to the root server, in order to get our meetings.
		$this->out_http_vars = array (  'meeting_key' => 'location_city_subsection',    ///< Bronx
										'meeting_key_value' => 'Bronx',
										'sort_key' => 'time'        
									);
		
        $in_http_vars['layout'] = 'two-fold-us-legal';
        $in_http_vars['columns'] = 4;
        
		parent::__construct ($in_http_vars);
		
		$black = 0;
		$blue = [0, 0, 153];
		$white = 255;
		
		$this->weekday_header_fill_color = isset($in_http_vars['color']) ? $blue : $black;
		$this->format_header_fill_color = isset($in_http_vars['color']) ? $blue : $black;
		$this->subcommittee_header_fill_color = isset($in_http_vars['color']) ? $blue : $black;
		
		$this->weekday_header_text_color = $white;
		$this->format_header_text_color = $white;
		$this->subcommittee_header_text_color = $white;
	}
	
	/********************************************************************
	*/
	function DrawOneMeeting (  $left,
	                            $top,
	                            $column_width,
	                            $meeting
	                        ) {
		$fontFamily = $this->napdf_instance->getFontFamily();
		$fontSize = $this->font_size - 1.5;

		$fSize = $fontSize / 70;
		$fSizeSmall = ($fontSize - 1) / 70;
	    
        $this->napdf_instance->SetFillColor (255);
        $this->napdf_instance->SetTextColor (0);
        
        $this->napdf_instance->SetFont ($fontFamily, 'B', $fontSize);
        
        $this->napdf_instance->SetY ($top);
    
        $display_string = '';
    
        if (isset ($meeting['start_time'])) {
            $display_string = self::translate_time ($meeting['start_time']);
        }
        
        if (isset ($meeting['duration_time']) && $meeting['duration_time'] && ('01:30:00' != $meeting['duration_time'])) {
            $display_string .= " (".self::translate_duration ($meeting['duration_time']).")";
        }
    
        $this->napdf_instance->SetX ($left);
    
        $this->napdf_instance->MultiCell ($column_width, $fSize, utf8_decode ($display_string));
        
        $display_string = isset ($meeting['meeting_name']) ? $meeting['meeting_name'] : '';
    
        if (isset ($meeting['formats'])) {
            $display_string .= " (".$this->RearrangeFormats ($meeting['formats']).")";
        }

        $this->napdf_instance->SetX ($left);
        
        $this->napdf_instance->MultiCell ($column_width, $fSize, utf8_decode ($display_string), 0, 'L');
    
        $this->napdf_instance->SetFont ($fontFamily, '', $fontSize);
    
        if (isset ($meeting['location_neighborhood']) && $meeting['location_neighborhood']) {
            $display_string = $meeting['location_neighborhood'];
            $this->napdf_instance->SetX ($left);
            $this->napdf_instance->MultiCell ($column_width, $fSize, utf8_decode ($display_string), 0, 'L');
        }
    
        $display_string = '';
    
        if (isset ($meeting['location_text']) && $meeting['location_text']) {
            $display_string .= $meeting['location_text'];
        }
    
        if (isset ($meeting['location_info']) && $meeting['location_info']) {
            if ($display_string) {
                $display_string .= ', ';
            }

            $display_string .= " (".$meeting['location_info'].")";
        }
    
        if ($display_string) {
            $display_string .= ', ';
        }
    
        $display_string .= isset ($meeting['location_info']) ? $meeting['location_street'] : '';
    
        $this->napdf_instance->SetX ($left);
        $this->napdf_instance->MultiCell ($column_width, $fSize, utf8_decode ($display_string), 0, 'L');
    
        if (isset ($meeting['description_string']) && $meeting['description_string']) {
            if ($desc) {
                $desc .= ", ";
            }
            
            $desc = $meeting['description_string'];
        }
        
        $desc = '';
        
        if (isset ($meeting['comments']) && $meeting['comments']) {
            if ($desc) {
                $desc .= ", ";
            }
            
            $desc .= $meeting['comments'];
        }
        
        $desc = preg_replace ("/[\n|\r]/", ", ", $desc);
        $desc = preg_replace ("/,\s*,/", ",", $desc);
        $desc = stripslashes (stripslashes ($desc));

        if ($desc) {
            $extra = ($fSizeSmall * 3);
            $this->napdf_instance->SetFont ($fontFamily, 'I', $fontSize - 1);
            $this->napdf_instance->SetX ($left);
            $this->napdf_instance->MultiCell ($column_width, $fSizeSmall, utf8_decode ($desc));
        }
        
        return $this->napdf_instance->GetY();
	}
	
	/********************************************************************
	*/
	function DrawFrontPanel (   $fixed_font_size,
	                            $left,
	                            $top,
	                            $right,
	                            $bottom
	                        ) {
	    parent::DrawFrontPanel($fixed_font_size, $left - ($this->page_margins / 2), $top, $right, $bottom);		
        $this->font_size = $fixed_font_size;
        $this->napdf_instance->SetFont ($this->font, 'B', $this->font_size + 1);
		$this->DrawPhoneList($left - ($this->page_margins / 2), $this->napdf_instance->GetY(), $right, $bottom - ($this->page_margins / 2));
	}
	
	/********************************************************************
	*/
	function DrawRearPanel (    $fixed_font_size,
	                            $left,
	                            $top,
	                            $right,
	                            $bottom
	                        ) {
	    $this->DrawSubcommittees($left, $this->page_margins, $right, $bottom);
        $y = $this->napdf_instance->GetY();
        $this->font_size = $fixed_font_size;
        $this->napdf_instance->SetFont ($this->font, 'B', $this->font_size + 1);
		$this->DrawPhoneList($left, $y, $right, $bottom + ($this->page_margins / 2));
	}
	
	/********************************************************************
	*/
	function DrawSubcommittees (    $left,
	                                $top,
	                                $right,
	                                $bottom
	                            ) {
        $y = $top;
    
        if ( $y > $this->page_margins ) {
            $y += $this->page_margins;
        }
    
        $column_width = $right - $left;
    
		$fontFamily = $this->napdf_instance->getFontFamily();
		$fontSize = $this->font_size;

        $s_array = array();
        $na_dom = new DOMDocument;
        if ( $na_dom ) {
            if ( @$na_dom->loadHTML($this->napdf_instance->call_curl ( "https://newyorkna.org/PrintedList/bronx.html" )) ) {
                $div_contents = $na_dom->getElementByID ( "meeting_times" );
            
                if ( $div_contents ) {
                    $p_list = $div_contents->getElementsByTagName ( "p" );
                    if ( $p_list && $p_list->length ) {
                        for ( $i = 0; $i < $p_list->length; $i++ ) {
                            $the_item = $p_list->item($i);
                            if ( $the_item ) {
                                $a = null;
                            
                                if ( "first" == $the_item->getAttribute ( "class" ) ) {
                                    $p_list2 = $the_item->getElementsByTagName ( 'b' );
                                
                                    if ( !$p_list2 || !$p_list2->item(0) ) {
                                        $p_list2 = $the_item->getElementsByTagName ( 'strong' );
                                    }
                                    
                                    if ( $p_list2 && $p_list2->item(0) && $p_list2->item(0)->nodeValue ) {
                                        $a['_name'] = $p_list2->item(0)->nodeValue;
                                        $a['_description'] = '';
                                    
                                        while ( $p_list->item($i + 1) && ("first" != $p_list->item($i + 1)->getAttribute ( "class" )) ) {
                                            if ( $a['_description'] ) {
                                                $a['_description'] .= "\n";
                                            }
                                            $a['_description'] .= $p_list->item(++$i)->nodeValue;
                                        }
                                    }
                                }
                            
                                if ( $a ) {
                                    array_push ( $s_array, $a );
                                }
                            }
                        }
                    }
                }
            }
        }

        $fill_color = isset($this->subcommittee_header_fill_color) ? $this->subcommittee_header_fill_color : 0;
        $text_color = isset($this->subcommittee_header_text_color) ? $this->subcommittee_header_text_color : 255;

        if ( is_array($fill_color) && (3 == count($fill_color)) ) {
            $this->napdf_instance->SetFillColor ($fill_color[0], $fill_color[1], $fill_color[2]);
        } else {
            $this->napdf_instance->SetFillColor ($fill_color);
        }

        if ( is_array($text_color) && (3 == count($text_color)) ) {
            $this->napdf_instance->SetTextColor ($text_color[0], $text_color[1], $text_color[2]);
        } else {
            $this->napdf_instance->SetTextColor ($text_color);
        }
        $headerString = _BRONX_LIST_SUBCOMMITTEE_HEADER;
        
		$this->napdf_instance->Rect ($left, $y, ($right - $left), 0.18, "F");

		$y += 0.08;

		$this->napdf_instance->SetFont ($fontFamily, 'B', 9);
		$stringWidth = $this->napdf_instance->GetStringWidth ($headerString);
		
		$cellleft = (($right + $left) - $stringWidth) / 2;
		$this->napdf_instance->SetXY ($cellleft, $y + 0.0125);
		$this->napdf_instance->Cell (0, 0, $headerString);

		$y += 0.125;

        if ( is_array ( $s_array ) && count ( $s_array ) ) {
            $heading_height = $fontSize + 1;
            $height = ($heading_height / 72) * 1.07;
        
            for ( $c = 0; $c < count ( $s_array ); $c++ ) {
                $this->napdf_instance->SetFillColor ( 255 );
                $this->napdf_instance->SetTextColor ( 0 );

                $this->napdf_instance->SetFont ( $fontFamily, 'B', $heading_height );
                $stringWidth = $this->napdf_instance->GetStringWidth ( $s_array[$c]['_name'] );
                $cellleft = $left;
                $this->napdf_instance->SetXY ( $cellleft, $y + 0.005 );
                $this->napdf_instance->Cell ( 0, $height, $s_array[$c]['_name'] );
                $y += $height + .02;

                $this->napdf_instance->SetFillColor ( 255 );
                $this->napdf_instance->SetTextColor ( 0 );
                $this->napdf_instance->SetFont ( $fontFamily, '', ($fontSize) );
                $this->napdf_instance->SetLeftMargin ( $left );
                $this->napdf_instance->SetXY ( $left, $y );
                $this->napdf_instance->MultiCell ( ($right - $left), ($fontSize) / 72, $s_array[$c]['_description'], 0, "L" );
                $y = $this->napdf_instance->GetY ( ) + 0.1;
            }
        }
    }
};
?>