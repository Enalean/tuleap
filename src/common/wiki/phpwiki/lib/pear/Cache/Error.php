<?php
// +----------------------------------------------------------------------+
// | PEAR :: Cache                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Ulf Wendel <ulf.wendel@phpdoc.de>                           |
// +----------------------------------------------------------------------+
//
// $Id: Error.php,v 1.3 2004/06/21 08:39:38 rurban Exp $

require_once 'PEAR.php';

/**
* Cache Error class
* 
* @package Cache
*/
class Cache_Error extends PEAR_Error {


  /**
  * Prefix of all error messages.
  * 
  * @var  string
  */
  var $error_message_prefix = 'Cache-Error: ';
  
  /**
  * Creates an cache error object.
  * 
  * @param  string  error message
  * @param  string  file where the error occured
  * @param  string  linenumber where the error occured
  */
  function Cache_Error($msg, $file = __FILE__, $line = __LINE__) {
    
    $this->PEAR_Error(sprintf("%s [%s on line %d].", $msg, $file, $line));
    
  } // end func Cache_Error
  
} // end class Cache_Error
?>