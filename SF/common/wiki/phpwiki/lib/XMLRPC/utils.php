<?php

/*
  This file is part of, or distributed with, libXMLRPC - a C library for 
  xml-encoded function calls.

  Author: Dan Libby (dan@libby.com)
  Epinions.com may be contacted at feedback@epinions-inc.com
*/

/*  
  Copyright 2001 Epinions, Inc. 

  Subject to the following 3 conditions, Epinions, Inc.  permits you, free 
  of charge, to (a) use, copy, distribute, modify, perform and display this 
  software and associated documentation files (the "Software"), and (b) 
  permit others to whom the Software is furnished to do so as well.  

  1) The above copyright notice and this permission notice shall be included 
  without modification in all copies or substantial portions of the 
  Software.  

  2) THE SOFTWARE IS PROVIDED "AS IS", WITHOUT ANY WARRANTY OR CONDITION OF 
  ANY KIND, EXPRESS, IMPLIED OR STATUTORY, INCLUDING WITHOUT LIMITATION ANY 
  IMPLIED WARRANTIES OF ACCURACY, MERCHANTABILITY, FITNESS FOR A PARTICULAR 
  PURPOSE OR NONINFRINGEMENT.  

  3) IN NO EVENT SHALL EPINIONS, INC. BE LIABLE FOR ANY DIRECT, INDIRECT, 
  SPECIAL, INCIDENTAL OR CONSEQUENTIAL DAMAGES OR LOST PROFITS ARISING OUT 
  OF OR IN CONNECTION WITH THE SOFTWARE (HOWEVER ARISING, INCLUDING 
  NEGLIGENCE), EVEN IF EPINIONS, INC.  IS AWARE OF THE POSSIBILITY OF SUCH 
  DAMAGES.    

*/


/* xmlrpc utilities (xu) 
 * author: Dan Libby (dan@libby.com)
 */

// ensure extension is loaded.
xu_load_extension();

// a function to ensure the xmlrpc extension is loaded.
// xmlrpc_epi_dir = directory where libxmlrpc.so.0 is located
// xmlrpc_php_dir = directory where xmlrpc-epi-php.so is located
function xu_load_extension($xmlrpc_php_dir="") {
   $bSuccess = extension_loaded('xmlrpc');
   if (!$bSuccess) {
      putenv("LD_LIBRARY_PATH=/usr/lib/php4/apache/xmlrpc/");
      if ($xmlrpc_php_dir) {
          $xmlrpc_php_dir .= '/';
      }
      if (substr(PHP_OS,0,3) == 'WIN')
          $bSuccess = dl("php_xmlrpc.dll");
      else    
          $bSuccess = dl($xmlrpc_php_dir . "xmlrpc-epi-php.so");
   }
   return $bSuccess;
}

/* generic function to call an http server with post method */
function xu_query_http_post($request, $host, $uri, $port, $debug, 
                            $timeout, $user, $pass, $secure=false) {
    $response_buf = "";
    if ($host && $uri && $port) {
        $content_len = strlen($request);

        $fsockopen = $secure ? "fsockopen_ssl" : "fsockopen";

        dbg1("opening socket to host: $host, port: $port, uri: $uri", $debug);
        $query_fd = $fsockopen($host, $port, $errno, $errstr, 10);

        if ($query_fd) {

            $auth = "";
            if ($user) {
                $auth = "Authorization: Basic " .
                    base64_encode($user . ":" . $pass) . "\r\n";
            }

            $http_request = 
                "POST $uri HTTP/1.0\r\n" .
                "User-Agent: xmlrpc-epi-php/0.2 (PHP)\r\n" .
                "Host: $host:$port\r\n" .
                $auth .
                "Content-Type: text/xml\r\n" .
                "Content-Length: $content_len\r\n" . 
                "\r\n" .
                $request;

           dbg1("sending http request:</em><br /> <xmp>\n$http_request\n</xmp>", $debug);

           fputs($query_fd, $http_request, strlen($http_request));

           dbg1("receiving response...", $debug);

           $header_parsed = false;

           $line = fgets($query_fd, 4096);
           while ($line) {
               if (!$header_parsed) {
                   if ($line === "\r\n" || $line === "\n") {
                       $header_parsed = 1;
                   }
                   dbg2("got header - $line", $debug);
               }
               else {
                   $response_buf .= $line;
               }
               $line = fgets($query_fd, 4096);
           }

           fclose($query_fd);
       }
       else {
           dbg1("socket open failed", $debug);
       }
   }
   else {
       dbg1("missing param(s)", $debug);
   }

   dbg1("got response:</em><br />. <xmp>\n$response_buf\n</xmp>\n", $debug);

   return $response_buf;
}

function xu_fault_code($code, $string) {
   return array('faultCode' => $code,
                'faultString' => $string);
}


function find_and_decode_xml($buf, $debug) {
    if (strlen($buf)) {
        $xml_begin = substr($buf, strpos($buf, "<?xml"));
        if (strlen($xml_begin)) {

            $retval = xmlrpc_decode($xml_begin);
        }
        else {
            dbg1("xml start token not found", $debug);
        }
    }
    else {
        dbg1("no data", $debug);
    }
    return $retval;
}
 
/**
 * @param params   a struct containing 3 or more of these key/val pairs:
 * @param host		 remote host (required)
 * @param uri		 remote uri	 (required)
 * @param port		 remote port (required)
 * @param method   name of method to call
 * @param args	    arguments to send (parameters to remote xmlrpc server)
 * @param debug	 debug level (0 none, 1, some, 2 more)
 * @param timeout	 timeout in secs.  (0 = never)
 * @param user		 user name for authentication.  
 * @param pass		 password for authentication
 * @param secure	 secure. wether to use fsockopen_ssl. (requires special php build).
 * @param output	 array. xml output options. can be null.  details below:
 *
 *     output_type: return data as either php native data types or xml
 *                  encoded. if php is used, then the other values are ignored. default = xml
 *     verbosity:   determine compactness of generated xml. options are
 *                  no_white_space, newlines_only, and pretty. default = pretty
 *     escaping:    determine how/whether to escape certain characters. 1 or
 *                  more values are allowed. If multiple, they need to be specified as
 *                  a sub-array. options are: cdata, non-ascii, non-print, and
 *                  markup. default = non-ascii | non-print | markup
 *     version:     version of xml vocabulary to use. currently, three are
 *                  supported: xmlrpc, soap 1.1, and simple. The keyword auto is also
 *                  recognized to mean respond in whichever version the request came
 *                  in. default = auto (when applicable), xmlrpc
 *     encoding:    the encoding that the data is in. Since PHP defaults to
 *                  iso-8859-1 you will usually want to use that. Change it if you know
 *                  what you are doing. default=iso-8859-1
 *
 *   example usage
 *
 *                   $output_options = array('output_type' => 'xml',
 *                                           'verbosity' => 'pretty',
 *                                           'escaping' => array('markup', 'non-ascii', 'non-print'),
 *                                           'version' => 'xmlrpc',
 *                                           'encoding' => 'utf-8'
 *                                         );
 *                   or
 *
 *                   $output_options = array('output_type' => 'php');
 */
function xu_rpc_http_concise($params) {
   $host = $uri = $port = $method = $args = $debug = null;
   $timeout = $user = $pass = $secure = $debug = null;

   extract($params);

   // default values
   if(!$port) {
       $port = 80;
   }
   if(!$uri) {
       $uri = '/';
   }
   if(!$output) {
       $output = array('version' => 'xmlrpc');
   }

   $response_buf = "";
   if ($host && $uri && $port) {
       $request_xml = xmlrpc_encode_request($method, $args, $output);
       $response_buf = xu_query_http_post($request_xml, $host, $uri, $port, $debug,
                                          $timeout, $user, $pass, $secure);

       $retval = find_and_decode_xml($response_buf, $debug);
   }
   return $retval;
}

/* call an xmlrpc method on a remote http server. legacy support. */
function xu_rpc_http($method, $args, $host, $uri="/", $port=80, $debug=false, 
                     $timeout=0, $user=false, $pass=false, $secure=false) {
	return xu_rpc_http_concise(
		array(
			'method'  => $method,
			'args'    => $args,
			'host'    => $host,
			'uri'     => $uri,
			'port'    => $port,
			'debug'   => $debug,
			'timeout' => $timeout,
			'user'    => $user,
			'pass'    => $pass,
			'secure'  => $secure
		));
}



function xu_is_fault($arg) {
   // xmlrpc extension finally supports this.
   return is_array($arg) ? xmlrpc_is_fault($arg) : false;
}

/* sets some http headers and prints xml */
function xu_server_send_http_response($xml) {
    header("Content-type: text/xml");
    header("Content-length: " . strlen($xml) );
    echo $xml;
}

function dbg($msg) {
    echo "<em>",$msg,"</em><br />"; flush();
}
function dbg1($msg, $debug_level) {
   if ($debug_level >= 1) {
      dbg($msg);
   }
}
function dbg2($msg, $debug_level) {
   if ($debug_level >= 2) {
      dbg($msg);
   }
}
?>