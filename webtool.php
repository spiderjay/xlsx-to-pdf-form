<?php

/*

	PHP application to auto populate PDF form fields with
	data from Excel file

*/

define("PATH_ROOT",	getcwd());
define("PATH_FORMS", PATH_ROOT . "/forms/");
define("DATES_FILE", PATH_FORMS . "dates.txt");
define("XLSX_FILE", PATH_ROOT . "/data.xlsx");


class excelToPdfForm {

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

	// Load xlsx data into array
	function loadXlsxData() {
		$row = 1;
		if (($handle = fopen(XLSX_FILE, "r")) !== FALSE) {
		    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
		        $num = count($data);
		        echo "<p> $num fields in line $row: <br /></p>\n";
		        $row++;
		        for ($c=0; $c < $num; $c++) {
		            echo $data[$c] . "<br />\n";
		        }
		    }
		    fclose($handle);
		    print_r($data);
		    die;
		}
		else {
			echo "Could not open XLSX file for reading.";
		}
	}

}

$x = new excelToPdfForm();

$x->loadXlsxData();

if (!$x->checkPdftk()) {

	echo "pdftk is not installed so we cannot proceed.\n";
	die;
}


if ($forms_to_reprocess = $x->formsForReprocessing()) {
	echo "We must reprocess the following forms:\n";
	print_r($forms_to_reprocess);
	$x->reprocessForms($forms_to_reprocess);
	$x->updateModifiedDates($forms_to_reprocess);
}

die;



require_once("./map.php");
require_once("./vals.php");





$xfdf_head = '<?xml version="1.0" encoding="UTF-8"?><xfdf xmlns="http://ns.adobe.com/xfdf/" xml:space="preserve"><fields>';
$xml_data = '';
$xfdf_end = '</fields></xfdf>';


foreach ($values as $key => $val) {

	echo "Mapping field name [". $mapped_fields[$key]['Name'] ." to value [$val]\n";

	// if we have an array of options for the value this is a checkbox
	if (isset($mapped_fields[$key]["StateOption"])) {
		//print_r($mapped_fields[$key]);

		//echo "Field has state options\n";

		if ($val != "Off") {

			//echo "-- Field should NOT be off\n";

			$search = array_search("Off", $mapped_fields[$key]["StateOption"]);
			$val = $search == 0 ? $mapped_fields[$key]["StateOption"][1] : $mapped_fields[$key]["StateOption"][0];

			//echo "-- Value set to [$val]\n";
		}
	}

	$state = "";
	if (isset($mapped_fields[$key]["StateOption"])){
		$state = '<stateoption>'.$val.'</stateoption>';
	}

    $xml_data .= '
        <field name="'.$mapped_fields[$key]['Name'].'">
            <value>'.$val.'</value>
        </field>';
}



$FDF_content = $xfdf_head.$xml_data.$xfdf_end;

$FDF_file = fopen('new_fdf.fdf', 'w');
fwrite($FDF_file, $FDF_content);
fclose($FDF_file);

?>
