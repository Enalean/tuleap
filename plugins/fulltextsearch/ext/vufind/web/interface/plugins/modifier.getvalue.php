<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     getvalue
 * Purpose:  Prints the subfield data of a MARC_Field object
 * -------------------------------------------------------------
 */
function smarty_modifier_getvalue($marcField, $subfield) {
    if ($marcField) {
        $subfield = $marcField->getSubfield($subfield);
        if ($subfield) {
            return $subfield->getData();
        } else {
            return null;
        }
    } else {
        return null;
    }
}
?>