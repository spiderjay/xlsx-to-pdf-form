<?php

require_once './fpdm/vendor/autoload.php';

echo "Loaded and ready to rock...\n\n";

$fields = array(
    "topmostSubform[0].Page1[0].p1-t1[0]"    => 'Linklite Systems'
);

$pdf = new FPDM('test.pdf');
$pdf->Load($fields, false); // second parameter: false if field values are in ISO-8859-1, true if UTF-8
$pdf->Merge();
$pdf->Output("F", "output.pdf");

/*
FieldType: Text
FieldName: topmostSubform[0].Page1[0].p1-t1[0]
FieldNameAlt: Page 1. Name on Internal Revenue Service (I R S) Account.
FieldFlags: 8388608
FieldJustification: Left
*/

?>
