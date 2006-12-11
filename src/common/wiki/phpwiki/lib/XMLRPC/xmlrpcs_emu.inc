<?php

/*
xmlrpcs_emu.inc -- xmlrpcs.inc wrapper.

08/30/01 - last modified by Dan Libby <dan@libby.com>

This code provides API compatibility with Edd Dumbill's php xmlrpc
library (http://phpxmlrpc.sourceforge.net/) but uses the xmlrpc-epi 
engine for the actual xml processing.  It is intended to provide a 
smooth transition path for those who would like to be able to use either
implementation.

To use in your existing application, simply change:

include("xmlrpcs.inc");

to:

include("xmlrpcs_emu.inc");

Notes:
- This file requires that xmlrpc-epi C extension be installed.
  See http://xmlrpc-epi.sourceforge.net/

- xmlrpc_decode, xmlrpc_encode are present in both the xmlrpc-epi
  C extension and the usefulinc implementation, and conflict.
  They have been enhanced and renamed to val_to_php, php_to_val.
  
- the xmlrpc-epi engine uses different fault codes and strings than
  the xmlrpc.inc. Application fault codes will remain unchanged
  between implementations, but system codes will likely be
  different.  
  See http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php

- Certain methods are not implemented and will typically return
  a message saying so.

*/


// by Edd Dumbill (C) 1999,2000
// <edd@usefulinc.com>
// $Id: xmlrpcs_emu.inc,v 1.3 2005/01/21 14:07:50 rurban Exp $

// License is granted to use or modify this software ("XML-RPC for PHP")
// for commercial or non-commercial use provided the copyright of the author
// is preserved in any distributed or derivative work.

// THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESSED OR
// IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
// OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
// IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
// INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
// NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, 
// DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
// THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
// (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
// THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

// XML RPC Server class
// requires: transport.php, xmlrpc_emu.inc

// change path as necessary. or set before including this file.
if(!$xmlrpc_util_path) {
   $xmlrpc_util_path = "./utils/";
}

include_once("$xmlrpc_util_path/utils.php");
include_once("$xmlrpc_util_path/xmlrpc_emu.inc");

/*************************************************************
* The Introspection callback is called when an introspection *
* request is received by the xmlrpc server. It is a private  *
* function and should never be called directly.              *
*                                                            *
* It translates useful_inc style introspection data for all  *
* of the registered methods into xmlrpc-epi style xml, which *
* is then returned to the server, parsed, and possibly spit  *
* out as xmlrpc.                                             *
*************************************************************/
function _introspection_cb($userdata) {
   foreach($userdata as $name => $method) {
      if($incr++ > 0) break;
      $sigs_buf = "";
      $purpose = "";

      if(isset($method[docstring])) {
          $purpose = "<purpose>$method[docstring]</purpose>";
      }

      if(is_array($method[signature])) {
         $sigs_buf = "<signatures>";
         foreach($method[signature] as $sig) {
            $count = 0;
            $params = "";
            foreach($sig as $param) {
               $xml = "<value type='$param'/>\n";

               if($count++ == 0) {
                  $returns = $xml;
               }
               else {
                  $params .= $xml;
               }
            }

            $sigs_buf .= 
               "<signature><params>$params</params><returns>$returns</returns></signature>\n";
         }
         $sigs_buf .= "</signatures>";
      }

      $method_desc .= 
         "<methodDescription name='$name'>\n$purpose\n$sigs_buf\n</methodDescription>\n";
   }

   $xml = "<?xml version='1.0'?>\n\n<introspection version='1.0'>\n<methodList>$method_desc</methodList></introspection>\n";
   return $xml;
}

/********************************************************************
* xmlrpc_server class.  Wrappers around the xmlrpc-epi server APIs. *
********************************************************************/
class xmlrpc_server {
   var $dmap = array();
   var $xmlrpc_server;

   // constructor. creates server and optionally services request.
   function xmlrpc_server($dispMap, $serviceNow=1) {
      global $HTTP_RAW_POST_DATA;
      // dispMap is a despatch array of methods
      // mapped to function names and signatures
      // if a method
      // doesn't appear in the map then an unknown
      // method error is generated
      $this->dmap = $dispMap;

      // create server.
      // php has no destructor support currently, so we can't call xmlrpc_server_destroy()
      // at the end like we should. Fortunately, the C code takes care of this at the
      // end of the request.  Feels unclean though.
      $this->xmlrpc_server = xmlrpc_server_create();

      // register methods
      foreach($this->dmap as $name => $method) {
         xmlrpc_server_register_method($this->xmlrpc_server, $name, "xmlrpc_user_func_wrapper_cb");
      }

      // register a callback in case of introspection queries.
      xmlrpc_server_register_introspection_callback($this->xmlrpc_server, "_introspection_cb");

      // possibly go to work handling request
      if ($serviceNow) {
         $this->service();
      }
   }

   // private. not really useful anymore since this is all handled by the xmlrpc-epi stuff.
   function serializeDebug() {
      global $_xmlrpc_debuginfo;
      if ($_xmlrpc_debuginfo!="")
         return "<!-- DEBUG INFO:\n\n" .
         $_xmlrpc_debuginfo . "\n-->\n";
      else
         return "";
   }
   
   // public. service the xmlrpc request
   function service() {
      global $HTTP_RAW_POST_DATA;
      $data = $HTTP_RAW_POST_DATA;

      // call server
      $payload = xmlrpc_server_call_method($this->xmlrpc_server, $data, $this->dmap, 
                                           array('output_type' => "xml", 'version' => "xmlrpc"));
      Header("Content-type: text/xml\nContent-length: " . strlen($payload));
      echo $payload;
   }

   // private. no equivalent in C library (yet)
   function verifySignature($in, $sig) {
      return "verifySignature not supported";
   }

   // public. this used to be called by service(). 
   // it's no longer necessary, but we keep it around in case 
   // people are calling it directly from applications.
   function parseRequest($data="") {
      global $_xh, $HTTP_RAW_POST_DATA;

      global $xmlrpcerr, $xmlrpcstr, $xmlrpcerrxml, $xmlrpc_defencoding;

      if ($data == "") {
         $data = $HTTP_RAW_POST_DATA;
      }

      // call server
      $php_val = xmlrpc_server_call_method($this->xmlrpc_server, $data, $this->dmap, 
                                           array('output_type' => "php", 'version' => "xmlrpc"));

      /* check for fault */
      if (is_array($php_val) && isset($php_val['faultCode'])) {
         $fc = $php_val['faultCode'];
         $fs = $php_val['faultString'];
      } else {
         $rpc_val = php_to_val($php_val);
      }
      $response = new xmlrpcresp($rpc_val, $fc, $fs);

      return $response;
   }

   // public.  test routine.
   function echoInput() {
      global $HTTP_RAW_POST_DATA;

      // a debugging routine: just echos back the input
      // packet as a string value

      $r = new xmlrpcresp;
      $r->xv = new xmlrpcval( "'Aha said I: '" . $HTTP_RAW_POST_DATA, "string");
      print $r->serialize();
   }
}


/**********************************************************************
*  This is the callback function that is called by C engine for *all* *
*  php methods. This then calls the user callback funcs which require *
*  xmlrpcmsg for input and return xmlrpcresp.                         *
*                                                                     *
*  This function converts between native php types which are          *
*  sent/expected by C engine, and the objects which are used          *
*  by user funcs.  kind of ugly/slow, but necessary.                  *
**********************************************************************/
function xmlrpc_user_func_wrapper_cb($methodname, $params, $method_map) {
   global $xmlrpcerr;
   $user_func = $method_map[$methodname]['function'];

   if ($user_func) {
      // create msg from methodname and params.
      $msg = new xmlrpcmsg($methodname);
      foreach($params as $param) {
         $msg->addParam(php_to_val($param));
      }
      
      // call user func.
      $rpc_resp = $user_func($msg);

      // translate user func response into php values.
      if (is_object($rpc_resp)) {
         if ($rpc_resp->faultCode()) {
            return xu_fault_code($rpc_resp->faultCode(), $rpc_resp->faultString());
         }
         return val_to_php($rpc_resp->value());
      } else {
         return xu_fault_code( $xmlrpcerr["invalid_return"], $xmlrpcstr["invalid_return"]);
      }
   }
   return xu_fault_code( $xmlrpcerr["unknown_method"], $xmlrpcstr["unknown_method"]);
}

$_xmlrpc_debuginfo='';
function xmlrpc_debugmsg($m)
{
    global $_xmlrpc_debuginfo;
    $_xmlrpc_debuginfo=$_xmlrpc_debuginfo . $m . "\n";
} 
?>