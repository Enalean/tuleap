<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     printms
 * Purpose:  Prints a human readable format from a number of milliseconds
 * -------------------------------------------------------------
 */
function smarty_modifier_printms($ms) {
    $seconds = floor($ms/1000);
    $ms = ($ms % 1000);

    $minutes = floor($seconds/60);
    $seconds = ($seconds % 60);

    $hours = floor($minutes/60);
    $minutes = ($minutes % 60);

    if ($hours) {
        $days = floor($hours/60);
        $hours = ($hours % 60);
        
        if ($days) {
            $years = floor($days/365);
            $days = ($days % 365);
            
            if ($years) {
                return sprintf("%dyears %ddays %dhours %dminutes %dseconds",
                               $years, $days, $hours, $minutes, $seconds);
            } else {
                return sprintf("%ddays %dhours %dminutes %dseconds",
                               $days, $hours, $minutes, $seconds);
            }
        } else {
            return sprintf("%dhours %dminutes %dseconds",
                           $hours, $minutes, $seconds);
        }
    } else {
        return sprintf("%dminutes %dseconds",
                       $minutes, $seconds);
    }
}
?>