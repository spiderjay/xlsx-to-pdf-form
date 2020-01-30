<?php

/*

	PHP application to auto populate PDF form fields with
	data from Excel file

*/

define("PATH_ROOT",	getcwd());
define("PATH_FORMS", PATH_ROOT . "/forms/");


class excelToPdfForm {

	// Check modified date of all forms
	function formsForReprocessing() {

		$dates = array();
		$dates_file = PATH_FORMS . "dates.txt";
		$forms_to_reprocess = array();


		// Load last modified dates (if available)
		if (is_file($dates_file)) {
			
			$handle = fopen($dates_file, "r");
			
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

		$str = "";

		foreach ($files as $file) {
			
			$file_path = $dir . $file;

			// Get modified date only for PDF files
			if (is_file($file_path) && mime_content_type($file_path) == "application/pdf") {
				$mod_date = filemtime($file_path);
				$str .= "$file $mod_date\n";

				if (isset($dates[$file]) && $mod_date != $dates[$file]) {
					echo "Date mismatch for $file... dates file says [{$dates[$file]}] but file is [$mod_date]\n";
					$forms_to_reprocess[] = $file;
				}
			}
		}

		// Write new modified dates to file
		//file_put_contents($dates_file, $str);

		if (count($forms_to_reprocess)) {
			return $forms_to_reprocess;
		}
		
		return false;

	}

}

$x = new excelToPdfForm();

if ($forms_to_reprocess = $x->formsForReprocessing()) {
	echo "We must reprocess the following forms:\n";
	print_r($forms_to_reprocess);
}

die;



require_once("./map.php");
require_once("./vals.php");


// Verify that the original form(s) have not been altered





$xfdf_head = '<?xml version="1.0" encoding="UTF-8"?><xfdf xmlns="http://ns.adobe.com/xfdf/" xml:space="preserve"><fields>';
$xml_data = '';
$xfdf_end = '</fields></xfdf>';

/* Generate all fields with field_key form webform and value form submission */

/*

$fields = array(
	"topmostSubform[0].Page1[0].p1-t1[0]" => "My name goes here"
);

foreach ($fields as $key => $value) {

    $xml_data .= '
        <field name="'.$key.'">
            <value>'.$value.'</value>
        </field>';
}
*/

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
