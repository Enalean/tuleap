<?php
/*
 * @see http://stackoverflow.com/questions/3063787/handle-json-request-in-php
 */
var_dump(json_decode(file_get_contents('php://input')));
?>