<?php
/**
 * SOAP server
 * Taken from http://www.wlug.org.nz/archive/
 * Please see http://phpwiki.sourceforge.net/phpwiki/PhpWiki.wdsl
 * for the wdsl discussion.
 *
 * Todo: 
 * checkCredentials: set the $GLOBALS['request']->_user object for 
 *                   mayAccessPage
 * enable native pecl extension (xml-rpc or soap)
 * serverurl: 
 *   Installer helper which changes server url of the default PhpWiki.wdsl
 *   Or do it dynamically in the soap class? No, the client must connect to us.
 *
 * @author: Reini Urban
 */
define ("WIKI_SOAP", "true");

include_once("./index.php");
include_once("lib/main.php");
require_once('lib/nusoap/nusoap.php');

/*
// bypass auth and request loop for now.
// require_once('lib/prepend.php');
include_once("index.php");

//require_once('lib/stdlib.php');
require_once('lib/WikiDB.php');
require_once('lib/config.php');
class WikiRequest extends Request {}
$request = new WikiRequest();
require_once('lib/PagePerm.php');
require_once('lib/WikiUserNew.php');
require_once('lib/WikiGroup.php');
*/

function checkCredentials(&$server, &$credentials, $access, $pagename) {
    // check the "Authorization: Basic '.base64_encode("$this->username:$this->password").'\r\n'" header
    if (isset($server->header['Authorization'])) {
        $line = base64_decode(str_replace("Basic ","",trim($server->header['Authorization'])));
        list($credentials['username'],$credentials['password']) = explode(':',$line);
    } else {
        // TODO: where in the header is the client IP
        if (!isset($credentials['username'])) {
            if (isset($_SERVER['REMOTE_ADDR']))
                $credentials['username'] = $_SERVER['REMOTE_ADDR'];
            elseif (isset($GLOBALS['REMOTE_ADDR']))
                $credentials['username'] = $GLOBALS['REMOTE_ADDR'];
            else 
                $credentials['username'] = $server->host;
        }
    }
    if (!isset($credentials['password'])) $credentials['password'] = '';

    global $request;
    if (ENABLE_USER_NEW) {
        $request->_user = WikiUser($credentials['username']);
    } else {
        $request->_user = new WikiUser($request, $credentials['username']);
    }
    $request->_user->AuthCheck(array('userid' => $credentials['username'], 'passwd' => $credentials['password']));

    if (! mayAccessPage ($access, $pagename))
        $server->fault(401,'',"no permission");
}

$GLOBALS['SERVER_NAME'] = SERVER_URL;
$GLOBALS['SCRIPT_NAME'] = DATA_PATH . "/SOAP.php";
$url = SERVER_URL . DATA_PATH . "/SOAP.php";

// Local or external wdsl support is experimental. 
// It works without also. Just the client has to 
// know the wdsl definitions.
$server = new soap_server(/* 'PhpWiki.wdsl' */);
// Now change the server url to ours, because in the wdsl is the original PhpWiki address
//   <soap:address location="http://phpwiki.sourceforge.net/phpwiki/SOAP.php"/>
//   <soap:operation soapAction="http://phpwiki.sourceforge.net/phpwiki/SOAP.php" />
/*
$server->ports[$server->currentPort]['location'] = $url;
$server->bindings[ $server->ports[$server->currentPort]['binding'] ]['endpoint'] = $url;
$server->soapaction = $url; // soap_transport_http
*/

$actions = array('getPageContent','getPageRevision','getCurrentRevision',
	         'getPageMeta','doSavePage','getAllPagenames',
	         'getBackLinks','doTitleSearch','doFullTextSearch');
foreach ($actions as $action) {
    $server->register($actions);
    $server->operations[$actions]['soapaction'] = $url;
}

//todo: check and set credentials
// requiredAuthorityForPage($action);
// require 'edit' access
function doSavePage($pagename,$content,$credentials=false) {
    global $server;
    checkCredentials($server, $credentials,'edit',$pagename);
    $dbi = WikiDB::open($GLOBALS['DBParams']);
    $page = $dbi->getPage($pagename);
    $current = $page->getCurrentRevision();
    $meta = $current->_data;
    $meta['summary'] = sprintf(_("SOAP Request %s", $credentials['username'])); // from user or IP ?
    $version = $current->getVersion();
    return $page->save($content, $version + 1, $meta);
}

// require 'view' access
function getPageContent($pagename,$credentials=false) {
    global $server;
    checkCredentials($server,$credentials,'view',$pagename);
    $dbi = WikiDB::open($GLOBALS['DBParams']);
    $page = $dbi->getPage($pagename);
    $rev = $page->getCurrentRevision();
    $text = $rev->getPackedContent();
    return $text;
}
// require 'view' access
function getPageRevision($pagename,$revision,$credentials=false) {
    global $server;
    checkCredentials($server,$credentials,'view',$pagename);
    $dbi = WikiDB::open($GLOBALS['DBParams']);
    $page = $dbi->getPage($pagename);
    $rev = $page->getCurrentRevision();
    $text = $rev->getPackedContent();
    return $text;
}
// require 'view' access
function getCurrentRevision($pagename,$credentials=false) {
    global $server;
    checkCredentials($server,$credentials,'view',$pagename);
    if (!mayAccessPage ('view',$pagename))
        $server->fault(401,'',"no permission");
    $dbi = WikiDB::open($GLOBALS['DBParams']);
    $page = $dbi->getPage($pagename);
    $rev = $page->getCurrentRevision();
    $version = $current->getVersion();
    return (double)$version;
}
// require 'change' or 'view' access ?
function getPageMeta($pagename,$credentials=false) {
    global $server;
    checkCredentials($server,$credentials,'view',$pagename);
    $dbi = WikiDB::open($GLOBALS['DBParams']);
    $page = $dbi->getPage($pagename);
    $rev = $page->getCurrentRevision();
    $meta = $rev->_data;
    //todo: reformat the meta hash
    return $meta;
}
// require 'view' access to AllPages
function getAllPagenames($credentials=false) {
    global $server;
    checkCredentials($server,$credentials,'view',_("AllPages"));
    $dbi = WikiDB::open($GLOBALS['DBParams']);
    $page_iter = $dbi->getAllPages();
    $pages = array();
    while ($page = $page_iter->next()) {
        $pages[] = array('pagename' => $page->_pagename);
    }
    return $pages;
}
// require 'view' access
function getBacklinks($pagename,$credentials=false) {
    global $server;
    checkCredentials($server,$credentials,'view',$pagename);
    $dbi = WikiDB::open($GLOBALS['DBParams']);
    $backend = &$dbi->_backend;
    $result =  $backend->get_links($pagename);
    $page_iter = new WikiDB_PageIterator($dbi, $result);
    $pages = array();
    while ($page = $page_iter->next()) {
        $pages[] = array('pagename' => $page->getName());
    }
    return $pages;
}
// require 'view' access to TitleSearch
function doTitleSearch($s, $credentials=false) {
    global $server;
    checkCredentials($server,$credentials,'view',_("TitleSearch"));
    $dbi = WikiDB::open($GLOBALS['DBParams']);
    $query = new TextSearchQuery($s);
    $page_iter = $dbi->titleSearch($query);
    $pages = array();
    while ($page = $page_iter->next()) {
        $pages[] = array('pagename' => $page->getName());
    }
    return $pages;
}
// require 'view' access to FullTextSearch
function doFullTextSearch($s, $credentials=false) {
    global $server;
    checkCredentials($server,$credentials,'view',_("FullTextSearch"));
    $dbi = WikiDB::open($GLOBALS['DBParams']);
    $query = new TextSearchQuery($s);
    $page_iter = $dbi->fullSearch($query);
    $pages = array();
    while ($page = $page_iter->next()) {
        $pages[] = array('pagename' => $page->getName());
    }
    return $pages;
}

$server->service($GLOBALS['HTTP_RAW_POST_DATA']);

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>