<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2006. All rights reserved
 *
 * 
 *
 */
/**
 * Simple API script available through HTTP
 *
 * input parameters:
 *    group_id : project where references are defined
 *    text     : HTML input text
 * output: HTML text with embedded references (links to goto script)
*/
require_once('pre.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/include/ReferenceManager.class.php');

header('Content-type: text/html');

$reference_manager =& ReferenceManager::instance();
$request =& HTTPRequest::instance();

$GLOBALS['Language']->loadLanguageMsg('project/project');

if (!$request->get('group_id')) {
    if (!$request->get('group_name')) {
        $group_id=100;
    } else {
        $group_id=group_getid_by_name($request->get('group_name'));
    }
 } else $group_id=$request->get('group_id');

if (!$request->get('text')) {
    # Empty string? return empty string...
    exit;
 }
if ($request->get('help')) {
    echo $GLOBALS['Language']->getText('project_reference', 'insert_syntax');
    exit;
}
$text = $request->get('text');
if (isset($_SERVER['CONTENT_TYPE']) && (strpos(strtolower($_SERVER['CONTENT_TYPE']), 'utf-8') !== FALSE)) {
    $text = utf8_decode($text);
}
echo nl2br(util_make_links(htmlentities($text, ENT_QUOTES), $group_id)."\n");
exit;

?>
