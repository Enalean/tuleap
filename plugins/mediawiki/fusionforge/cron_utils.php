<?php

$GLOBALS['verbose'] = true;

function cron_debug($string) {
        global $verbose;
        if($verbose) {
                echo $string."\n";
        }
}

function cron_entry($job,$output) {
//        $sql='INSERT INTO cron_history (rundate,job,output)
//                values ($1, $2, $3)' ;
//        return db_query_params ($sql,
//                                array (time(), $job, $output));
}

?>
