<?php
/**
 * ARC2 base class
 *
 * @author Benjamin Nowack
 * @license <http://arc.semsol.org/license>
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version 2010-06-25
*/

class ARC2_Class {
  
  /*  */

  function __construct($a = '', &$caller) {
    $a = is_array($a) ? $a : array();
    $this->a = $a;
    $this->caller = &$caller;
    $this->__init();
  }
  
  function ARC2_Class($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __destruct() {
    //echo "\ndestructing " . get_class($this);
  }

  function __init() {/* base, time_limit */
    if (!$_POST && isset($GLOBALS['HTTP_RAW_POST_DATA'])) parse_str($GLOBALS['HTTP_RAW_POST_DATA'], $_POST); /* php5 bug */
    $this->inc_path = ARC2::getIncPath();
    $this->ns_count = 0;
    $this->nsp = array('http://www.w3.org/1999/02/22-rdf-syntax-ns#' => 'rdf');
    $this->used_ns = array('http://www.w3.org/1999/02/22-rdf-syntax-ns#');
    $this->ns = $this->v('ns', array(), $this->a);

    $this->base = $this->v('base', ARC2::getRequestURI(), $this->a);
    $this->errors = array();
    $this->warnings = array();
    $this->adjust_utf8 = $this->v('adjust_utf8', 0, $this->a);
    $this->max_errors = $this->v('max_errors', 25, $this->a);
  }

  /*  */
  
  function v($name, $default = false, $o = false) {/* value if set */
    if ($o === false) $o =& $this;
    if (is_array($o)) {
      return isset($o[$name]) ? $o[$name] : $default;
    }
    return isset($o->$name) ? $o->$name : $default;
  }
  
  function v1($name, $default = false, $o = false) {/* value if 1 (= not empty) */
    if ($o === false) $o =& $this;
    if (is_array($o)) {
      return (isset($o[$name]) && $o[$name]) ? $o[$name] : $default;
    }
    return (isset($o->$name) && $o->$name) ? $o->$name : $default;
  }
  
  function m($name, $a = false, $default = false, $o = false) {/* call method */
    if ($o === false) $o =& $this;
    return method_exists($o, $name) ? $o->$name($a) : $default;
  }

  /*  */

  function camelCase($v, $lc_first = 0, $keep_boundaries = 0) {
    $r = ucfirst($v);
    while (preg_match('/^(.*)[^a-z0-9](.*)$/si', $r, $m)) {
      /* don't fuse 2 upper-case chars */
      if ($keep_boundaries && $m[1]) {
        $boundary = substr($m[1], -1);
        if (strtoupper($boundary) == $boundary) $m[1] .= 'CAMELCASEBOUNDARY';
      }
      $r = $m[1] . ucfirst($m[2]);
    }
    $r = str_replace('CAMELCASEBOUNDARY', '_', $r);
    if ((strlen($r) > 1) && $lc_first && !preg_match('/[A-Z]/', $r[1])) $r = strtolower($r[0]) . substr($r, 1);
    return $r;
  }

  function deCamelCase($v, $uc_first = 0) {
    $r = str_replace('_', ' ', $v);
    $r = preg_replace('/([a-z0-9])([A-Z])/e', '"\\1 " . strtolower("\\2")', $r);
    return $uc_first ? ucfirst($r) : $r;
  }

  function extractTermLabel($uri, $loops = 0) {
    list($ns, $r) = $this->splitURI($uri);
    $r = $this->deCamelCase($this->camelCase($r, 1, 1));
    if (($loops < 1) && preg_match('/^(self|it|this|me)$/i', $r)) {
      return $this->extractTermLabel(preg_replace('/\#.+$/', '', $uri), $loops + 1);
    }
    if ($uri && !$r && ($loops < 2)) {
      return $this->extractTermLabel(preg_replace('/[\#\/]$/', '', $uri), $loops + 1);
    }
    return $r;
  }

  /*  */
  
  function addError($v) {
    if (!in_array($v, $this->errors)) {
      $this->errors[] = $v;
    }
    if ($this->caller && method_exists($this->caller, 'addError')) {
      $glue = strpos($v, ' in ') ? ' via ' : ' in ';
      $this->caller->addError($v . $glue . get_class($this));
    }
    if (count($this->errors) > $this->max_errors) {
      die('Too many errors (limit: ' . $this->max_errors . '): ' . print_r($this->errors, 1));
    }
    return false;
  }
  
  function getErrors() {
    return $this->errors;
  }
  
  function getWarnings() {
    return $this->warnings;
  }

  function resetErrors() {
    $this->errors = array();
    if ($this->caller && method_exists($this->caller, 'resetErrors')) {
      $this->caller->resetErrors();
    }
  }
  
  /*  */
  
  function splitURI($v) {
    return ARC2::splitURI($v);
  }

  /*  */

  function getPName($v, $connector = ':') {
    /* is already a pname */
    if ($ns = $this->getPNameNamespace($v, $connector)) {
      if (!in_array($ns, $this->used_ns)) $this->used_ns[] = $ns;
      return $v;
    }
    /* new pname */
    if ($parts = $this->splitURI($v)) {
      /* known prefix */
      foreach ($this->ns as $prefix => $ns) {
        if ($parts[0] == $ns) {
          if (!in_array($ns, $this->used_ns)) $this->used_ns[] = $ns;
          return $prefix . $connector . $parts[1];
        }
      }
      /* new prefix */
      $prefix = $this->getPrefix($parts[0]);
      return $prefix . $connector . $parts[1];
    }
    return $v;
  }

  function getPNameNamespace($v, $connector = ':') {
    $re = '/^([a-z0-9\_\-]+)\:([a-z0-9\_\-\.\%]+)$/i';
    if ($connector != ':') {
      $connectors = array('\:', '\-', '\_', '\.');
      $chars = join('', array_diff($connectors, array($connector)));
      $re = '/^([a-z0-9' . $chars . ']+)\\' . $connector . '([a-z0-9\_\-\.\%]+)$/i';
    }
    if (!preg_match($re, $v, $m)) return 0;
    if (!isset($this->ns[$m[1]])) return 0;
    return $this->ns[$m[1]];
  }

  function getPrefix($ns) {
    if (!isset($this->nsp[$ns])) {
      $this->ns['ns' . $this->ns_count] = $ns;
      $this->nsp[$ns] = 'ns' . $this->ns_count;
      $this->ns_count++;
    }
    if (!in_array($ns, $this->used_ns)) $this->used_ns[] = $ns;
    return $this->nsp[$ns];
  }

  function expandPName($v, $connector = ':') {
    $re = '/^([a-z0-9\_\-]+)\:([a-z0-9\_\-\.\%]+)$/i';
    if ($connector != ':') {
      $connectors = array(':', '-', '_', '.');
      $chars = '\\' . join('\\', array_diff($connectors, array($connector)));
      $re = '/^([a-z0-9' . $chars . ']+)\\' . $connector . '([a-z0-9\_\-\.\%]+)$/Ui';
    }
    if (preg_match($re, $v, $m) && isset($this->ns[$m[1]])) {
      return $this->ns[$m[1]] . $m[2];
    }
    return $v;
  }

  function expandPNames($index) {
    $r = array();
    foreach ($index as $s => $ps) {
      $s = $this->expandPName($s);
      $r[$s] = array();
      foreach ($ps as $p => $os) {
        $p = $this->expandPName($p);
        if (!is_array($os)) $os = array($os);
        foreach ($os as $i => $o) {
          if (!is_array($o)) {
            $o_val = $this->expandPName($o);
            $o_type = preg_match('/^[a-z]+\:[^\s\<\>]+$/si', $o_val) ? 'uri' : 'literal';
            $o = array('value' => $o_val, 'type' => $o_type);
          }
          $os[$i] = $o;
        }
        $r[$s][$p] = $os;
      }
    }
    return $r;
  }

  /*  */
  
  function calcURI($path, $base = "") {
    /* quick check */
    if (preg_match("/^[a-z0-9\_]+\:/i", $path)) {/* abs path or bnode */
      return $path;
    }
    if (preg_match('/^\$\{.*\}/', $path)) {/* placeholder, assume abs URI */
      return $path;
    }
    if (preg_match("/^\/\//", $path)) {/* net path, assume http */
      return 'http:' . $path;
    }
    /* other URIs */
    $base = $base ? $base : $this->base;
    $base = preg_replace('/\#.*$/', '', $base);
    if ($path === true) {/* empty (but valid) URIref via turtle parser: <> */
      return $base;
    }
    $path = preg_replace("/^\.\//", '', $path);
    $root = preg_match('/(^[a-z0-9]+\:[\/]{1,3}[^\/]+)[\/|$]/i', $base, $m) ? $m[1] : $base; /* w/o trailing slash */
    $base .= ($base == $root) ? '/' : '';
    if (preg_match('/^\//', $path)) {/* leading slash */
      return $root . $path;
    }
    if (!$path) {
      return $base;
    }
    if (preg_match('/^([\#\?])/', $path, $m)) {
      return preg_replace('/\\' .$m[1]. '.*$/', '', $base) . $path;
    }
    if (preg_match('/^(\&)(.*)$/', $path, $m)) {/* not perfect yet */
      return preg_match('/\?/', $base) ? $base . $m[1] . $m[2] : $base . '?' . $m[2];
    }
    if (preg_match("/^[a-z0-9]+\:/i", $path)) {/* abs path */
      return $path;
    }
    /* rel path: remove stuff after last slash */
    $base = substr($base, 0, strrpos($base, '/')+1);
    /* resolve ../ */
    while (preg_match('/^(\.\.\/)(.*)$/', $path, $m)) {
      $path = $m[2];
      $base = ($base == $root.'/') ? $base : preg_replace('/^(.*\/)[^\/]+\/$/', '\\1', $base);
    }
    return $base . $path;
  }
  
  /*  */
  
  function calcBase($path) {
    $r = $path;
    $r = preg_replace('/\#.*$/', '', $r);/* remove hash */
    $r = preg_replace('/^\/\//', 'http://', $r);/* net path (//), assume http */
    if (preg_match('/^[a-z0-9]+\:/', $r)) {/* scheme, abs path */
      while (preg_match('/^(.+\/)(\.\.\/.*)$/U', $r, $m)) {
        $r = $this->calcURI($m[1], $m[2]);
      }
      return $r;
    }
    return 'file://' . realpath($r);/* real path */
  }

  /*  */

  function getResource($uri, $store_or_props = '') {
    $res = ARC2::getResource($this->a);
    $res->setURI($uri);
    if (is_array($store_or_props)) {
      $res->setProps($store_or_props);
    }
    else {
      $res->setStore($store_or_props);
    }
    return $res;
  }
  
  function toIndex($v) {
    if (is_array($v)) {
      if (isset($v[0]) && isset($v[0]['s'])) return ARC2::getSimpleIndex($v, 0);
      return $v;
    }
    $parser = ARC2::getRDFParser($this->a);
    if ($v && !preg_match('/\s/', $v)) {/* assume graph URI */
      $parser->parse($v);
    }
    else {
      $parser->parse('', $v);
    }
    return $parser->getSimpleIndex(0);
  }

  function toTriples($v) {
    if (is_array($v)) {
      if (isset($v[0]) && isset($v[0]['s'])) return $v;
      return ARC2::getTriplesFromIndex($v);
    }
    $parser = ARC2::getRDFParser($this->a);
    if ($v && !preg_match('/\s/', $v)) {/* assume graph URI */
      $parser->parse($v);
    }
    else {
      $parser->parse('', $v);
    }
    return $parser->getTriples();
  }

  /*  */

  function toNTriples($v, $ns = '', $raw = 0) {
    ARC2::inc('NTriplesSerializer');
    if (!$ns) $ns = isset($this->a['ns']) ? $this->a['ns'] : array();
    $ser = new ARC2_NTriplesSerializer(array_merge($this->a, array('ns' => $ns)), $this);
    return (isset($v[0]) && isset($v[0]['s'])) ? $ser->getSerializedTriples($v, $raw) : $ser->getSerializedIndex($v, $raw);
  }
  
  function toTurtle($v, $ns = '', $raw = 0) {
    ARC2::inc('TurtleSerializer');
    if (!$ns) $ns = isset($this->a['ns']) ? $this->a['ns'] : array();
    $ser = new ARC2_TurtleSerializer(array_merge($this->a, array('ns' => $ns)), $this);
    return (isset($v[0]) && isset($v[0]['s'])) ? $ser->getSerializedTriples($v, $raw) : $ser->getSerializedIndex($v, $raw);
  }
  
  function toRDFXML($v, $ns = '', $raw = 0) {
    ARC2::inc('RDFXMLSerializer');
    if (!$ns) $ns = isset($this->a['ns']) ? $this->a['ns'] : array();
    $ser = new ARC2_RDFXMLSerializer(array_merge($this->a, array('ns' => $ns)), $this);
    return (isset($v[0]) && isset($v[0]['s'])) ? $ser->getSerializedTriples($v, $raw) : $ser->getSerializedIndex($v, $raw);
  }

  function toRDFJSON($v, $ns = '') {
    ARC2::inc('RDFJSONSerializer');
    if (!$ns) $ns = isset($this->a['ns']) ? $this->a['ns'] : array();
    $ser = new ARC2_RDFJSONSerializer(array_merge($this->a, array('ns' => $ns)), $this);
    return (isset($v[0]) && isset($v[0]['s'])) ? $ser->getSerializedTriples($v) : $ser->getSerializedIndex($v);
  }

  function toRSS10($v, $ns = '') {
    ARC2::inc('RSS10Serializer');
    if (!$ns) $ns = isset($this->a['ns']) ? $this->a['ns'] : array();
    $ser = new ARC2_RSS10Serializer(array_merge($this->a, array('ns' => $ns)), $this);
    return (isset($v[0]) && isset($v[0]['s'])) ? $ser->getSerializedTriples($v) : $ser->getSerializedIndex($v);
  }

  function toLegacyXML($v, $ns = '') {
    ARC2::inc('LegacyXMLSerializer');
    if (!$ns) $ns = isset($this->a['ns']) ? $this->a['ns'] : array();
    $ser = new ARC2_LegacyXMLSerializer(array_merge($this->a, array('ns' => $ns)), $this);
    return $ser->getSerializedArray($v);
  }

  function toLegacyJSON($v, $ns = '') {
    ARC2::inc('LegacyJSONSerializer');
    if (!$ns) $ns = isset($this->a['ns']) ? $this->a['ns'] : array();
    $ser = new ARC2_LegacyJSONSerializer(array_merge($this->a, array('ns' => $ns)), $this);
    return $ser->getSerializedArray($v);
  }

  function toLegacyHTML($v, $ns = '') {
    ARC2::inc('LegacyHTMLSerializer');
    if (!$ns) $ns = isset($this->a['ns']) ? $this->a['ns'] : array();
    $ser = new ARC2_LegacyHTMLSerializer(array_merge($this->a, array('ns' => $ns)), $this);
    return $ser->getSerializedArray($v);
  }

  function toHTML($v, $ns = '', $label_store = '') {
    ARC2::inc('MicroRDFSerializer');
    if (!$ns) $ns = isset($this->a['ns']) ? $this->a['ns'] : array();
    $conf = array_merge($this->a, array('ns' => $ns));
    if ($label_store) $conf['label_store'] = $label_store;
    $ser = new ARC2_MicroRDFSerializer($conf, $this);
    return (isset($v[0]) && isset($v[0]['s'])) ? $ser->getSerializedTriples($v) : $ser->getSerializedIndex($v);
  }

  /*  */

  function getFilledTemplate($t, $vals, $g = '') {
    $parser = ARC2::getTurtleParser();
    $parser->parse($g, $this->getTurtleHead() . $t);
    return $parser->getSimpleIndex(0, $vals);
  }
  
  function getTurtleHead() {
    $r = '';
    $ns = $this->v('ns', array(), $this->a);
    foreach ($ns as $k => $v) {
      $r .= "@prefix " . $k . ": <" .$v. "> .\n";
    }
    return $r;
  }
  
  function completeQuery($q, $ns = '') {
    if (!$ns) $ns = isset($this->a['ns']) ? $this->a['ns'] : array();
    $added_prefixes = array();
    $prologue = '';
    foreach ($ns as $k => $v) {
      $k = rtrim($k, ':');
      if (in_array($k, $added_prefixes)) continue;
      if (preg_match('/(^|\s)' . $k . ':/s', $q) && !preg_match('/PREFIX\s+' . $k . '\:/is', $q)) {
        $prologue .= "\n" . 'PREFIX ' . $k . ': <' . $v . '>';
      }
      $added_prefixes[] = $k;
    }
    return $prologue . "\n" . $q;
  }

  /*  */

  function toUTF8($str) {
    return $this->adjust_utf8 ? ARC2::toUTF8($str) : $str;
  }

  function toDataURI($str) {
    return 'data:text/plain;charset=utf-8,' . rawurlencode($str);
  }

  function fromDataURI($str) {
    return str_replace('data:text/plain;charset=utf-8,', '', rawurldecode($str));
  }

  /* prevent SQL injections via SPARQL REGEX */

  function checkRegex($str) {
    return addslashes($str); // @@todo extend
  }
  
  /* Microdata methods */

  function getMicrodataAttrs($id, $type = '') {
    $type = $type ? $this->expandPName($type) : $this->expandPName('owl:Thing');
    return 'itemscope="" itemtype="' . htmlspecialchars($type) . '" itemid="' . htmlspecialchars($id) . '"';
  }

  function mdAttrs($id, $type = '') {
    return $this->getMicrodataAttrs($id, $type);
  }

  /* central DB query hook */

  function queryDB($sql, $con, $log_errors = 0) {
    $t1 = ARC2::mtime();
    $r = mysql_query($sql, $con);
    $t2 = ARC2::mtime() - $t1;
    if ($t2 > 1) {
      //echo "\n needed " . $t2 . ' secs for ' . $sql;
    }
    if ($log_errors && ($er = mysql_error($con))) $this->addError($er);
    return $r;
  }

  /*  */

}
