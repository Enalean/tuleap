<?php
/**
 * ARC2 N-Triples Serializer
 *
 * @author Benjamin Nowack
 * @license <http://arc.semsol.org/license>
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version 2010-03-29
*/

ARC2::inc('RDFSerializer');

class ARC2_NTriplesSerializer extends ARC2_RDFSerializer {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_NTriplesSerializer($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->esc_chars = array();
    $this->raw = 0;
  }

  /*  */
  
  function getTerm($v) {
    if (!is_array($v)) {
      if (preg_match('/^\_\:/', $v)) {
        return $v;
      }
      if (preg_match('/^[a-z0-9]+\:[^\s\"]*$/is', $v)) {
        return '<' . $this->escape($v) . '>';
      }
      return $this->getTerm(array('type' => 'literal', 'value' => $v));
    }
    if ($v['type'] != 'literal') {
      return $this->getTerm($v['value']);
    }
    /* literal */
    $quot = '"';
    if ($this->raw && preg_match('/\"/', $v['value'])) {
      $quot = "'";
      if (preg_match('/\'/', $v['value'])) {
        $quot = '"""';
        if (preg_match('/\"\"\"/', $v['value']) || preg_match('/\"$/', $v['value']) || preg_match('/^\"/', $v['value'])) {
          $quot = "'''";
          $v['value'] = preg_replace("/'$/", "' ", $v['value']);
          $v['value'] = preg_replace("/^'/", " '", $v['value']);
          $v['value'] = str_replace("'''", '\\\'\\\'\\\'', $v['value']);
        }
      }
    }
    if ($this->raw && (strlen($quot) == 1) && preg_match('/[\x0d\x0a]/', $v['value'])) {
      $quot = $quot . $quot . $quot;
    }
    $suffix = isset($v['lang']) && $v['lang'] ? '@' . $v['lang'] : '';
    $suffix = isset($v['datatype']) && $v['datatype'] ? '^^' . $this->getTerm($v['datatype']) : $suffix;
    //return $quot . "object" . utf8_encode($v['value']) . $quot . $suffix;
    return $quot . $this->escape($v['value']) . $quot . $suffix;
  }
  
  function getSerializedIndex($index, $raw = 0) {
    $this->raw = $raw;
    $r = '';
    $nl = "\n";
    foreach ($index as $s => $ps) {
      $s = $this->getTerm($s);
      foreach ($ps as $p => $os) {
        $p = $this->getTerm($p);
        if (!is_array($os)) {/* single literal o */
          $os = array(array('value' => $os, 'type' => 'literal'));
        }
        foreach ($os as $o) {
          $o = $this->getTerm($o);
          $r .= $r ? $nl : '';
          $r .= $s . ' ' . $p . ' ' . $o . ' .';
        }
      }
    }
    return $r . $nl;
  }
  
  /*  */

  function escape($v) {
    $r = '';
    $v = (strpos(utf8_decode(str_replace('?', '', $v)), '?') === false) ? utf8_decode($v) : $v;
    if ($this->raw) return $v;
    for ($i = 0, $i_max = strlen($v); $i < $i_max; $i++) {
      $c = $v[$i];
      if (!isset($this->esc_chars[$c])) {
        $this->esc_chars[$c] = $this->getEscapedChar($c, $this->getCharNo($c));
      }
      $r .= $this->esc_chars[$c];
    }
    return $r;
  }
  
  /*  */
  
  function getCharNo($c) {
    $c_utf = utf8_encode($c);
    $bl = strlen($c_utf);/* binary length */
    $r = 0;
    switch ($bl) {
      case 1:/* 0####### (0-127) */
        $r = ord($c_utf);
        break;
      case 2:/* 110##### 10###### = 192+x 128+x */
        $r = ((ord($c_utf[0]) - 192) * 64) + (ord($c_utf[1]) - 128);
        break;
      case 3:/* 1110#### 10###### 10###### = 224+x 128+x 128+x */
        $r = ((ord($c_utf[0]) - 224) * 4096) + ((ord($c_utf[1]) - 128) * 64) + (ord($c_utf[2]) - 128);
        break;
      case 4:/* 1111#### 10###### 10###### 10###### = 240+x 128+x 128+x 128+x */
        $r = ((ord($c_utf[0]) - 240) * 262144) + ((ord($c_utf[1]) - 128) * 4096) + ((ord($c_utf[2]) - 128) * 64) + (ord($c_utf[3]) - 128);
        break;
    }
    return $r;
  }

  function getEscapedChar($c, $no) {/*see http://www.w3.org/TR/rdf-testcases/#ntrip_strings */
    if ($no < 9)        return "\\u" . sprintf('%04X', $no);  /* #x0-#x8 (0-8) */
    if ($no == 9)       return '\t';                          /* #x9 (9) */
    if ($no == 10)      return '\n';                          /* #xA (10) */
    if ($no < 13)       return "\\u" . sprintf('%04X', $no);  /* #xB-#xC (11-12) */
    if ($no == 13)      return '\r';                          /* #xD (13) */
    if ($no < 32)       return "\\u" . sprintf('%04X', $no);  /* #xE-#x1F (14-31) */
    if ($no < 34)       return $c;                            /* #x20-#x21 (32-33) */
    if ($no == 34)      return '\"';                          /* #x22 (34) */
    if ($no < 92)       return $c;                            /* #x23-#x5B (35-91) */
    if ($no == 92)      return '\\';                          /* #x5C (92) */
    if ($no < 127)      return $c;                            /* #x5D-#x7E (93-126) */
    if ($no < 65536)    return "\\u" . sprintf('%04X', $no);  /* #x7F-#xFFFF (128-65535) */
    if ($no < 1114112)  return "\\U" . sprintf('%08X', $no);  /* #x10000-#x10FFFF (65536-1114111) */
    return '';                                                /* not defined => ignore */
  }
  
  /*  */
 
}
