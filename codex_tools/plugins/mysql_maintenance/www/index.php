<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 */
 
require_once('pre.php');
require_once('common/include/HTTPRequest.class.php');

$GLOBALS['Language']->loadLanguageMsg('MySQLMaintenance', 'mysql_maintenance');

session_require(array('group'=>'1','admin_flags'=>'A'));

//get Request object
$request =& HTTPRequest::instance();

$result_analyze = '';

if ($request->exist('do_analyze')) {
    require_once('common/dao/DBTablesDao.class.php');
    require_once('common/dao/CodexDataAccess.class.php');
    require_once('Table.php');
    $dao =& new DBTablesDao(CodeXDataAccess::instance());
    $dar =& $dao->searchAll();
    
    $table =& new Console_Table();
    $table->setHeaders(array('Table', 'Msg_type', 'Msg_text'));
    while ($data = $dar->getRow()) {
        $name = $data['Tables_in_'.$GLOBALS['sys_dbname']];
        $analyze_dar =& $dao->analyzeTable($name);
        $result = $analyze_dar->getRow();
        $table->addRow(array($name, $result['Msg_type'], $result['Msg_text']));
    }
    $result_analyze .= $Language->getText('plugin_MySQLMaintenance','result').'<pre>'.$table->getTable().'</pre>';
}

$title = $Language->getText('plugin_MySQLMaintenance','title');
$HTML->header(array('title'=>$title));
$output = '<h2>'.$title.'</h2>';


$output .= $result_analyze;
$output .= '<form action="" method="get"><input type="submit" name="do_analyze" value="'.$Language->getText('plugin_MySQLMaintenance','submit').'" /></form>';

echo $output;

$HTML->footer(array());
?>
