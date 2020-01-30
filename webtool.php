<?php

/*

	PHP application to auto populate PDF form fields with
	data from Excel file

*/

define("PATH_ROOT",	getcwd());
define("PATH_FORMS", PATH_ROOT . "/forms/");
define("DATES_FILE", PATH_FORMS . "dates.txt");
define("FIELDS_FILE", PATH_ROOT . "/fields.txt");
define("XLSX_FILE", PATH_ROOT . "/data.xlsx");
define("FDF_FILE", PATH_ROOT . "/data.fdf");

require_once(PATH_ROOT . "/lib/simplexlsx/src/SimpleXLSX.php");

class excelToPdfForm {

	public $mapped_fields = array();

	// Check if pdftk is installed
	function checkPdftk() {
		return !empty(shell_exec("pdftk --version"));
	}


	// Check modified date of all forms
	function formsForReprocessing() {

		$dates = array();
		$forms_to_reprocess = array();

		// Load last modified dates (if available)
		if (is_file(DATES_FILE)) {
			$handle = fopen(DATES_FILE, "r");			
			if ($handle) {
			    while (($line = fgets($handle)) !== false) {
			    	if (trim($line) != "") {
						$exp = explode(" ", $line);
						$dates[$exp[0]] = trim($exp[1]);
					}
				}
			}
		}

		// Find all the files in the pdf form directory
		$dir = PATH_FORMS;
		$files = array_diff(scandir($dir), array('.', '..'));

		foreach ($files as $file) {
			
			$file_path = $dir . $file;

			// Get modified date only for PDF files
			if (is_file($file_path) && mime_content_type($file_path) == "application/pdf") {
				$mod_date = filemtime($file_path);

				if (!isset($dates[$file]))
				{
					// file is new / unknown, so add for processing
					$forms_to_reprocess[] = $file;

				// if modified date differs
				} elseif (isset($dates[$file]) && $mod_date != $dates[$file]) {
					echo "Date mismatch for $file... dates file says [{$dates[$file]}] but file is [$mod_date]\n";
					$forms_to_reprocess[] = $file;
				}
			}
		}

		if (count($forms_to_reprocess)) {
			return $forms_to_reprocess;
		}

		return false;
	}



	// Reprocess new/amended forms with pdftk
	function reprocessForms($forms) {
		foreach($forms as $form) {

			$file_path = PATH_FORMS . $form;

			if (is_file($file_path) && mime_content_type($file_path) == "application/pdf") {
				shell_exec("pdftk $file_path output $file_path.new && mv $file_path.new $file_path");
			}
		}
	}

	// Save new modified dates
	function updateModifiedDates($forms) {
		
		$str = "";
		foreach($forms as $form) {

			$file_path = PATH_FORMS . $form;

			if (is_file($file_path) && mime_content_type($file_path) == "application/pdf") {
				$mod_date = filemtime($file_path);
				$str .= "$form $mod_date\n";
			}
		}	
				
		if (trim($str)){
			// Write new modified dates to file
			file_put_contents(DATES_FILE, $str);
		}

		echo $str;

	}


	// map fields from PDF form
	function mapFields() {
	
		$handle = fopen(FIELDS_FILE, "r");
		
		$i = 0;

		if ($handle) {

		    while (($line = fgets($handle)) !== false) {
			
		    	if (trim($line) != "---") {
					
					$exp = explode(": ", $line);

					$field['key'] = str_replace("Field", "", $exp[0]);
					$field['value'] = trim($exp[1]);

					// we have two options for this value
					if ($field['key'] == "StateOption") {

						// if we've already created an array for this value, use it
						if(is_array($this->mapped_fields[$i][$field['key']])) {
							$this->mapped_fields[$i][$field['key']][] = $field['value'];
						}
						// otherwise create the array now
						else {
							$this->mapped_fields[$i][$field['key']] = array($field['value']);
						}
					}
					else {
						// store the key value pair
						$this->mapped_fields[$i][$field['key']] = $field['value'];	
					}
					

				} else {

					if (count($this->mapped_fields) > 0) {
						$i++;
					}
				}  
		    }
		    fclose($handle);
		} else {
		    // error opening the file.
		    echo "There was an error opening/reading the fields.txt file.";
		} 
	}


	// Load xlsx data into array
	function loadXlsxData() {
		if ( $xlsx = SimpleXLSX::parse(XLSX_FILE) ) {
			$data = $xlsx->rows();
			$headers = array_shift($data); 	//removing first line of headers
			return $data;
		}
		echo "Could not parse xlsx file:\n" . SimpleXLSX::parseError();
		return false;
	}

	// Build XFDF data from Excel Data Array
	function buildXfdfData($data) {

		$xfdf_head = '<?xml version="1.0" encoding="UTF-8"?><xfdf xmlns="http://ns.adobe.com/xfdf/" xml:space="preserve"><fields>';
		$xml_data = '';
		$xfdf_end = '</fields></xfdf>';


		foreach ($data as $field) {

 			/*
 			[0] => Type
            [1] => Field Name
            [2] => Data
            */

            $field_name = $field[1];
            $field_data = $field[2];

			//echo "Mapping field name [{$field[1]}] to value [{$field[2]}]\n";

			$key = array_search($field_name, array_column($this->mapped_fields, 'Name'));

			// if we have an array of options for the value this is a checkbox
			if (isset($this->mapped_fields[$key]["StateOption"])) {

				if ($field_data != "Off") {

					//echo "-- Field should NOT be off\n";

					$search = array_search("Off", $this->mapped_fields[$key]["StateOption"]);
					$field_data = $search == 0 ? $this->mapped_fields[$key]["StateOption"][1] : $this->mapped_fields[$key]["StateOption"][0];

					//echo "-- Value set to [$field_data]\n";
				}
			}

			$field_data = substr($field_data, 0, 10);

			$xml_data .= '
		        <field name="'.$field_name.'">
		            <value>'.$field_data.'</value>
		        </field>';
		}

		$FDF_content = $xfdf_head.$xml_data.$xfdf_end;
		return $FDF_content;
	}

}


// init the class
$x = new excelToPdfForm();

// check we have the right software installed
if (!$x->checkPdftk()) {

	echo "pdftk is not installed so we cannot proceed.\n";
	die;
}

// check for updates to the PDF forms
if ($forms_to_reprocess = $x->formsForReprocessing()) {
	echo "We must reprocess the following forms:\n";
	print_r($forms_to_reprocess);
	$x->reprocessForms($forms_to_reprocess);
	$x->updateModifiedDates($forms_to_reprocess);
}

// map the fields
$x->mapFields();

// fetch the data
$excel_data = $x->loadXlsxData();

$xfdf = $x->buildXfdfData($excel_data);

echo "Writing XFDF data...\n";

$FDF_file = fopen(FDF_FILE, 'w');
fwrite($FDF_file, $xfdf);
fclose($FDF_file);

echo "Merge PDF to new doc...\n";

shell_exec("pdftk " . PATH_FORMS . "f433a.pdf fill_form " . PATH_ROOT . "/data.fdf output " . PATH_ROOT . "/pdfs/f433a_filled_" . date("Y-m-d-His") . ".pdf");


?>
