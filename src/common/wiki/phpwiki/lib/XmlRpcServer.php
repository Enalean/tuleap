<?php
// $Id: XmlRpcServer.php,v 1.14 2005/07/21 19:27:04 rurban Exp $
/* Copyright (C) 2002, Lawrence Akka <lakka@users.sourceforge.net>
 * Copyright (C) 2004, 2005 $ThePhpWikiProgrammingTeam
 *
 * LICENCE
 * =======
 * This file is part of PhpWiki.
 * 
 * PhpWiki is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * PhpWiki is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with PhpWiki; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * LIBRARY USED - POSSIBLE PROBLEMS
 * ================================
 * 
 * This file provides an XML-RPC interface for PhpWiki. 
 * It checks for the existence of the xmlrpc-epi c library by Dan Libby 
 * (see http://uk2.php.net/manual/en/ref.xmlrpc.php), and falls back to 
 * the slower PHP counterpart XML-RPC library by Edd Dumbill. 
 * See http://xmlrpc.usefulinc.com/php.html for details.
 * 
 * INTERFACE SPECIFICTION
 * ======================
 *  
 * The interface specification is that discussed at 
 * http://www.ecyrd.com/JSPWiki/Wiki.jsp?page=WikiRPCInterface
 * 
 * See also http://www.usemod.com/cgi-bin/mb.pl?XmlRpc
 * or http://www.devshed.com/c/a/PHP/Using-XMLRPC-with-PHP/
 * 
 * Note: All XMLRPC methods are automatically prefixed with "wiki."
 *       eg. "wiki.getAllPages"
*/

/*
ToDo:
        Remove all warnings from xmlrpc.inc 
        Return list of external links in listLinks
        Support RSS2 cloud subscription
Done:
        Test hwiki.jar xmlrpc interface (java visualization plugin)
     	Make use of the xmlrpc extension if found. http://xmlrpc-epi.sourceforge.net/
	Resolved namespace conflicts
*/

// Intercept GET requests from confused users.  Only POST is allowed here!
if ($_SERVER['REQUEST_METHOD'] != "POST")
{
    die('This is the address of the XML-RPC interface.' .
        '  You must use XML-RPC calls to access information here');
}

// All these global declarations make it so that this file
// (XmlRpcServer.php) can be included within a function body
// (not in global scope), and things will still work....

global $xmlrpcI4, $xmlrpcInt, $xmlrpcBoolean, $xmlrpcDouble, $xmlrpcString;
global $xmlrpcDateTime, $xmlrpcBase64, $xmlrpcArray, $xmlrpcStruct;
global $xmlrpcTypes;
global $xmlEntities;
global $xmlrpcerr, $xmlrpcstr;
global $xmlrpc_defencoding;
global $xmlrpcName, $xmlrpcVersion;
global $xmlrpcerruser, $xmlrpcerrxml;
global $xmlrpc_backslash;
global $_xh;

if (loadPhpExtension('xmlrpc')) { // fast c lib
    define('XMLRPC_EXT_LOADED', true);

    global $xmlrpc_util_path;
    $xmlrpc_util_path = dirname(__FILE__)."/XMLRPC/";
    include_once("lib/XMLRPC/xmlrpc_emu.inc"); 
    global $_xmlrpcs_debug;
    include_once("lib/XMLRPC/xmlrpcs_emu.inc");

 } else { // slow php lib
    define('XMLRPC_EXT_LOADED', true);

    // Include the php XML-RPC library
    include_once("lib/XMLRPC/xmlrpc.inc");

    global $_xmlrpcs_dmap;
    global $_xmlrpcs_debug;
    include_once("lib/XMLRPC/xmlrpcs.inc");
}
 

//  API version
define ("WIKI_XMLRPC_VERSION", 2);

/**
 * Helper function:  Looks up a page revision (most recent by default) in the wiki database
 * 
 * @param xmlrpcmsg $params :  string pagename [int version]
 * @return WikiDB _PageRevision object, or false if no such page
 */

function _getPageRevision ($params)
{
    global $request;
    $ParamPageName = $params->getParam(0);
    $ParamVersion = $params->getParam(1);
    $pagename = short_string_decode($ParamPageName->scalarval());
    $version =  ($ParamVersion) ? ($ParamVersion->scalarval()):(0);
    // FIXME:  test for version <=0 ??
    $dbh = $request->getDbh();
    if ($dbh->isWikiPage($pagename)) {
        $page = $dbh->getPage($pagename);
        if (!$version) {
            $revision = $page->getCurrentRevision();
        } else {
            $revision = $page->getRevision($version);
        } 
        return $revision;
    } 
    return false;
} 

/*
 * Helper functions for encoding/decoding strings.
 *
 * According to WikiRPC spec, all returned strings take one of either
 * two forms.  Short strings (page names, and authors) are converted to
 * UTF-8, then rawurlencode()d, and returned as XML-RPC <code>strings</code>.
 * Long strings (page content) are converted to UTF-8 then returned as
 * XML-RPC <code>base64</code> binary objects.
 */

/**
 * Urlencode ASCII control characters.
 *
 * (And control characters...)
 *
 * @param string $str
 * @return string
 * @see urlencode
 */
function UrlencodeControlCharacters($str)
{
    return preg_replace_callback(
        '/([\x00-\x1F])/',
        function (array $matches) {
            return urlencode($matches[1]);
        },
        $str
    );
}

/**
 * Convert a short string (page name, author) to xmlrpcval.
 */
function short_string ($str) {
    return new xmlrpcval(UrlencodeControlCharacters(utf8_encode($str)), 'string');
}

/**
 * Convert a large string (page content) to xmlrpcval.
 */
function long_string ($str) {
    return new xmlrpcval(utf8_encode($str), 'base64');
}

/**
 * Decode a short string (e.g. page name)
 */
function short_string_decode ($str) {
    return utf8_decode(urldecode($str));
}

/**
 * Get an xmlrpc "No such page" error message
 */
function NoSuchPage ($pagename='') 
{
    global $xmlrpcerruser;
    return new xmlrpcresp(0, $xmlrpcerruser + 1, "No such page ".$pagename);
}


// ****************************************************************************
// Main API functions follow
// ****************************************************************************
global $wiki_dmap;

/**
 * int getRPCVersionSupported(): Returns 1 for this version of the API 
 */
$wiki_dmap['getRPCVersionSupported']
= array('signature'	=> array(array($xmlrpcInt)),
        'documentation' => 'Get the version of the wiki API',
        'function'	=> 'getRPCVersionSupported');

// The function must be a function in the global scope which services the XML-RPC
// method.
function getRPCVersionSupported($params)
{
    return new xmlrpcresp(new xmlrpcval((integer)WIKI_XMLRPC_VERSION, "int"));
}

/**
 * array getRecentChanges(Date timestamp) : Get list of changed pages since 
 * timestamp, which should be in UTC. The result is an array, where each element
 * is a struct: 
 *     name (string) : Name of the page. The name is UTF-8 with URL encoding to make it ASCII. 
 *     lastModified (date) : Date of last modification, in UTC. 
 *     author (string) : Name of the author (if available). Again, name is UTF-8 with URL encoding. 
 * 	   version (int) : Current version. 
 * A page MAY be specified multiple times. A page MAY NOT be specified multiple 
 * times with the same modification date.
 */
$wiki_dmap['getRecentChanges']
= array('signature'	=> array(array($xmlrpcArray, $xmlrpcDateTime)),
        'documentation' => 'Get a list of changed pages since [timestamp]',
        'function'	=> 'getRecentChanges');

function getRecentChanges($params)
{
    global $request;
    // Get the first parameter as an ISO 8601 date. Assume UTC
    $encoded_date = $params->getParam(0);
    $datetime = iso8601_decode($encoded_date->scalarval(), 1);
    $dbh = $request->getDbh();
    $pages = array();
    $iterator = $dbh->mostRecent(array('since' => $datetime));
    while ($page = $iterator->next()) {
        // $page contains a WikiDB_PageRevision object
        // no need to url encode $name, because it is already stored in that format ???
        $name = short_string($page->getPageName());
        $lastmodified = new xmlrpcval(iso8601_encode($page->get('mtime')), "dateTime.iso8601");
        $author = short_string($page->get('author'));
        $version = new xmlrpcval($page->getVersion(), 'int');

        // Build an array of xmlrpc structs
        $pages[] = new xmlrpcval(array('name' => $name, 
                                       'lastModified' => $lastmodified,
                                       'author' => $author,
                                       'version' => $version),
                                 'struct');
    } 
    return new xmlrpcresp(new xmlrpcval($pages, "array"));
} 


/**
 * base64 getPage( String pagename ): Get the raw Wiki text of page, latest version. 
 * Page name must be UTF-8, with URL encoding. Returned value is a binary object,
 * with UTF-8 encoded page data.
 */
$wiki_dmap['getPage']
= array('signature'	=> array(array($xmlrpcBase64, $xmlrpcString)),
        'documentation' => 'Get the raw Wiki text of the current version of a page',
        'function'	=> 'getPage');

function getPage($params)
{
    $revision = _getPageRevision($params);

    if (! $revision ) {
        $ParamPageName = $params->getParam(0);
        $pagename = short_string_decode($ParamPageName->scalarval());
        return NoSuchPage($pagename);
    }

    return new xmlrpcresp(long_string($revision->getPackedContent()));
}
 

/**
 * base64 getPageVersion( String pagename, int version ): Get the raw Wiki text of page.
 * Returns UTF-8, expects UTF-8 with URL encoding.
 */
$wiki_dmap['getPageVersion']
= array('signature'	=> array(array($xmlrpcBase64, $xmlrpcString, $xmlrpcInt)),
        'documentation' => 'Get the raw Wiki text of a page version',
        'function'	=> 'getPageVersion');

function getPageVersion($params)
{
    // error checking is done in getPage
    return getPage($params);
} 

/**
 * base64 getPageHTML( String pagename ): Return page in rendered HTML. 
 * Returns UTF-8, expects UTF-8 with URL encoding.
 */

$wiki_dmap['getPageHTML']
= array('signature'	=> array(array($xmlrpcBase64, $xmlrpcString)),
        'documentation' => 'Get the current version of a page rendered in HTML',
        'function'	=> 'getPageHTML');

function getPageHTML($params)
{
    $revision = _getPageRevision($params);
    if (!$revision)
        return NoSuchPage();
    
    $content = $revision->getTransformedContent();
    $html = $content->asXML();
    // HACK: Get rid of outer <div class="wikitext">
    if (preg_match('/^\s*<div class="wikitext">/', $html, $m1)
	&& preg_match('@</div>\s*$@', $html, $m2)) {
	$html = substr($html, strlen($m1[0]), -strlen($m2[0]));
    }

    return new xmlrpcresp(long_string($html));
} 

/**
 * base64 getPageHTMLVersion( String pagename, int version ): Return page in rendered HTML, UTF-8.
 */
$wiki_dmap['getPageHTMLVersion']
= array('signature'	=> array(array($xmlrpcBase64, $xmlrpcString, $xmlrpcInt)),
        'documentation' => 'Get a version of a page rendered in HTML',
        'function'	=> 'getPageHTMLVersion');

function getPageHTMLVersion($params)
{
    return getPageHTML($params);
} 

/**
 * getAllPages(): Returns a list of all pages. The result is an array of strings.
 */
$wiki_dmap['getAllPages']
= array('signature'	=> array(array($xmlrpcArray)),
        'documentation' => 'Returns a list of all pages as an array of strings', 
        'function'	=> 'getAllPages');

function getAllPages($params)
{
    global $request;
    $dbh = $request->getDbh();
    $iterator = $dbh->getAllPages();
    $pages = array();
    while ($page = $iterator->next()) {
        $pages[] = short_string($page->getName());
    } 
    return new xmlrpcresp(new xmlrpcval($pages, "array"));
} 

/**
 * struct getPageInfo( string pagename ) : returns a struct with elements: 
 *   name (string): the canonical page name 
 *   lastModified (date): Last modification date 
 *   version (int): current version 
 * 	 author (string): author name 
 */
$wiki_dmap['getPageInfo']
= array('signature'	=> array(array($xmlrpcStruct, $xmlrpcString)),
        'documentation' => 'Gets info about the current version of a page',
        'function'	=> 'getPageInfo');

function getPageInfo($params)
{
    $revision = _getPageRevision($params);
    if (!$revision)
        return NoSuchPage();
    
    $name = short_string($revision->getPageName());
    $version = new xmlrpcval ($revision->getVersion(), "int");
    $lastmodified = new xmlrpcval(iso8601_encode($revision->get('mtime'), 0),
                                  "dateTime.iso8601");
    $author = short_string($revision->get('author'));
        
    return new xmlrpcresp(new xmlrpcval(array('name' => $name, 
                                              'lastModified' => $lastmodified,
                                              'version' => $version, 
                                              'author' => $author), 
                                        "struct"));
} 

/**
 * struct getPageInfoVersion( string pagename, int version ) : returns
 * a struct just like plain getPageInfo(), but this time for a
 * specific version.
 */
$wiki_dmap['getPageInfoVersion']
= array('signature'	=> array(array($xmlrpcStruct, $xmlrpcString, $xmlrpcInt)),
        'documentation' => 'Gets info about a page version',
        'function'	=> 'getPageInfoVersion');

function getPageInfoVersion($params)
{
    return getPageInfo($params);
}

 
/*  array listLinks( string pagename ): Lists all links for a given page. The
 *  returned array contains structs, with the following elements: 
 *   	 name (string) : The page name or URL the link is to. 
 *       type (int) : The link type. Zero (0) for internal Wiki link,
 *         one (1) for external link (URL - image link, whatever).
 */
$wiki_dmap['listLinks']
= array('signature'	=> array(array($xmlrpcArray, $xmlrpcString)),
        'documentation' => 'Lists all links for a given page',
        'function'	=> 'listLinks');

function listLinks($params)
{
    global $request;
    
    $ParamPageName = $params->getParam(0);
    $pagename = short_string_decode($ParamPageName->scalarval());
    $dbh = $request->getDbh();
    if (! $dbh->isWikiPage($pagename))
        return NoSuchPage($pagename);

    $page = $dbh->getPage($pagename);
    
    // The fast WikiDB method. below is the slow method which goes through the formatter
    // NB no clean way to extract a list of external links yet, so
    // only internal links returned.  i.e. all type 'local'.
    $linkiterator = $page->getPageLinks();
    $linkstruct = array();
    while ($currentpage = $linkiterator->next()) {
        $currentname = $currentpage->getName();
        // Compute URL to page
        $args = array();
        // How to check external links?
        if (!$currentpage->exists()) $args['action'] = 'edit';

        // FIXME: Autodetected value of VIRTUAL_PATH wrong,
        // this make absolute URLs constructed by WikiURL wrong.
        // Also, if USE_PATH_INFO is false, WikiURL is wrong
        // due to its use of SCRIPT_NAME.
        //$use_abspath = USE_PATH_INFO && ! preg_match('/RPC2.php$/', VIRTUAL_PATH);
        
        // USE_PATH_INFO must be defined in index.php or config.ini but not before, 
        // otherwise it is ignored and xmlrpc urls are wrong.
        // SCRIPT_NAME here is always .../RPC2.php
        if (USE_PATH_INFO and !$args) {
            $url = preg_replace('/%2f/i', '/', rawurlencode($currentname));
        } elseif (!USE_PATH_INFO) {
            $url = str_replace("/RPC2.php","/index.php", WikiURL($currentname, $args, true));
        } else {
            $url = WikiURL($currentname, $args);
        }
        $linkstruct[] = new xmlrpcval(array('page'=> short_string($currentname),
                                            'type'=> new xmlrpcval('local', 'string'),
                                            'href' => short_string($url)),
                                      "struct");
    }
   
    /*
    $current = $page->getCurrentRevision();
    $content = $current->getTransformedContent();
    $links = $content->getLinkInfo();
    foreach ($links as $link) {
        // We used to give an href for unknown pages that
        // included action=edit.  I think that's probably the
        // wrong thing to do.
        $linkstruct[] = new xmlrpcval(array('page'=> short_string($link->page),
                                            'type'=> new xmlrpcval($link->type, 'string'),
                                            'href' => short_string($link->href),
                                            //'pageref' => short_string($link->pageref),
                                            ),
                                      "struct");
    }
    */
    return new xmlrpcresp(new xmlrpcval ($linkstruct, "array"));
} 

/** 
 * struct putPage(String pagename, String content, [String author[, String password]})
 * returns a struct with elements: 
 *   code (int): 200 on success, 400 or 401 on failure
 *   message (string): success or failure message
 *   version (int): version of new page
 *
 * @author: Arnaud Fontaine, Reini Urban
 */
$wiki_dmap['putPage']
= array('signature'     => array(array($xmlrpcStruct, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString)),
        'documentation' => 'put the raw Wiki text into a page as new version',
        'function'      => 'putPage');

function _getUser($userid='') {
    global $request;
    
    if (! $userid ) {
        if (isset($_SERVER['REMOTE_USER']))
            $userid = $_SERVER['REMOTE_USER'];
        elseif (isset($_ENV['REMOTE_USER']))
            $userid = $_ENV['REMOTE_USER'];
        elseif (isset($_SERVER['REMOTE_ADDR']))
            $userid = $_SERVER['REMOTE_ADDR'];
        elseif (isset($_ENV['REMOTE_ADDR']))
            $userid = $_ENV['REMOTE_ADDR'];
        elseif (isset($GLOBALS['REMOTE_ADDR']))
            $userid = $GLOBALS['REMOTE_ADDR'];
    }

    if (ENABLE_USER_NEW) {
        return WikiUser($userid);
    } else {
        return new WikiUser($request, $userid);
    }
}
        
function putPage($params) {
    global $request;

    $ParamPageName = $params->getParam(0);
    $ParamContent = $params->getParam(1);
    $pagename = short_string_decode($ParamPageName->scalarval());
    $content = short_string_decode($ParamContent->scalarval());
    $passwd = '';
    if (count($params->params) > 2) {
        $ParamAuthor = $params->getParam(2);
        $userid = short_string_decode($ParamAuthor->scalarval());
        if (count($params->params) > 3) {
            $ParamPassword = $params->getParam(3);
            $passwd = short_string_decode($ParamPassword->scalarval());
        }
    } else {
    	$userid = $request->_user->_userid;
    }
    $request->_user = _getUser($userid);
    $request->_user->_group = $request->getGroup();
    $request->_user->AuthCheck($userid, $passwd);
                                         
    if (! mayAccessPage ('edit', $pagename)) {
        return new xmlrpcresp(
                              new xmlrpcval(
                                            array('code' => new xmlrpcval(401, "int"), 
                                                  'version' => new xmlrpcval(0, "int"), 
                                                  'message' => 
                                                  short_string("no permission for "
                                                               .$request->_user->UserName())), 
                                            "struct"));
    }

    $now = time();
    $dbh = $request->getDbh();
    $page = $dbh->getPage($pagename);
    $current = $page->getCurrentRevision();
    $content = trim($content);
    $version = $current->getVersion();
    // $version = -1 will force create a new version
    if ($current->getPackedContent() != $content) {
        $init_meta = array('ctime' => $now,
                           'creator' => $userid,
                           'creator_id' => $userid,
                           );
        $version_meta = array('author' => $userid,
                              'author_id' => $userid,
                              'markup' => 2.0,
                              'summary' => isset($summary) ? $summary : _("xml-rpc change"),
                              'mtime' => $now,
                              'pagetype' => 'wikitext',
                              'wikitext' => $init_meta,
                              );
        $version++;
        $res = $page->save($content, $version, $version_meta);
        if ($res)
            $message = "Page $pagename version $version created";
        else
            $message = "Problem creating version $version of page $pagename";
    } else {
    	$res = 0;
    	$message = $message = "Page $pagename unchanged";
    }
    return new xmlrpcresp(new xmlrpcval(array('code'    => new xmlrpcval($res ? 200 : 400, "int"), 
                                              'version' => new xmlrpcval($version, "int"), 
                                              'message' => short_string($message)), 
                                        "struct"));
}

/**
 * Publish-Subscribe
 * Client subscribes to a RecentChanges-like channel, getting a short 
 * callback notification on every change. Like PageChangeNotification, just shorter 
 * and more complicated
 * RSS2 support (not yet), since radio userland's rss-0.92. now called RSS2.
 * BTW: Radio Userland deprecated this interface.
 *
 * boolean wiki.rssPleaseNotify ( notifyProcedure, port, path, protocol, urlList )
 *   returns: true or false 
 *
 * Check of the channel behind the rssurl has a cloud element, 
 * if the client has a direct IP connection (no NAT),
 * register the client on the WikiDB notification handler
 *
 * http://backend.userland.com/publishSubscribeWalkthrough
 * http://www.soapware.org/xmlStorageSystem#rssPleaseNotify
 * http://www.thetwowayweb.com/soapmeetsrss#rsscloudInterface
 */
$wiki_dmap['rssPleaseNotify']
= array('signature'	=> array(array($xmlrpcBoolean, $xmlrpcStruct)),
        'documentation' => 'RSS2 change notification subscriber channel',
        'function'	=> 'rssPleaseNotify');

function rssPleaseNotify($params)
{
    // register the clients IP
    return new xmlrpcresp(new xmlrpcval (0, "boolean"));
}

/*
 *  String wiki.rssPleaseNotify ( username )
 *  returns: true or false 

 */
$wiki_dmap['mailPasswordToUser']
= array('signature'	=> array(array($xmlrpcBoolean, $xmlrpcString)),
        'documentation' => 'RSS2 user management helper',
        'function'	=> 'mailPasswordToUser');

function mailPasswordToUser($params)
{
    global $request;
    $ParamUserid = $params->getParam(0);
    $userid = short_string_decode($ParamUserid->scalarval());
    $request->_user = _getUser($userid);
    //$request->_prefs =& $request->_user->_prefs;
    $email = $request->getPref('email');
    $success = 0;
    if ($email) {
        $body = WikiURL('') . "\nPassword: " . $request->getPref('passwd');
        $success = mail($email, "[".WIKI_NAME."} Password Request", 
                        $body);
    }
    return new xmlrpcresp(new xmlrpcval ($success, "boolean"));
}

/** 
 * array wiki.titleSearch(String substring [, Integer option = 0])
 * returns an array of matching pagenames.
 * TODO: standardize options
 *
 * @author: Reini Urban
 */
$wiki_dmap['titleSearch']
= array('signature'     => array(array($xmlrpcArray, $xmlrpcString, $xmlrpcInt)),
        'documentation' => "Return matching pagenames. 
Option 1: caseexact, 2: regex, 4: starts_with, 8: exact",
        'function'      => 'titleSearch');

function titleSearch($params)
{
    global $request;
    $ParamPageName = $params->getParam(0);
    $searchstring = short_string_decode($ParamPageName->scalarval());
    if (count($params->params) > 1) {
        $ParamOption = $params->getParam(1);
        $option = $ParamOption->scalarval();
    } else $option = 0;
    // default option: substring, case-inexact

    $case_exact = $option & 1;
    $regex      = $option & 2;
    if (!$regex) {
        if ($option & 4) { // STARTS_WITH
            $regex = true;
            $searchstring = "^".$searchstring;
        }
        if ($option & 8) { // EXACT
            $regex = true;
            $searchstring = "^".$searchstring."$";
        }
    } else {
    	if ($option & 4 or $option & 8) { 
		    global $xmlrpcerruser;
    		return new xmlrpcresp(0, $xmlrpcerruser + 1, "Invalid option");
    	}
    }
    include_once("lib/TextSearchQuery.php");
    $query = new TextSearchQuery($searchstring, $case_exact, $regex ? 'auto' : 'none');
    $dbh = $request->getDbh();
    $iterator = $dbh->titleSearch($query);
    $pages = array();
    while ($page = $iterator->next()) {
        $pages[] = short_string($page->getName());
    } 
    return new xmlrpcresp(new xmlrpcval($pages, "array"));
}

/** 
 * array wiki.listPlugins()
 *
 * Returns an array of all available plugins. 
 * For EditToolbar pluginPulldown via AJAX
 *
 * @author: Reini Urban
 */
$wiki_dmap['listPlugins']
= array('signature'     => array(array($xmlrpcArray)),
        'documentation' => "Return names of all plugins",
        'function'      => 'listPlugins');

function listPlugins($params)
{
    $plugin_dir = 'lib/plugin';
    if (defined('PHPWIKI_DIR'))
        $plugin_dir = PHPWIKI_DIR . "/$plugin_dir";
    $pd = new fileSet($plugin_dir, '*.php');
    $plugins = $pd->getFiles();
    unset($pd);
    sort($plugins);
    $RetArray = array();
    if (!empty($plugins)) {
        require_once("lib/WikiPlugin.php");
        $w = new WikiPluginLoader;
        foreach ($plugins as $plugin) {
            $pluginName = str_replace(".php", "", $plugin);
            $p = $w->getPlugin($pluginName, false); // second arg?
            // trap php files which aren't WikiPlugin~s: wikiplugin + wikiplugin_cached only
            if (strtolower(substr(get_parent_class($p), 0, 10)) == 'wikiplugin') {
                $RetArray[] = short_string($pluginName);
            }
        }
    }
  
    return new xmlrpcresp(new xmlrpcval($RetArray, "array"));
}

/** 
 * String wiki.getPluginSynopsis(String plugin)
 *
 * For EditToolbar pluginPulldown via AJAX
 *
 * @author: Reini Urban
 */
$wiki_dmap['getPluginSynopsis']
= array('signature'     => array(array($xmlrpcArray, $xmlrpcString)),
        'documentation' => "Return plugin synopsis",
        'function'      => 'getPluginSynopsis');

function getPluginSynopsis($params)
{
    $ParamPlugin = $params->getParam(0);
    $pluginName = short_string_decode($ParamPlugin->scalarval());

    require_once("lib/WikiPlugin.php");
    $w = new WikiPluginLoader;
    $synopsis = '';
    $p = $w->getPlugin($pluginName, false); // second arg?
    // trap php files which aren't WikiPlugin~s: wikiplugin + wikiplugin_cached only
    if (strtolower(substr(get_parent_class($p), 0, 10)) == 'wikiplugin') {
        $plugin_args = '';
        $desc = $p->getArgumentsDescription();
        $src = array("\n",'"',"'",'|','[',']','\\');
        $replace = array('%0A','%22','%27','%7C','%5B','%5D','%5C');
        $desc = str_replace("<br />",' ',$desc->asXML());
        if ($desc)
            $plugin_args = '\n'.str_replace($src, $replace, $desc);
        $synopsis = "<?plugin ".$pluginName.$plugin_args."?>"; // args?
    }
   
    return new xmlrpcresp(short_string($synopsis));
}

/** 
 * array wiki.listRelations()
 *
 * Returns an array of all available relations. 
 * For SemanticSearch autofill method.
 *
 * @author: Reini Urban
 */
$wiki_dmap['listRelations']
= array('signature'     => array(array($xmlrpcArray)),
        'documentation' => "Return names of all relations",
        'function'      => 'listRelations');

function listRelations($params)
{
    global $request;
    $dbh = $request->getDbh();
    $RetArray = $dbh->listRelations();
    return new xmlrpcresp(new xmlrpcval($RetArray->asArray(), "array"));
}

/** 
 * String pingback.ping(String sourceURI, String targetURI)

Spec: http://www.hixie.ch/specs/pingback/pingback

Parameters
    sourceURI of type string
        The absolute URI of the post on the source page containing the
        link to the target site.
    targetURI of type string
        The absolute URI of the target of the link, as given on the source page.
Return Value
    A string, as described below.
Faults
    If an error condition occurs, then the appropriate fault code from
    the following list should be used. Clients can quickly determine
    the kind of error from bits 5-8. 0x001x fault codes are used for
    problems with the source URI, 0x002x codes are for problems with
    the target URI, and 0x003x codes are used when the URIs are fine
    but the pingback cannot be acknowledged for some other reaon.

    0 
    	A generic fault code. Servers MAY use this error code instead
    	of any of the others if they do not have a way of determining
    	the correct fault code.
    0x0010 (16)
        The source URI does not exist.
    0x0011 (17)
        The source URI does not contain a link to the target URI, and
        so cannot be used as a source.
    0x0020 (32)
        The specified target URI does not exist. This MUST only be
        used when the target definitely does not exist, rather than
        when the target may exist but is not recognised. See the next
        error.
    0x0021 (33)
        The specified target URI cannot be used as a target. It either
        doesn't exist, or it is not a pingback-enabled resource. For
        example, on a blog, typically only permalinks are
        pingback-enabled, and trying to pingback the home page, or a
        set of posts, will fail with this error.
    0x0030 (48)
        The pingback has already been registered.
    0x0031 (49)
        Access denied.
    0x0032 (50)
        The server could not communicate with an upstream server, or
        received an error from an upstream server, and therefore could
        not complete the request. This is similar to HTTP's 402 Bad
        Gateway error. This error SHOULD be used by pingback proxies
        when propagating errors.

    In addition, [FaultCodes] defines some standard fault codes that
    servers MAY use to report higher level errors.

Servers MUST respond to this function call either with a single string
or with a fault code.

If the pingback request is successful, then the return value MUST be a
single string, containing as much information as the server deems
useful. This string is only expected to be used for debugging
purposes.

If the result is unsuccessful, then the server MUST respond with an
RPC fault value. The fault code should be either one of the codes
listed above, or the generic fault code zero if the server cannot
determine the correct fault code.

Clients MAY ignore the return value, whether the request was
successful or not. It is RECOMMENDED that clients do not show the
result of successful requests to the user.

Upon receiving a request, servers MAY do what they like. However, the
following steps are RECOMMENDED:

   1. The server MAY attempt to fetch the source URI to verify that
   the source does indeed link to the target.
   2. The server MAY check its own data to ensure that the target
   exists and is a valid entry.
   3. The server MAY check that the pingback has not already been registered.
   4. The server MAY record the pingback.
   5. The server MAY regenerate the site's pages (if the pages are static).

 * @author: Reini Urban
 */
$wiki_dmap['pingback.ping']
= array('signature'     => array(array($xmlrpcString, $xmlrpcString, $xmlrpcString)),
        'documentation' => "",
        'function'      => 'pingBack');
function pingBack($params)
{
    global $request;
    $Param0 = $params->getParam(0);
    $sourceURI = short_string_decode($Param0->scalarval());
    $Param1 = $params->getParam(1);
    $targetURI = short_string_decode($Param1->scalarval());
    // TODO...
}
 
/** 
 * Construct the server instance, and set up the dispatch map, 
 * which maps the XML-RPC methods on to the wiki functions.
 * Provide the "wiki." prefix to each function. Besides 
 * the blog - pingback, ... - functions with a seperate namespace.
 */
class XmlRpcServer extends xmlrpc_server
{
    function XmlRpcServer ($request = false) {
        global $wiki_dmap;
        foreach ($wiki_dmap as $name => $val) {
            if ($name == 'pingback.ping') // non-wiki methods
                $dmap[$name] = $val;
            else
                $dmap['wiki.' . $name] = $val;
        }

        $this->xmlrpc_server($dmap, 0 /* delay service*/);
    }

    function service () {
        global $ErrorManager;

        $ErrorManager->pushErrorHandler(new WikiMethodCb($this, '_errorHandler'));
        xmlrpc_server::service();
        $ErrorManager->popErrorHandler();
    }
    
    function _errorHandler ($e) {
        $msg = htmlspecialchars($e->asString());
        // '--' not allowed within xml comment
        $msg = str_replace('--', '&#45;&#45;', $msg);
        if (function_exists('xmlrpc_debugmsg'))
            xmlrpc_debugmsg($msg);
        return true;
    }
}

/*
 $Log: XmlRpcServer.php,v $
 Revision 1.17  2005/10/31 16:49:31  rurban
 fix doc

 Revision 1.16  2005/10/29 14:17:51  rurban
 fix doc

 Revision 1.15  2005/10/29 08:57:12  rurban
 fix for !register_long_arrays
 new: array wiki.listPlugins()
      String wiki.getPluginSynopsis(String plugin)
      String pingback.ping(String sourceURI, String targetURI) (preliminary)


 */

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>