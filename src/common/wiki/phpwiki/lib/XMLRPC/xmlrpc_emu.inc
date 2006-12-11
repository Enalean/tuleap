<?php

/*
xmlrpc_emu.inc -- xmlrpc.inc wrapper.

08/30/01 - last modified by Dan Libby <dan@libby.com>

This code provides API compatibility with Edd Dumbill's php xmlrpc
library (http://phpxmlrpc.sourceforge.net/) but uses the xmlrpc-epi 
engine for the actual xml processing.  It is intended to provide a 
smooth transition path for those who would like to be able to use either
implementation.

To use in your existing application, simply change:

include("xmlrpc.inc");

to:

include("xmlrpc_emu.inc");

Notes:
- This file requires that xmlrpc-epi C extension be installed.
  See http://xmlrpc-epi.sourceforge.net/

- xmlrpc_decode, xmlrpc_encode are present in both the xmlrpc-epi
  C extension and the usefulinc implementation, and conflict.
  They have been enhanced and renamed to val_to_php, php_to_val.

- Certain methods are not implemented and will typically return
  a message saying so.

*/

// by Edd Dumbill (C) 1999-2001
// <edd@usefulinc.com>
//

// $Id: xmlrpc_emu.inc,v 1.1 2004/12/10 00:12:14 rurban Exp $


// Copyright (c) 1999,2000,2001 Edd Dumbill.
// All rights reserved.
//
// Redistribution and use in source and binary forms, with or without
// modification, are permitted provided that the following conditions
// are met:
//
//    * Redistributions of source code must retain the above copyright
//      notice, this list of conditions and the following disclaimer.
//
//    * Redistributions in binary form must reproduce the above
//      copyright notice, this list of conditions and the following
//      disclaimer in the documentation and/or other materials provided
//      with the distribution.
//
//    * Neither the name of the "PHP for XML-RPC" nor the names of its
//      contributors may be used to endorse or promote products derived
//      from this software without specific prior written permission.
//
// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
// "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
// LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
// FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
// REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
// INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
// (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
// SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
// HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
// STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
// ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
// OF THE POSSIBILITY OF SUCH DAMAGE.

// Requires: transport.php

// change path as necessary. or set before including this file.
if(!$xmlrpc_util_path) {
   $xmlrpc_util_path = "./utils/";
}
include_once("$xmlrpc_util_path/utils.php");

// xmlrpc types.  seems like these should be defines instead of globals.
$xmlrpcI4="i4";
$xmlrpcInt="int";
$xmlrpcBoolean="boolean";
$xmlrpcDouble="double";
$xmlrpcString="string";
$xmlrpcDateTime="dateTime.iso8601";
$xmlrpcBase64="base64";
$xmlrpcArray="array";
$xmlrpcStruct="struct";

// map local types to xmlrpc-epi-php types.
$epiTypeMap = array($xmlrpcI4 => "int",
                    $xmlrpcInt => "int",
                    $xmlrpcBoolean => "boolean",
                    $xmlrpcString => "string",
                    $xmlrpcDouble => "double",
                    $xmlrpcDateTime => "datetime",
                    $xmlrpcBase64 => "base64",
                    $xmlrpcArray => "array",
                    $xmlrpcStruct => "struct");

// map local types to php types
$phpTypeMap = array($xmlrpcI4 => "integer",
                    $xmlrpcInt => "integer",
                    $xmlrpcBoolean => "boolean",
                    $xmlrpcString => "string",
                    $xmlrpcDouble => "double",
                    $xmlrpcDateTime => "string",
                    $xmlrpcBase64 => "string",
                    $xmlrpcArray => "array",
                    $xmlrpcStruct => "array");

$xmlrpcTypes=array($xmlrpcI4 => 1,
                   $xmlrpcInt => 1,
                   $xmlrpcBoolean => 1,
                   $xmlrpcString => 1,
                   $xmlrpcDouble => 1,
                   $xmlrpcDateTime => 1,
                   $xmlrpcBase64 => 1,
                   $xmlrpcArray => 2,
                   $xmlrpcStruct => 3);

// some error definitions
$xmlrpcerr["unknown_method"]=1;
$xmlrpcstr["unknown_method"]="Unknown method";
$xmlrpcerr["invalid_return"]=2;
$xmlrpcstr["invalid_return"]="Invalid return payload: enabling debugging to examine incoming payload";
$xmlrpcerr["incorrect_params"]=3;
$xmlrpcstr["incorrect_params"]="Incorrect parameters passed to method";
$xmlrpcerr["introspect_unknown"]=4;
$xmlrpcstr["introspect_unknown"]="Can't introspect: method unknown";
$xmlrpcerr["http_error"]=5;
$xmlrpcstr["http_error"]="Didn't receive 200 OK from remote server.";
$xmlrpcerr["no_data"]=6;
$xmlrpcstr["no_data"]="No data received from server.";
$xmlrpcerr["no_ssl"]=7;
$xmlrpcstr["no_ssl"]="No SSL support compiled in.";
$xmlrpcerr["curl_fail"]=8;
$xmlrpcstr["curl_fail"]="CURL error";

$xmlrpc_defencoding="UTF-8";

$xmlrpcName="XML-RPC for PHP (xmlrpc-epi wrapper version)";
$xmlrpcVersion="1.0";

// let user errors start at 800
$xmlrpcerruser=800; 


/************************************************
* xmlrpc client class.  basically a wrapper for *
* xu_rpc_http().                                *
************************************************/
class xmlrpc_client {
   var $path;
   var $server;
   var $port;
   var $errno;
   var $errstring;
   var $debug=0;
   var $username="";
   var $password="";

   // constructor
   function xmlrpc_client($path, $server, $port=80) {
      $this->port=$port; $this->server=$server; $this->path=$path;
   }

   // public. for debugging info.
   function setDebug($in) {
      if ($in) {
         $this->debug=1;
      } else {
         $this->debug=0;
      }
   }

   // public. for http authentication.
   function setCredentials($u, $p) {
      $this->username=$u;
      $this->password=$p;
   }

   // public.  sent request to server.
   function send($msg, $timeout=0, $pause=0, $secure=false) {
      // where msg is an xmlrpcmsg
      $msg->debug=$this->debug;


      if ($pause) {
         sleep($pause);
      }

      return $this->sendPayloadHTTP10($msg, $this->server, $this->port, $timeout, $this->username, $this->password);

   }

   // private. performs http post request
   function sendPayloadHTTP10($msg, $server, $port, $timeout=0, $username="", $password="", $secure=false) {
      // Only create the payload if it was not created previously
      if (empty($msg->payload)) {
         $msg->createPayload();
      }

      $response_buf = xu_query_http_post($msg->payload, $server, $this->path, $port, 
                                         $this->debug, $timeout, $username, $password, $secure);

      $resp=$msg->parseResponse($response_buf);

      return $resp;
   }

} // end class xmlrpc_client

/******************************************
* a class to represent an xmlrpc response *
******************************************/
class xmlrpcresp {
   var $xv;
   var $fn;
   var $fs;
   var $hdrs;

   // constructor.
   function xmlrpcresp($val, $fcode=0, $fstr="") {
      if ($fcode!=0) {
         $this->fn=$fcode;
         $this->fs=$fstr;
      } else {
         $this->xv=$val;
      }
   }

   // public. get methods
   function faultCode() { return $this->fn;}
   function faultString() { return $this->fs;}
   function value() { return $this->xv;}

   // public. serialize self as xml string.
   function serialize() {
      /* check if fault */
      if ($this->fn) {
         $result = xmlrpc_encode_request(null, xu_fault_code($this->fn, $this->fs));
      } else {
         $php_vals = val_to_php($this->xv);

         // null for methodname indicates response type.
         $result = xmlrpc_encode_request(null, $php_vals);
      }

      return $result;
   }
}

/*****************************************
* a class to represent an xmlrpc message *
*****************************************/
class xmlrpcmsg {
   var $payload;
   var $methodname;
   var $params=array();
   var $debug=0;

   // constructor
   function xmlrpcmsg($meth, $pars=0) {
      $this->methodname=$meth;
      if (is_array($pars) && sizeof($pars)>0) {
         for ($i=0; $i<sizeof($pars); $i++)
            $this->addParam($pars[$i]);
      }
   }

   // unused. xmlrpc-epi does this automagically
   function xml_header() {
      return "xml_header not supported";
   }

   // unused. not necessary
   function xml_footer() {
      return "xml_footer not supported";
   }

   // private. performs the actual message serialization.
   function createPayload() {
      $php_params_val = array();
      foreach($this->params as $param) {
         $php_params_val[] = val_to_php($param);
      }
      $this->payload = xmlrpc_encode_request($this->methodname, $php_params_val);
   }

   // public. returns name of method
   function method($meth="") {
      if ($meth!="") {
         $this->methodname=$meth;
      }
      return $this->methodname;
   }

   // public. serialization message as xml
   function serialize() {
      $this->createPayload();
      return $this->payload;
   }

   // public. add/retrieve/count message params
   function addParam($par) { $this->params[]=$par;}
   function getParam($i) { return $this->params[$i];}
   function getNumParams() { return sizeof($this->params);}

   // public. in case we are given a file handle
   function parseResponseFile($fp) {
      $ipd="";

      while ($data=fread($fp, 32768)) {
         $ipd.=$data;
      }
      return $this->parseResponse($ipd);
   }

   // public. parse xml, return as xmlrpcresp.
   function parseResponse($data="") {
      $php_val = xmlrpc_decode($data);

      /* check for fault */
      if (is_array($php_val) && isset($php_val[0][faultCode])) {
         $fc = $php_val[0][faultCode];
         $fs = $php_val[0][faultString];
      } else {
         $rpc_val = php_to_val($php_val);
      }


      return new xmlrpcresp($rpc_val, $fc, $fs);
   }

}

/***************************************
* a class to represent an xmlrpc value *
***************************************/
class xmlrpcval {
   var $me=array();
   var $mytype=0;

   // constructor
   function xmlrpcval($val=-1, $type="") {
      global $xmlrpcTypes;
      $this->me=array();
      $this->mytype=0;
      if ($val!=-1 || $type!="") {
         if ($type=="") $type="string";
         if ($xmlrpcTypes[$type]==1) {
            $this->addScalar($val,$type);
         } else if ($xmlrpcTypes[$type]==2)
            $this->addArray($val);
         else if ($xmlrpcTypes[$type]==3)
            $this->addStruct($val);
      }
   }

   // public. add a php scalar value.
   function addScalar($val, $type="string") {
      global $xmlrpcTypes, $xmlrpcBoolean;

      if ($this->mytype==1) {
         echo "<B>xmlrpcval</B>: scalar can have only one value<BR>";
         return 0;
      }
      $typeof=$xmlrpcTypes[$type];
      if ($typeof!=1) {
         echo "<B>xmlrpcval</B>: not a scalar type (${typeof})<BR>";
         return 0;
      }

      if ($type==$xmlrpcBoolean) {
         if (strcasecmp($val,"true")==0 || $val==1 || $val==true) {
            $val=1;
         } else {
            $val=0;
         }
      }

      if ($this->mytype==2) {
         // we're adding to an array here
         $ar=$this->me["array"];
         $ar[]=new xmlrpcval($val, $type);
         $this->me["array"]=$ar;
      } else {
         // a scalar, so set the value and remember we're scalar
         $this->me[$type]=$val;
         $this->mytype=$typeof;
      }
      return 1;
   }

   // public. add a php array
   function addArray($vals) {
      global $xmlrpcTypes;
      if ($this->mytype!=0) {
         echo "<B>xmlrpcval</B>: already initialized as a [" . 
         $this->kindOf() . "]<BR>";
         return 0;
      }
      $this->mytype=$xmlrpcTypes["array"];
      $this->me["array"]=$vals;
      return 1;
   }

   // public. add a php keyed array as a struct.
   function addStruct($vals) {
      global $xmlrpcTypes;
      if ($this->mytype!=0) {
         echo "<B>xmlrpcval</B>: already initialized as a [" . 
         $this->kindOf() . "]<BR>";
         return 0;
      }
      $this->mytype=$xmlrpcTypes["struct"];
      $this->me["struct"]=$vals;
      return 1;
   }

   // public. write myself out as html.
   function dump($ar) {
      foreach($ar as $key => $val) {
         echo "$key => $val<br>";
         if ($key == 'array') {
            foreach($val as $key2 => $val2) {
               echo "-- $key2 => $val2";
            }
         }
      }
   }

   // public. kind of value. 
   // (not 1 to 1 mapping with xmlrpc types or php types)
   function kindOf() {
      switch ($this->mytype) {
      case 3:
         return "struct";
         break;
      case 2:
         return "array";
         break;
      case 1:
         return "scalar";
         break;
      default:
         return "undef";
      }
   }

   // unused.
   function serializedata($typ, $val) {
      return "serializedata not supported";
   }

   // public. serialize self as xml.
   function serialize() {
      return $this->serializeval($this);
   }

   // public. serialize any xmlrpcval object as xml.
   function serializeval($o) {
      $php_val = val_to_php($o);
      $result_xml = xmlrpc_encode($php_val);

      return $result_xml;
   }

   // public.  get struct members.
   function structmem($m) {
      $nv=$this->me["struct"][$m];
      return $nv;
   }

   // public. reset struct to first item.
   function structreset() {
      reset($this->me["struct"]);
   }

   // public. get key/val pair of next struct item.
   function structeach() {
      return each($this->me["struct"]);
   }

   // public. get php type scalar value.
   function scalarval() {
      global $xmlrpcBoolean, $xmlrpcBase64;
      reset($this->me);
      list($a,$b)=each($this->me);
      return $b;
   }

   // public. get xmlrpc type of value.
   function scalartyp() {
      global $xmlrpcI4, $xmlrpcInt;
      reset($this->me);
      list($a,$b)=each($this->me);
      if ($a==$xmlrpcI4)
         $a=$xmlrpcInt;
      return $a;
   }

   // public. get array member.
   function arraymem($m) {
      $nv=$this->me["array"][$m];
      return $nv;
   }

   // public. get array size
   function arraysize() {
      reset($this->me);
      list($a,$b)=each($this->me);
      return sizeof($b);
   }
}

// date helpers

/*****************************************************************
* These stubs need to be implemented in the C extension library. *
* When they are, these calls will be commented out. -danda       *
*****************************************************************/

function iso8601_encode($timet, $utc=0) {
   // return an ISO8601 encoded string
   // really, timezones ought to be supported
   // but the XML-RPC spec says:
   //
   // "Don't assume a timezone. It should be specified by the server in its
   // documentation what assumptions it makes about timezones."
   // 
   // these routines always assume localtime unless 
   // $utc is set to 1, in which case UTC is assumed
   // and an adjustment for locale is made when encoding
   if (!$utc) {
      $t=strftime("%Y%m%dT%H:%M:%S", $timet);
   } else {
      if (function_exists("gmstrftime"))
         // gmstrftime doesn't exist in some versions
         // of PHP
         $t=gmstrftime("%Y%m%dT%H:%M:%S", $timet);
      else {
         $t=strftime("%Y%m%dT%H:%M:%S", $timet-date("Z"));
      }
   }
   return $t;
}

function iso8601_decode($idate, $utc=0) {
   // return a timet in the localtime, or UTC
   $t=0;
   if (ereg("([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})",
            $idate, $regs)) {
      if ($utc) {
         $t=gmmktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
      } else {
         $t=mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
      }
   }
   return $t;
}

/****************************************************************
* xmlrpc_decode takes a message in PHP xmlrpc object format and *
* tranlates it into native PHP types.                           *
****************************************************************/
function val_to_php($xmlrpc_val) {
   global $epiTypeMap;
   global $phpTypeMap;

   $kind = $xmlrpc_val->kindOf();

   if ($kind == "scalar") {
      $type = $xmlrpc_val->scalartyp();
      $php_val = $xmlrpc_val->scalarval();
      $php_type = $phpTypeMap[$type];

      // value is stored in object as a string. we want
      // its native type.
      settype($php_val, $php_type);

      // magic to let xmlprc-epi engine know about base64 and datetime types.
      $epi_type = $epiTypeMap[$type];
      if($epi_type) {
         xmlrpc_set_type(&$php_val, $epi_type);
         // php_val may now be an object, if epi_type = base64 or datetime.
      }
      
      return $php_val;
   }
   // generate php indexed array. recurse for sub-values.
   else if ($kind == "array") {
      $size = $xmlrpc_val->arraysize();
      $arr = array();

      for ($i = 0; $i < $size; $i++) {
         $arr[] = val_to_php($xmlrpc_val->arraymem($i));
      }
      return $arr; 
   } 
   // generate php keyed array. recurse for sub-values.
   else if ($kind == "struct") {
      $xmlrpc_val->structreset();
      $arr = array();

      while (list($key,$value)=$xmlrpc_val->structeach()) {
         $arr[$key] = val_to_php($value);
      }
      return $arr;
   }
}

/****************************************************************
* php_to_val takes native php types and encodes them into       *
* xmlrpc PHP object format.                                     *
****************************************************************/
function php_to_val($php_val) {
   global $xmlrpcInt;
   global $xmlrpcDouble;
   global $xmlrpcString;
   global $xmlrpcArray;
   global $xmlrpcStruct;
   global $xmlrpcBoolean;
   global $xmlrpcDateTime;
   global $xmlrpcBase64;

   // get the xmlrpc type of value.
   $type = xmlrpc_get_type($php_val);
   $xmlrpc_val = new xmlrpcval;

   switch ($type) {
   case "array":
   case "vector":         //unused
      $arr = array();
      foreach($php_val as $v) {
         $arr[] = php_to_val($v);
      }
      $xmlrpc_val->addArray($arr);
      break;
   case "object":         //unused
   case "struct":
      foreach($php_val as $k => $v) {
         $arr[$k] = php_to_val($v);
      }
      $xmlrpc_val->addStruct($arr);
      break;
   case "integer":        //unused
   case "int":
      $xmlrpc_val->addScalar($php_val, $xmlrpcInt);
      break;
   case "boolean":
      $xmlrpc_val->addScalar($php_val, $xmlprcBoolean);
      break;
   case "double":
      $xmlrpc_val->addScalar($php_val, $xmlrpcDouble);
      break;
   case "string":
      $xmlrpc_val->addScalar($php_val, $xmlrpcString);
      break;
   case "datetime":
      $xmlrpc_val->addScalar($php_val->scalar, $xmlrpcDateTime);
      break;
   case "base64":
      $xmlrpc_val->addScalar($php_val->scalar, $xmlrpcBase64);
      break;
   case "unknown type":
   default:
      $xmlrpc_val = false;
      break;
   }
   return $xmlrpc_val;
}

?>