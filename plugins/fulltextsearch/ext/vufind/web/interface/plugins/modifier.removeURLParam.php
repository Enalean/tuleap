<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     removeURLParam
 * Purpose:  Remove a parameter from a URL with GET parameters.
 * -------------------------------------------------------------
 */
function smarty_modifier_removeURLParam($url, $param_to_remove) {
    // Break the base URL from the parameters:
    list($base, $params) = explode('?', $url);
    
    // Loop through the parameters and filter out the unwanted one:
    $params = explode('&', $params);
    $filtered_params = array();
    foreach ($params as $current_param) {
        list($name, $value) = explode('=', $current_param);
        if ($name != $param_to_remove) {
            $filtered_params[] = $current_param;
        }
    }

    // Reassemble the URL minus the unwanted parameter:
    return $base . '?' . implode('&', $filtered_params);
}
?>