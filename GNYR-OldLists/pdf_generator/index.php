<?php
	/**
		\file satellite_server/pdf_generator/index.php
	*/
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ERROR);
	
	Make_List ( );
	
	/**
		\brief This function actually gets the CSV data from the root server, and creates a PDF file from it, using FPDF.
	*/
	function Make_List ( )
		{
		$in_http_vars = array_merge_recursive ( $_GET, $_POST );
		
		if ( isset ( $in_http_vars['dir'] ) )
			{
			$dir = preg_replace ( "#[\\:/]#",'',$in_http_vars['dir']).'/';	// Just to make sure no one is trying to pull a fast one...	
			}
		else
			{
			$dir = 'pdf_generator/';
			}
		
		$class_name = preg_replace ( "#[\\:/]#",'',$in_http_vars['list_type']).'_napdf';

		if ( file_exists ( dirname ( __FILE__ )."/../$dir$class_name.class.php" ) )
			{
			require_once ( dirname ( __FILE__ )."/../$dir$class_name.class.php" );
			$class_instance = new $class_name ( $in_http_vars );

			if ( ($class_instance instanceof $class_name) && method_exists ( $class_instance, 'AssemblePDF' ) && method_exists ( $class_instance, 'OutputPDF' ) )
				{
				if ( $class_instance->AssemblePDF() )
					{
					$class_instance->OutputPDF();
					}
				}
			else
				{
				echo "Cannot instantiate $class_name";
				}
			}
		else
			{
			echo "Cannot find ".dirname ( __FILE__ )."/../$dir$class_name.class.php";
			}
		}
?>
