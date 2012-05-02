<?php
// +----------------------------------------------------------------------+
// | Near implementation of the java.util.Properties API for PHP 5        |
// | Copyright (C) 2005 Craig Manley                                      |
// +----------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU Lesser General Public License as       |
// | published by the Free Software Foundation; either version 2.1 of the |
// | License, or (at your option) any later version.                      |
// |                                                                      |
// | This library is distributed in the hope that it will be useful, but  |
// | WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU     |
// | Lesser General Public License for more details.                      |
// |                                                                      |
// | You should have received a copy of the GNU Lesser General Public     |
// | License along with this library; if not, write to the Free Software  |
// | Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307  |
// | USA                                                                  |
// |                                                                      |
// | LGPL license URL: http://opensource.org/licenses/lgpl-license.php    |
// +----------------------------------------------------------------------+
// | References:                                                          |
// | 1. The Perl (CPAN) module Config::Properties 0.58, originally        |
// | developed by Randy Jay Yarger, later mantained by me, and currently  |
// | mantained by Salvador Fandiño.                                       |
// | See: http://search.cpan.org/search?query=Config%3A%3AProperties      |
// | 2. The Java docs for the java.util.Properties API which can be found |
// | at http://java.sun.com/j2se/1.3/docs/api/index.html .                |
// +----------------------------------------------------------------------+
// | Author: Craig Manley                                                 |
// +----------------------------------------------------------------------+
//
// $Id: Properties.php,v 1.1 2005/11/27 16:39:23 cmanley Exp $


/**
 * @file 
 * Contains the Properties class and it's private classes.
 */

/** 
 * @mainpage
 * @author	Craig Manley
 * @since	PHP 5.0
 * @date	$Date: 2005/11/27 16:39:23 $
 * @version	$Revision: 1.1 $
 *
 * This is a near implementation of the java.util.Properties API for PHP 5.
 * The Properties class has an interface that is very similar to it's Java counterpart.
 * One notable difference however, is that this class is capable of retaining the
 * original structure, including comments and blanks, of a loaded properties file when 
 * saving the properties to a new file, which is something that it's Java counterpart doesn't do.
 *
 * @section motivation MOTIVATION:
 * Since I develop (web) applications that sometimes consist of code written in different  
 * programming languages, I needed a standard configuration file format and API's in each
 * of the programming languages to parse shared configuration files.
 *
 * Java has a java.util.Properties class which uses a standard configuration file format
 * that looks a lot like most .conf files in UNIX (of which there is no real standard format). 
 *
 * Perl has the lean and mean Config::Properties class which I often use to parse
 * (and sometimes write) Java properties files.
 *
 * PHP however didn't have something similar.
 *
 * @section file_format FILE FORMAT:
 * The format of a Java-style property file is that of a key-value pair seperated
 * by either whitespace, the colon (:) character, or the equals (=) character.
 * Whitespace before the key and on either side of the seperator is ignored.
 *
 * Lines that begin with either a hash (#) or a bang (!) are considered comment
 * lines and ignored.
 *
 * A backslash (\) at the end of a line signifies a continuation and the next
 * line is counted as part of the current line (minus the backslash, any whitespace
 * after the backslash, the line break, and any whitespace at the beginning of the next line).
 *
 * The official references used to determine this format can be found in the Java API docs
 * for java.util.Properties at http://java.sun.com/j2se/1.3/docs/api/java/util/Properties.html .
 *
 * @section synopsis SYNOPSIS:
 *
 * @code
 * my $p = new Properties();
 * 
 * // Read from string (from array and file handle is possible too):
 * $p->load(file_get_contents('site.properties');
 * 
 * // Get associative array of all loaded properties:
 * $config = $p->toArray();
 *
 * // Get a single property's value:
 * $email_from = $p->getProperty('email.from');
 * 
 * // Set a property:
 * $p->setProperty('email.from', 'no-reply@server.com');
 *
 * // Remove a property:
 * $p->remove('email.from');
 *
 * // Save properties method 1:
 * $p->store('site.properties');
 *
 * // Save properties method 2:
 * file_put_contents('site.properties', $p->toString());
 *
 * // Save properties method 3 (includes comments and blanks):
 * file_put_contents('site.properties', $p->toString(true));
 * @endcode
 *
 * Copyright © 2005, Craig Manley.
 * 
 */ 




/**
 * @ignore Require the section classes.
 */
//require_once(realpath(dirname(__FILE__) . '/Section/Blank.php'));
//require_once(realpath(dirname(__FILE__) . '/Section/Comment.php'));
//require_once(realpath(dirname(__FILE__) . '/Section/Property.php'));




/**
 * @ignore Create "Standard PHP Library" compatible exception classes if the SPL extension isn't available.
 * @ignore The SPL extension exists in PHP 5.0, but is only installed by default since PHP 5.1.
 */
//if (!extension_loaded('SPL')) { 
  if (!class_exists('LogicException')) {
    eval('class LogicException extends Exception {};');
  }
  if (!class_exists('InvalidArgumentException')) {
    eval('class InvalidArgumentException extends LogicException {};');
  }
//}







/**
 * Near implementation of the java.util.Properties class.
 * This is the class that you need to use for parsing and writing Java style property files.
 * It makes use of exception classes from the "Standard PHP Library" extension (http://www.php.net/spl)
 * that is installed per default since PHP 5.1, however it is not required to use this class.
 */
class Properties {
  
  protected $defaults   = null;    // Properties object to use for defaults.
  private   $sections   = array(); // array of Properties_Section objects loaded from a properties file with the load method.
  private   $properties = array(); // associative array of key => Properties_Section_Property object references.
  const WHITE_SPACE_CHARS = " \t\r\n\x0C"; // 0x0c == \f (form feed)

  /**
   * Constructor. Creates an empty property list.
   *
   * @param defaults optional Properties object to use for defaults.
   * @throw InvalidArgumentException
   */
  //public function __construct(Properties $defaults = null) { // Type hints with null as default only work in PHP5.1 up.
  public function __construct($defaults = null) { // This is for PHP 5.0 up.
    if (isset($defaults)) {
      if (!($defaults instanceof Properties)) {
    	throw new InvalidArgumentException('The $defaults parameter must be null or an instance of ' . __CLASS__ . '.');
      }
      $this->defaults = $defaults;
    }
  }


  /**
   * Tests an unescaped key's syntax. Throws an exception on error.
   *
   * @param key   
   * @throw InvalidArgumentException if key syntax is invalid.
   */
  protected static function _testKey($key) {
    if (!(isset($key) && strlen($key))) {
      throw new InvalidArgumentException('The $key parameter must be a string of 1 or more characters.');
    }
  }


  /**
   * Sets a property.
   *
   * @param key
   * @param value
   * @throw InvalidArgumentException
   */
  public function setProperty($key, $value) {
    Properties_Section_Property::testKey($key);
    Properties_Section_Property::testValue($key);
    if (array_key_exists($key, $this->properties)) {
      $this->properties[$key]->setValue($value);
    }
    else {
      $p = new Properties_Section_Property($key, $value);
      array_push($this->sections, $p);
      $this->properties[$key] = $p;
    }
  }


  /**
   * Does what Perl's chomp() function does.
   *
   * @param string reference
   * @return integer
   */
  private static function _chomp(&$string) {
    $result = 0;
    if (is_array($string)) {
      foreach($string as $i => $val) {
        $result += self::_chomp($string[$i]);
      }
    }
    else {
      while (strlen($string)) {
        $endchar = substr($string, -1);
        if (($endchar === "\n") || ($endchar === "\r")) {
          $string = substr($string, 0, -1);
          $result++;
        }
        else {
          break;
        }
      }
      // // alternative:
      // if (preg_match('/([\\r|\\n]+)$/', $string, $matches)) {
      //   $result = strlen($matches[1]);
      //   $string = substr($string, 0, -$result);
      // }
    }
    return $result;
  }


  /**
   * Determines if the given line ends with an unescaped continuation character (the '\').
   * If the result is true, then the continuation character is also stripped off the end of the line too.
   *
   * @param line string reference
   * @return boolean
   */
  private static function _continueLine(&$line) {
    $count = 0;
    $len = strlen($line);
    for ($i = $len - 1; $i >= 0; $i--) {
      if (substr($line, $i, 1) == '\\') {
      	$count++;
      }
      else {
        break;
      }
    }
    $result = (boolean) $count % 2;
    if ($result) {
      $line = substr($line, 0, $len - 1);
    }
    return $result;
  }


  /**
   * Loads properties from a file handle or string.
   * You can pass either an file handle open for reading,
   * an array of lines, or a string buffer containing all the lines.
   * When a file handle is passed, the caller is responsible for opening and closing it.
   *
   * @param source - an open file handle, string buffer, or array of lines.
   * @throw InvalidArgumentException
   */
  public function load($source) {
    if (!isset($source)) {
      throw new InvalidArgumentException('The $source parameter may not be null.');
    }
    $lines = null;
    if (is_array($source)) {
      $lines = $source;
    }
    elseif (is_string($source)) {
      $lines = preg_split('/(\\r\\n|\\n|\\r)/', $source); // DOS: \r\n	UNIX: \n	MAC: \r
    }
    elseif (get_resource_type($source) === 'file') {
      $contents = '';
      while (!feof($source)) {
        $contents .= fread($source, 8192);
      }
      $lines = preg_split('/(\\r\\n|\\n|\\r)/', $source); // DOS: \r\n	UNIX: \n	MAC: \r
    }
    else {
      throw new InvalidArgumentException('The $source parameter of type "' . gettype($source) . '" is not supported.');
    }

    // Now process the lines
    for ($i = 0; $i < count($lines); $i++) {      
      $line = ltrim($lines[$i], self::WHITE_SPACE_CHARS);
      self::_chomp($line);

      // handle blanks
      if (strlen($line) == 0) {
        if ($i < count($lines) -1) { // don't store last blank.
          $p = new Properties_Section_Blank();
          array_push($this->sections, $p);
        }
      }

      // handle comments
      elseif (preg_match('/^(#|!)(\\s*)(.*)$/', $line, $matches)) {
        $p = new Properties_Section_Comment($matches[3], $matches[1], $matches[2]);
        array_push($this->sections, $p);
      }

      // handle properties or multiline sections.
      else {
      	// handle continuation lines
      	while (self::_continueLine($line) && (++$i < count($lines))) {
      	  self::_chomp($lines[$i]);
          $line .= ltrim($lines[$i], self::WHITE_SPACE_CHARS);
        }

        // handle blanks (in case there were multiple lines with only a continuation character on them).
        if (strlen($line) == 0) {
          if ($i < count($lines) -1) { // don't store last blank.
            $p = new Properties_Section_Blank();
            array_push($this->sections, $p);
          }
        }

        // handle comments (in case there were multiple lines with only a continuation character on them followed by a comment).
        elseif (preg_match('/^(#|!)(\\s*)(.*)$/', $line, $matches)) {
          $p = new Properties_Section_Comment($matches[3], $matches[1], $matches[2]);
          array_push($this->sections, $p);
        }

        // handle properties
        else {
          $p = new Properties_Section_Property($line);
          $key = $p->getKey();
          if (array_key_exists($key, $this->properties)) {
            // Property name already exists.
            array_push($this->sections, new Properties_Section_Comment("WARNING: The following previously encountered property was commented out:\n$line"));
          }
          else {
            $this->properties[$key] = $p;
            array_push($this->sections, $p);
          }
        }
      }
    }
  }


  /**
   * Writes the property list (key and element pairs) to the given file name or handle.
   * If you want to retain the original structure of the file(s) you loaded as much as possible,
   * then use toString(true) method instead.
   *
   * @param file file name or file handle opened in write or append mode.
   * @param header optional header line which will be written as a comment.
   * @return array
   * @throw InvalidArgumentException
   */
  public function store($file, $header = null) {
    $data = '';
    // header comment (supports multiline headers too unlike the java method).
    if (isset($header) && strlen($header)) {
      $comment = new Properties_Section_Comment($header, false);
      $data .= $comment->toString();
    }
    // date comment, e.g. #Sat Nov 26 16:29:36 CET 2005    
    $data .= '#' . date('r') . "\n";
    $data .= $this->toString();
    // write
    if (get_resource_type($file) === 'file') {
      fwrite($file, $data);
    }
    else {
      file_put_contents($file, $data);
    }
  }


  /**
   * Gets a property value.
   * Returns a string (possibly empty), or null if not found.
   *
   * @param key
   * @param defaultValue optional default value to return if no match is found.
   * @return string or null.
   * @throw InvalidArgumentException
   */
  public function getProperty($key, $defaultValue = null) {
    self::_testKey($key);
    if (array_key_exists($key, $this->properties)) {
      return $this->properties[$key]->getValue();
    }
    if (isset($this->defaults)) {
      return $this->defaults->getProperty($key, $defaultValue);
    }
    return $defaultValue;
  }


  /**
   * Returns an array of all property names.
   *
   * @return array
   */
  public function propertyNames() {
    return array_keys($this->properties);
  }
  
  
  /**
   * Returns all the key -> value pairs as an associative array.
   *
   * @return array
   */
  public function toArray() {
    $result = array();
    foreach ($this->properties as $key => $section) {
      $result[$key] = $section->getValue();
    }
    return $result;
  }
  
  
  /**
   * Returns a string representation of the properties, without all the blanks and comments.
   *
   * @param everything optional boolean, default false. If true then comments and blanks will be included.
   * @return string
   */
  public function toString($everything = false) {
    $result = '';
    if ($everything) {
      foreach ($this->sections as $section) {
        $result .= $section->toString();
      }
    }
    else {
      foreach (array_values($this->properties) as $section) {
        $result .= $section->toString();
      }    
    }
    return $result;
  }
  
  
  /**
   * Determines if this object contains the exact same property name and value pairs as the given object.
   * Part of the java.util.Hashtable API.
   *
   * @param other Properties object.
   * @return boolean
   */
  public function equals(Properties $other) {
    $other_names = $other->propertyNames();    
    $my_names = $this->propertyNames();
    if ((count($other_names) != count($my_names)) || count(array_diff($other_names, $my_names))) {
      return false;
    }   
    for ($i = 0; $i < count($my_names); $i++) {      
      if ($this->getProperty($my_names[$i]) !== $other->getProperty($my_names[$i])) {
        return false;
      }
    }
    return true;
  }


  /**
   * Removes all properties.
   * Part of the java.util.Hashtable API.
   */
  public function clear() {
    $this->properties = array();
    $this->sections = array();
  }


  /**
   * Deletes a property.
   * Part of the java.util.Hashtable API.
   * Returns the property's last known value as a string (possibly empty), or null if not found.
   *
   * @param key
   * @return string or null
   */
  public function remove($key) {
    self::_testKey($key);
    $result = null;
    if (array_key_exists($key, $this->properties)) {
      // Remove the section and any blank or comment lines directly before it.
      $section_removed = false;
      for ($i = 0; $i < count($this->sections); $i++) {
        if (($this->sections[$i] instanceof Properties_Section_Property) && ($this->sections[$i]->getKey() == $key)) {
          $splice_from = $splice_to = $i;
          if ($i > 0) {
            $previous_section_class = get_class($this->sections[$i - 1]);
            if ($previous_section_class != 'Properties_Section_Property') {
              for ($j = $i - 1; $j >= 0; $j--) {
                if (get_class($this->sections[$j]) == $previous_section_class) {
                  $splice_from = $j;
                }
                else {
                  break;
                }
              }
            }
          }
          array_splice($this->sections, $splice_from, 1 + $splice_to - $splice_from);
          $section_removed = true;
          break;
        }
      }
      if (!$section_removed) {
      	throw new LogicException("Cannot find (and therefore remove) the section with key '$key'.");
      }
      // remove the property
      unset($this->properties[$key]);
    }
    return $result;
  }

}
/****************************** End of class Properties ******************************/







/**
 * Internal Section interface that you don't need to know about.
 */
interface Properties_ISection {

  /**
   * The implementation of this method must return the string representation of the section.
   *
   * @return string
   */
  public function toString();

}
/****************************** End of interface Properties_ISection ******************************/







/**
 * Internal Blank section class that you don't need to know about.
 * Lines that contain nothing or only whitespace are considered blanks.
 */
class Properties_Section_Blank implements Properties_ISection {

  /**
   * Returns the string representation of the section.
   *
   * @return string
   */
  public function toString() {
    return "\n";
  }

}
/****************************** End of class Properties_Section_Blank ******************************/







/**
 * Internal Comment section class that you don't need to know about.
 *
 * A comment line is a line whose first non-whitespace character
 * is an ASCII # or ! is ignored (thus, # (hash) or ! (bang) indicate comment lines).
 */
class Properties_Section_Comment implements Properties_ISection {

  private $comment_char = null;
  private $padding      = null;
  private $value        = null;

  /**
   * Constructor.
   *
   * @param value        string
   * @param comment_char optional comment character, default '#' (allowed values are '#' or !').
   * @param padding      optional comment padding string, default ' '.
   * @throw InvalidArgumentException
   */
  public function __construct($value, $comment_char = '#', $padding = ' ') {
    $this->value = $value;
    $this->_testCommentChar($comment_char);
    $this->comment_char = $comment_char;
    $this->_testPadding($padding);
    $this->padding = $padding;
  }


  /**
   * Tests the syntax of a comment character.
   *
   * @param $char
   * @throw InvalidArgumentException
   */
  protected static function _testCommentChar($char) {
    if (!isset($char)) {
      throw new InvalidArgumentException('Comment character may not be null.');
    }
    if (($char != '#') && ($char != '!')) {
      throw new InvalidArgumentException('String "' . $char . '" is not a valid comment character.');
    }
  }


  /**
   * Tests the syntax of a padding string.
   *
   * @param string
   * @throw InvalidArgumentException
   */
  protected static function _testPadding($string) {
    if (!isset($string)) {
      throw new InvalidArgumentException("Padding may not be NULL!");
    }
    if (!preg_match('/^( |\\t|#|!)*$/', $string)) {
      throw new InvalidArgumentException('Padding string contains invalid characters.');
    }
  }


  /**
   * Returns the string representation of the section.
   *
   * @return string
   */
  public function toString() {
    $lines = preg_split('/\\r?\\n\\r?/', $this->value);
    return $this->comment_char . $this->padding . implode("\n" . $this->comment_char . $this->padding, $lines) . "\n";
  }


  /**
   * Sets the value. This is the complete comment, but without the initial comment character and padding.
   *
   * @param value string
   */
  public function setValue($value) {
    $this->value = $value;
  }


  /**
   * Returns the value. This is the complete comment, but without the comment character and padding.
   *
   * @return string
   */
  public function getValue() {
    return $this->value;
  }

}
/****************************** End of class Properties_Section_Comment ******************************/








/**
 * Internal Property section class that you don't need to know about.
 *
 * Everything in a properties file besides comments and blank lines, are considered as (name-value) properties.
 */
class Properties_Section_Property implements Properties_ISection {

  private $key   = null;
  private $value = null;
  private $seperator = null;
  const SEPERATOR_REGEX_PATTERN = '[\\t \\x0c]*[=:\\t \\x0c][\\t \\x0c]*';

  /**
   * Overloaded constructor.
   *
   * Overload 1:
   * @param line a raw property line.
   *
   * Overload 2:
   * @param key
   * @param value
   * @param seperator optional seperator character (default '=').
   * @throw InvalidArgumentException
   */
  public function __construct() {
    $key = null;
    $value = null;
    $seperator = null;
    switch (func_num_args()) {
    case 1:
      $line = func_get_arg(0);
      if (isset($line)) {
        $line = ltrim($line, Properties::WHITE_SPACE_CHARS);
      }
      if (!(isset($line) && strlen($line))) {
      	throw new InvalidArgumentException('Empty line passed into overloaded constructor.');
      }
      $parts = $this->_parseLine($line);
      if (!$parts) {
        throw new InvalidArgumentException('Invalid property line passed into overloaded constructor.');
      }
      list($key, $seperator, $value) = $parts;
      break;
    case 2:
    case 3:
      $key   = func_get_arg(0);
      $value = func_get_arg(1);
      if (func_num_args() == 3) {
        $seperator = func_get_arg(2);
      }
      if (!isset($seperator)) {
        $seperator = '=';
      }
      break;
    default:
      throw new InvalidArgumentException('Invalid arguments passed into overloaded constructor.');
    }
    $this->testKey($key);
    $this->testValue($value);
    $this->_testSeperator($seperator);
    $this->key       = $key;
    $this->seperator = $seperator;
    $this->value     = $value;
  }


  /**
   * Splits a raw key-value line into it's constituent parts.
   * Returns an array with the 3 elements: key, seperator, value
   *
   * @param line reference
   * @return array
   */
  private static function _parseLine(&$line) {
    // Locate unescaped seperator.
    // Seperators match this sequence and may not be prefixed with an escape character: /\s*(=|:)\s*/
    $result = false;
    $key = $seperator = $value = null;
    $offset = 0;
    while (preg_match('/^((?U).+)(' . self::SEPERATOR_REGEX_PATTERN . ')(.*)$/s', substr($line, $offset), $matches, PREG_OFFSET_CAPTURE)) { // (?U) means ungreedy
      $possible_key = $offset ? substr($line, 0, $offset) . $matches[1][0] : $matches[1][0];
      $count = 0;
      $len = strlen($possible_key);
      for ($i = $len - 1; $i >= 0; $i--) {
        if (substr($possible_key, $i, 1) == '\\') {
          $count++;
        }
        else {
          break;
        }
      }
      if ($count % 2 == 0) { // even number of direct prior escapes is ok
      	$key       = $possible_key;
      	$seperator = $matches[2][0];
      	$value     = $matches[3][0];
      	break;
      }
      $offset += $matches[2][1];
    }
    if (!isset($key)) {
      $key = $line;
      $seperator = '=';
    }
    if (!isset($value)) {
      $value = '';
    }
    return array(self::unescape($key), $seperator, self::unescape($value));
  }


  /**
   * Tests an unescaped key's syntax. Throws an exception on error.
   *
   * @param key
   * @throw InvalidArgumentException
   */
  public static function testKey($key) {
    if (!isset($key)) {
      throw new InvalidArgumentException("Property key may not be NULL!");
    }
    if (!strlen($key)) {
      throw new InvalidArgumentException("Property key may not be empty!");
    }
  }


  /**
   * Tests an unescaped value's syntax. Throws an exception on error.
   *
   * @param value
   * @throw InvalidArgumentException
   */
  public static function testValue($value) {
    if (!isset($value)) {
      throw new InvalidArgumentException("Property value may not be NULL!");
    }
  }


  /**
   * Tests a seperator's syntax. Throws an exception on error.
   *
   * @param seperator   
   * @throw InvalidArgumentException
   */
  protected static function _testSeperator($seperator) {
    if (!isset($seperator)) {
      throw new InvalidArgumentException("Seperator may not be NULL!");
    }
    if (!strlen($seperator)) {
      throw new InvalidArgumentException("Seperator may not be empty!");
    }
    if (!preg_match('/^' . self::SEPERATOR_REGEX_PATTERN . '$/', $seperator)) {
      throw new InvalidArgumentException("Bad syntax in seperator string!");
    }
  }


  /**
   * Escapes a character.
   * The characters LF (line feed), CR (carriage return), FF (form feed), : (colon), # (hash), ! (bang),
   * = (equals), tab, and backslash are escaped C-style with a backslash character.
   * Characters outside the range 0x20 to 0x7E are encoded in the \\uxxxx format.
   * If $escape_space is true then the space character is escaped too.
   *
   * @param char character
   * @param escape_space boolean
   * @return string
   */
  protected static function _escapeChar($char, $escape_space) {
    static $escmap = null;
    if (is_null($escmap)) {
      $escmap = array("\r"      => 'r',
                      "\n"      => 'n',
                      chr(0x0c) => 'f',
                      "\t"      => 't',
                      '\\'      => '\\',
                      ':'       => ':',
                      '#'       => '#',
                      '!'       => '!',
                      '='	=> '=');
    }
    if (array_key_exists($char, $escmap)) {
      return '\\' . $escmap[$char];
    }
    elseif ($escape_space && ($char == ' ')) {
      return '\\ ';
    }
    elseif ((ord($char) < 0x20) || (ord($char) > 0x7e)) {
      return sprintf('\\u%04X', ord($char));
    }
    return $char;
  }


  /**
   * Escapes a string.
   * The characters LF (line feed), CR (carriage return), FF (form feed), : (colon), # (hash), ! (bang),
   * = (equals), tab, and backslash are escaped C-style with a backslash character.   
   * Characters outside the range 0x20 to 0x7E are encoded in the \\uxxxx format.
   * If $escapeSpace is true then the ' ' characters are escaped too.
   *
   * @param s
   * @param escape_space boolean
   * @return string
   */
  protected static function escape($s, $escape_space) {
    $result = preg_replace('/([\\s=:#!\\\\]|[^\\x20-\\x7e])/e', 'self::_escapeChar("\\1", $escape_space)', $s);
    // If the first character of a string is a space then always escape it, even if $escape_space is false.
    if (!$escape_space && strlen($result)) {
      if (substr($result,0,1) === ' ') {
        $result = "\\$result";
      }
    }
    return $result;
  }


  /**
   * Escapes a key.
   * The characters space, LF (line feed), CR (carriage return), FF (form feed), : (colon), # (hash), ! (bang),
   * = (equals), tab, and backslash are escaped C-style with a backslash character.   
   * Characters outside the range 0x20 to 0x7E are encoded in the \\uxxxx format.
   *
   * @param key
   * @return string
   */
  public static function escapeKey($key) {
    return self::escape($key, true);
  }


  /**
   * Escapes a value.
   * The characters LF (line feed), CR (carriage return), FF (form feed), : (colon), # (hash), ! (bang),
   * = (equals), tab, and backslash are escaped C-style with a backslash character.   
   * Characters outside the range 0x20 to 0x7E are encoded in the \\uxxxx format.
   * If the value starts with a whitespace character, then that too is escaped.
   *
   * @param value
   * @return string
   */
  public static function escapeValue($value) {
    return self::escape($value, false);
  }


  /**
   * Unescapes an escaped character sequence.
   *
   * @param s string, or array of matches from preg_replace_callback().
   * @return string
   */
  protected static function _unescapeChar($s) {
    if (is_array($s)) {
      $s = $s[0];
    }
    if (substr($s,0,1) != '\\') {
      return $s; // it wasn't escaped.
    }
    $s = substr($s,1); // drop \ character
    if (strlen($s) > 1) {
      if (!preg_match('/^u[\da-fA-F]{4}$/', $s)) {
        throw new Exception("Malformed \\uxxxx encoding in '\\$s'.");
      }
      $ord = hexdec(substr($s,1));      
      if ($ord < 128) { 
        return chr($ord); 
      } 
      else if ($ord < 2048) { 
        return (chr(192 + (($ord - ($ord % 64)) / 64))) .
               (chr(128 + ($ord % 64))); 
      }
      else { 
        return (chr(224 + (($ord - ($ord % 4096)) / 4096))) .
               (chr(128 + ((($ord % 4096) - ($ord % 64)) / 64))) .
               (chr(128 + ($ord % 64))); 
      } 
    }
    static $unescmap = null;
    if (is_null($unescmap)) {
      $unescmap = array('r'  => "\r",
                        'n'  => "\n",
                        'f'  => chr(0x0c),
                        't'  => "\t");
    }
    if (array_key_exists($s, $unescmap)) {
      return $unescmap[$s];
    }
    return $s;
  }


  /**
   * Unescapes a string.
   *
   * @param s
   * @return string
   */
  public static function unescape($s) {
    return preg_replace_callback('/(\\\\u[\da-fA-F]{4}|\\\\(.))/', array(__CLASS__, '_unescapeChar'), $s);
  }


  /**
   * Returns the string representation of the section.
   *
   * @param max_chars_per_line unsigned integer, maximum number of characters per line (default 120).
   * @return string
   */
  public function toString($max_chars_per_line = 120) {
    if (isset($max_chars_per_line)) {
      if ($max_chars_per_line < 1) {
        $max_chars_per_line = null;
      }
    }
    $key_and_sep = $this->escapeKey($this->key) . $this->seperator;
    $value = $this->escapeValue($this->value);
    if (!$max_chars_per_line || (strlen($key_and_sep) + strlen($value) <= $max_chars_per_line)) {
      return "$key_and_sep$value\n";
    }
    $lines = array();
    if (strlen($key_and_sep) > $max_chars_per_line / 2) { // key and seperator are quite long so leave them on their own line
      array_push($lines, $key_and_sep);
    }
    elseif (preg_match('/^.{1,' . ($max_chars_per_line - strlen($key_and_sep)) . '}(?<!\\\\)[ \\t]/', $value, $matches)) { // if first breakable part of value is short then place it on same line as key   	
      if (strlen($key_and_sep) < 8) { // common tab length
      	array_push($lines, $key_and_sep . "\t" . $matches[0]);
      }
      else {
        array_push($lines, $key_and_sep . $matches[0]);
      }      
      $value = substr($value, strlen($matches[0]));
    }
    else {
      array_push($lines, $key_and_sep); // because value seems too unbreakable to put on same line as key.
    }       
    // split out the (rest of) value
    $parts = preg_split('/(.{1,' . $max_chars_per_line . '}(?<!\\\\)[ \\t])/', $value, -1, PREG_SPLIT_DELIM_CAPTURE);    
    for ($i = 0; $i < count($parts); $i += 2) {
      $len = strlen($parts[$i]); // usually empty, unless unsplitable.
      if ($len > $max_chars_per_line) {
        $line = '';
        for ($j = 0; $j < $len; $j += $max_chars_per_line) {       	
          $line .= substr($parts[$i], $j, $max_chars_per_line);
          if ($j + $max_chars_per_line < $len - 1) {
            $line .= "\\\n\t";
          }
        }
      }
      else {
        $line = $parts[$i];
      }
      if ($i+1 < count($parts)) {
      	if (strlen($line)) {
      	  $line .= "\\\n\t";
        }
        $line .= $parts[$i+1]; // the preg_split delimiter
      }
      array_push($lines, $line);
    }    
    return implode("\\\n\t", $lines) . "\n";
  }


  /**
   * Sets the value.
   *
   * @param value
   */
  public function setValue($value) {
    if ((string)$value != $this->value) {
      $this->testValue($value);
      $this->value = (string) $value;
    }
  }


  /**
   * Returns the key.
   *
   * @return string
   */
  public function getKey() {
    return $this->key;
  }


  /**
   * Returns the value.
   *
   * @return string
   */
  public function getValue() {
    return $this->value;
  }

}
/****************************** End of class Properties_Section_Property ******************************/

?>