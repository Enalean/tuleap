<?php 
// $Id: RPC2.php,v 1.5 2005/01/21 11:23:58 rurban Exp $
/*
 * The guts of this code have been moved to lib/XmlRpcServer.php.
 *
 * This file is really a vestige, as now, you can direct XML-RPC
 * request to the main wiki URL (e.g. index.php) --- it will
 * notice that you've POSTed content-type of text/xml and
 * fire up the XML-RPC server automatically.
 */

// Intercept GET requests from confused users.  Only POST is allowed here!
// There is some indication that $HTTP_SERVER_VARS is deprecated in php > 4.1.0
// in favour of $_Server, but as far as I know, it still works.
if ($_SERVER['REQUEST_METHOD'] != "POST")
{
    die('This is the address of the XML-RPC interface.' .
        '  You must use XML-RPC calls to access information here.');
}

// Constant defined to indicate to phpwiki that it is being accessed via XML-RPC
define ("WIKI_XMLRPC", "true");

// Start up the main code
include_once("index.php");
include_once("lib/main.php");

include_once("lib/XmlRpcServer.php");

$server = new XmlRpcServer;
$server->service();

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>