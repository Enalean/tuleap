<?php
$Language->loadLanguageMsg('include/include');

function exit_error($title,$text) {
  global $Language;
	print $Language->getText('include_squal_exit','err').' - '.$text;
	exit;
}

function exit_permission_denied() {
  global $Language;
	exit_error('',$Language->getText('include_squal_exit','perm_denied'));
}

function exit_not_logged_in() {
  global $Language;
	exit_error('',$Language->getText('include_menu','not_logged_in'));
}

function exit_no_group() {
  global $Language;
	exit_error('',$Language->getText('include_squal_exit','choose_proj'));
}

function exit_missing_param() {
  global $Language;
	exit_error('',$Language->getText('include_squal_exit','missing_param'));
}

?>
