<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     addURLParams
 * Purpose:  Add parameters to a URL with GET parameters.
 * -------------------------------------------------------------
 */
function smarty_modifier_addURLParams($url, $params_to_add) {
    // Break the base URL from the parameters:
    list($base, $params) = explode('?', $url);
    
    // Loop through the parameters and filter out the unwanted one:
    $parts = explode('&', $params);
    $params = array();
    foreach($parts as $param) {
        if (!empty($param)) {
            $params[] = $param;
        }
    }
    $extra_params = explode('&', $params_to_add);
    foreach ($extra_params as $current_param) {
        if (!empty($current_param)) {
            $params[] = $current_param;
        }
    }

    // Reassemble the URL with the added parameter(s):
    return $base . '?' . implode('&', $params);
}
?>