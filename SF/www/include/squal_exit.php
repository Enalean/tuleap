<?php

function exit_error($title,$text) {
	print 'ERROR - '.$text;
	exit;
}

function exit_permission_denied() {
	exit_error('','PERMISSION DENIED');
}

function exit_not_logged_in() {
	exit_error('','NOT LOGGED IN');
}

function exit_no_group() {
	exit_error('','CHOOSE A PROJECT/GROUP');
}

function exit_missing_param() {
	exit_error('','MISSING REQUIRED PARAMETERS');
}

?>
