<?php

require_once("./map.php");
require_once("./vals.php");


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
