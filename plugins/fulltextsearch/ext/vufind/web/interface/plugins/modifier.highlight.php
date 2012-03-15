<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     highlight
 * Purpose:  Adds a span tag with class "highlight" around a
 *           specific phrase for highlighting
 * -------------------------------------------------------------
 */
function smarty_modifier_highlight($haystack, $needle) {
    // Normalize value to an array so we can loop through it; this saves us from
    // writing the highlighting code twice, once for arrays, once for non-arrays.
    if (!is_array($needle)) {
        $needle = array($needle);
    }

    // Highlight search terms one phrase at a time; we just put in placeholders
    // for the start and end span tags at this point so we can do proper URL
    // encoding later.   
    foreach ($needle as $phrase) {
        $phrase = trim(str_replace(array('"', '*', '?'), '', $phrase));
        if ($phrase != '') {
            $phrase = preg_quote($phrase, '/');
            $haystack = preg_replace("/($phrase)/i", 
                '{{{{START_HILITE}}}}$1{{{{END_HILITE}}}}', $haystack);
        }
    }
    
    // URL encode the string, then put in the highlight spans:
    $haystack = str_replace(array('{{{{START_HILITE}}}}', '{{{{END_HILITE}}}}'), 
        array('<span class="highlight">', '</span>'), 
        htmlspecialchars($haystack));
    
    return $haystack;
}
?>