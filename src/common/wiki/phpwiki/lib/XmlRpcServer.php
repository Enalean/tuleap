<?php 
// $Id$
/* Copyright (C) 2002, Lawrence Akka <lakka@users.sourceforge.net>
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
 * This file provides an XML-RPC interface for PhpWiki.  It uses the XML-RPC 
 * library for PHP by Edd Dumbill - see http://xmlrpc.usefulinc.com/php.html 
 * for details.
 *
 * PHP >= 4.1.0 includes experimental support for the xmlrpc-epi c library 
 * written by Dan Libby (see http://uk2.php.net/manual/en/ref.xmlrpc.php).  This
 * is not compiled into PHP by default.  If it *is* compiled into your installation
 * (ie you have --with-xmlrpc) there may well be namespace conflicts with the xml-rpc
 * library used by this code, and you will get errors.
 * 
 * INTERFACE SPECIFICTION
 * ======================
 *  
 * The interface specification is that discussed at 
 * http://www.ecyrd.com/JSPWiki/Wiki.jsp?page=WikiRPCInterface
 * 
 * See also http://www.usemod.com/cgi-bin/mb.pl?XmlRpc
 * 
 * NB:  All XMLRPC methods should be prefixed with "wiki."
 * eg  wiki.getAllPages
 * 
*/

// ToDo:  
//        Make use of the xmlrpc extension if found. Resolve namespace conflicts
//        Remove all warnings from xmlrpc.inc 
//        Return list of external links in listLinks
 

// Intercept GET requests from confused users.  Only POST is allowed here!
// There is some indication that $HTTP_SERVER_VARS is deprecated in php > 4.1.0
// in favour of $_Server, but as far as I know, it still works.
if ($GLOBALS['HTTP_SERVER_VARS']['REQUEST_METHOD'] != "POST")  
{
    die('This is the address of the XML-RPC interface.' .
        '  You must use XML-RPC calls to access information here');
}

// Include the php XML-RPC library

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
include_once("lib/XMLRPC/xmlrpc.inc");

global $_xmlrpcs_dmap;
global $_xmlrpcs_debug;
include_once("lib/XMLRPC/xmlrpcs.inc");

//  API version
define ("WIKI_XMLRPC_VERSION", 1);

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
function UrlencodeControlCharacters($str) {
    return preg_replace('/([\x00-\x1F])/e', "urlencode('\\1')", $str);
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
function NoSuchPage () 
{
    global $xmlrpcerruser;
    return new xmlrpcresp(0, $xmlrpcerruser + 1, "No such page");
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
    return new xmlrpcresp(new xmlrpcval(WIKI_XMLRPC_VERSION, "int"));
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
    // Get the first parameter as an ISO 8601 date.  Assume UTC
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
        $pages[] = new xmlrpcval(array('name'=>$name, 
                                       'lastModified'=>$lastmodified,
                                       'author'=>$author,
                                       'version'=>$version),
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

    if (! $revision)
        return NoSuchPage();

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
        return NoSuchPage();

    $page = $dbh->getPage($pagename);
    /*
    $linkiterator = $page->getLinks(false);
    $linkstruct = array();
    while ($currentpage = $linkiterator->next()) {
        $currentname = $currentpage->getName();
        $name = short_string($currentname);
        // NB no clean way to extract a list of external links yet, so
        // only internal links returned.  ie all type 'local'.
        $type = new xmlrpcval('local');

        // Compute URL to page
        $args = array();
        $currentrev = $currentpage->getCurrentRevision();
        if ($currentrev->hasDefaultContents())
            $args['action'] = 'edit';

        // FIXME: Autodetected value of VIRTUAL_PATH wrong,
        // this make absolute URLs contstructed by WikiURL wrong.
        // Also, if USE_PATH_INFO is false, WikiURL is wrong
        // due to its use of SCRIPT_NAME.
        $use_abspath = USE_PATH_INFO && ! preg_match('/RPC2.php$/', VIRTUAL_PATH);
        $href = new xmlrpcval(WikiURL($currentname, $args, $use_abspath));
            
        $linkstruct[] = new xmlrpcval(array('name'=> $name,
                                            'type'=> $type,
                                            'href' => $href),
                                      "struct");
    }
    */
    
    $current = $page->getCurrentRevision();
    $content = $current->getTransformedContent();
    $links = $content->getLinkInfo();

    foreach ($links as $link) {
        // We used to give an href for unknown pages that
        // included action=edit.  I think that's probably the
        // wrong thing to do.
        $linkstruct[] = new xmlrpcval(array('name'=> short_string($link->page),
                                            'type'=> new xmlrpcval($link->type),
                                            'href' => short_string($link->href)),
                                      "struct");
    }
        
    return new xmlrpcresp(new xmlrpcval ($linkstruct, "array"));
} 
 
// Construct the server instance, and set up the despatch map, which maps
// the XML-RPC methods onto the wiki functions
class XmlRpcServer extends xmlrpc_server
{
    function XmlRpcServer ($request = false) {
        global $wiki_dmap;
        foreach ($wiki_dmap as $name => $val)
            $dmap['wiki.' . $name] = $val;
        
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
        xmlrpc_debugmsg($msg);
        return true;
    }
}


// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>
