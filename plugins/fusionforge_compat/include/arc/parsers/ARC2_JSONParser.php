<?php
/**
 * ARC2 JSON Parser
 * Does not extract triples, needs sub-class for RDF extraction
 *
 * @author Benjamin Nowack <bnowack@semsol.com>
 * @license http://arc.semsol.org/license
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version 2010-06-07
*/

ARC2::inc('RDFParser');

class ARC2_JSONParser extends ARC2_RDFParser {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_JSONParser($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
  }
  
  /*  */

  function x($re, $v, $options = 'si') {
    while (preg_match('/^\s*(\/\*.*\*\/)(.*)$/Usi', $v, $m)) {/* comment removal */
      $v = $m[2];
    }
    $this->unparsed_code = (strlen($this->unparsed_code) > strlen($v)) ? $v : $this->unparsed_code;
    return ARC2::x($re, $v, $options);
  }

  function parse($path, $data = '') {
    $this->state = 0;
    /* reader */
    if (!$this->v('reader')) {
      ARC2::inc('Reader');
      $this->reader = & new ARC2_Reader($this->a, $this);
    }
    $this->reader->setAcceptHeader('Accept: application/json; q=0.9, */*; q=0.1');
    $this->reader->activate($path, $data);
    $this->x_base = isset($this->a['base']) && $this->a['base'] ? $this->a['base'] : $this->reader->base;
    /* parse */
    $doc = '';
    while ($d = $this->reader->readStream()) {
      $doc .= $d;
    }
    $this->reader->closeStream();
    unset($this->reader);
    $doc = preg_replace('/^[^\{]*(.*\})[^\}]*$/is', '\\1', $doc);
    $this->unparsed_code = $doc;
    list($this->struct, $rest) = $this->extractObject($doc);
    return $this->done();
  }
  
  /*  */
  
  function extractObject($v) {
    if (function_exists('json_decode')) return array(json_decode($v, 1), '');
    $r = array();
    /* sub-object */
    if ($sub_r = $this->x('\{', $v)) {
      $v = $sub_r[1];
      while ((list($sub_r, $v) = $this->extractEntry($v)) && $sub_r) {
        $r[$sub_r['key']] = $sub_r['value'];
      }
      if ($sub_r = $this->x('\}', $v)) $v = $sub_r[1];
    }
    /* sub-list */
    elseif ($sub_r = $this->x('\[', $v)) {
      $v = $sub_r[1];
      while ((list($sub_r, $v) = $this->extractObject($v)) && $sub_r) {
        $r[] = $sub_r;
        $v = ltrim($v, ',');
      }
      if ($sub_r = $this->x('\]', $v)) $v = $sub_r[1];
    }
    /* sub-value */
    elseif ((list($sub_r, $v) = $this->extractValue($v)) && ($sub_r !== false)) {
      $r = $sub_r;
    }
    return array($r, $v);
  }
  
  function extractEntry($v) {
    if ($r = $this->x('\,', $v)) $v = $r[1];
    /* k */
    if ($r = $this->x('\"([^\"]+)\"\s*\:', $v)) {
      $k = $r[1];
      $sub_v = $r[2];
      if (list($sub_r, $sub_v) = $this->extractObject($sub_v)) {
        return array(
          array('key' => $k, 'value' => $sub_r),
          $sub_v
        );
      }
    }
    return array(0, $v);
  }
  
  function extractValue($v) {
    if ($r = $this->x('\,', $v)) $v = $r[1];
    if ($sub_r = $this->x('null', $v)) {
      return array(null, $sub_r[1]);
    }
    if ($sub_r = $this->x('(true|false)', $v)) {
      return array($sub_r[1], $sub_r[2]);
    }
    if ($sub_r = $this->x('([\-\+]?[0-9\.]+)', $v)) {
      return array($sub_r[1], $sub_r[2]);
    }
    if ($sub_r = $this->x('\"', $v)) {
      $rest = $sub_r[1];
      if (preg_match('/^([^\x5c]*|.*[^\x5c]|.*\x5c{2})\"(.*)$/sU', $rest, $m)) {
        $val = $m[1];
        /* unescape chars (single-byte) */
        $val = preg_replace('/\\\u(.{4})/e', 'chr(hexdec("\\1"))', $val);
        //$val = preg_replace('/\\\u00(.{2})/e', 'rawurldecode("%\\1")', $val);
        /* other escaped chars */
        $from = array('\\\\', '\r', '\t', '\n', '\"', '\b', '\f', '\/');
        $to = array("\\", "\r", "\t", "\n", '"', "\b", "\f", "/");
        $val = str_replace($from, $to, $val);
        return array($val, $m[2]);
      }
    }
    return array(false, $v);
  }
  
  /*  */

  function getObject() {
    return $this->v('struct', array());
  }
  
  function getTriples() {
    return $this->v('triples', array());
  }
  
  function countTriples() {
    return $this->t_count;
  }

  function addT($s = '', $p = '', $o = '', $s_type = '', $o_type = '', $o_dt = '', $o_lang = '') {
    $o = $this->toUTF8($o);
    //echo str_replace($this->base, '', "-----\n adding $s / $p / $o\n-----\n");
    $t = array('s' => $s, 'p' => $p, 'o' => $o, 's_type' => $s_type, 'o_type' => $o_type, 'o_datatype' => $o_dt, 'o_lang' => $o_lang);
    if ($this->skip_dupes) {
      $h = md5(serialize($t));
      if (!isset($this->added_triples[$h])) {
        $this->triples[$this->t_count] = $t;
        $this->t_count++;
        $this->added_triples[$h] = true;
      }
    }
    else {
      $this->triples[$this->t_count] = $t;
      $this->t_count++;
    }
  }

  /*  */
  
}
