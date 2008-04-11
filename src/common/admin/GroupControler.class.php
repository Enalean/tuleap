<?php
require_once('pre.php');
require_once('account.php');
require_once('www/project/admin/ugroup_utils.php');
require_once('group_view.php');

$Language->loadLanguageMsg('admin/admin');

session_require(array('group'=>'1','admin_flas'=>'A'));

$HTML->header(array('title'=>$Language->getText('admin_userlist','title')));


$groupSearchDisplay = new GroupSearchDisplay();


//Header
$groupSearchDisplay->displayHeader();

//Search
$groupSearchDisplay->displaySearchFilter();

//Browsing
$groupSearchDisplay->displayBrowse();


//Search table
$groupSearchDisplay->displaySearch();


//Browsing
$groupSearchDisplay->displayBrowse();

$HTML->footer(array());

?>