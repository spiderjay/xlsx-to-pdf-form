<?php

$xfdf_head = '<?xml version="1.0" encoding="UTF-8"?><xfdf xmlns="http://ns.adobe.com/xfdf/" xml:space="preserve"><fields>';
$xml_data = '';
$xfdf_end = '</fields></xfdf>';

/* Generate all fields with field_key form webform and value form submission */

$fields = array(
	"topmostSubform[0].Page1[0].p1-t1[0]" => "My name goes here"
);

foreach ($fields as $key => $value) {

    $xml_data .= '
        <field name="'.$key.'">
            <value>'.$value.'</value>
        </field>';
}


$FDF_content = $xfdf_head.$xml_data.$xfdf_end;

$FDF_file = fopen('new_fdf.fdf', 'w');
fwrite($FDF_file, $FDF_content);
fclose($FDF_file);

?>
