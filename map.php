<?php

$mapped_fields = array();

$handle = fopen("fields.txt", "r");
$i = 0;

if ($handle) {
    while (($line = fgets($handle)) !== false) {
	
    	if (trim($line) != "---") {
			
			$exp = explode(": ", $line);

			$field['key'] = str_replace("Field", "", $exp[0]);
			$field['value'] = trim($exp[1]);

			//echo($field['value']);

			//echo $line;
			//echo "Key [{$field['key']}] Value [{$field['value']}]\n";

			// we have two options for this value
			if ($field['key'] == "StateOption") {

				// if we've already created an array for this value, use it
				if(is_array($mapped_fields[$i][$field['key']])) {
					$mapped_fields[$i][$field['key']][] = $field['value'];
				}
				// otherwise create the array now
				else {
					$mapped_fields[$i][$field['key']] = array($field['value']);
				}
				
			}
			else {
				// store the key value pair
				$mapped_fields[$i][$field['key']] = $field['value'];	
			}
			



		} else {

			if (count($mapped_fields) > 0) {
				$i++;
			}

			/*if(count($mapped_fields) > 15) {
				print_r($mapped_fields); die;
			}*/
		}  


    }

    fclose($handle);
} else {
    // error opening the file.
    echo "There was an error opening/reading the file.";
} 


?>