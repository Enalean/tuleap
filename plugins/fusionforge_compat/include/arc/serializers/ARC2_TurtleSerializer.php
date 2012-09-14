<?php
/**
 * ARC2 Turtle Serializer
 *
 * @author    Benjamin Nowack
 * @license   http://arc.semsol.org/license
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version   2009-11-23
 *
*/

ARC2::inc('RDFSerializer');

class ARC2_TurtleSerializer extends ARC2_RDFSerializer {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_TurtleSerializer($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->content_header = 'application/x-turtle';
  }

  /*  */
  
  function getTerm($v, $term = '', $qualifier = '') {
    if (!is_array($v)) {
      if (preg_match('/^\_\:/', $v)) {
        return $v;
      }
      if (($term === 'p') && ($pn = $this->getPName($v))) {
        return $pn;
      }
      if (
        ($term === 'o') &&
        in_array($qualifier, array('rdf:type', 'rdfs:domain', 'rdfs:range', 'rdfs:subClassOf')) &&
        ($pn = $this->getPName($v))
      ) {
        return $pn;
      }
      if (preg_match('/^[a-z0-9]+\:[^\s]*$/is', $v)) {
        return '<' .$v. '>';
      }
      return $this->getTerm(array('type' => 'literal', 'value' => $v), $term, $qualifier);
    }
    if (!isset($v['type']) || ($v['type'] != 'literal')) {
      return $this->getTerm($v['value'], $term, $qualifier);
    }
    /* literal */
    $quot = '"';
    if (preg_match('/\"/', $v['value'])) {
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
    if ((strlen($quot) == 1) && preg_match('/[\x0d\x0a]/', $v['value'])) {
      $quot = $quot . $quot . $quot;
    }
    $suffix = isset($v['lang']) && $v['lang'] ? '@' . $v['lang'] : '';
    $suffix = isset($v['datatype']) && $v['datatype'] ? '^^' . $this->getTerm($v['datatype'], 'dt') : $suffix;
    return $quot . $v['value'] . $quot . $suffix;
  }
  
  function getHead() {
    $r = '';
    $nl = "\n";
    foreach ($this->used_ns as $v) {
      $r .= $r ? $nl : '';
      $r .= '@prefix ' . $this->nsp[$v] . ': <' .$v. '> .';
    }
    return $r;
  }
  
  function getSerializedIndex($index, $raw = 0) {
    $r = '';
    $nl = "\n";
    foreach ($index as $s => $ps) {
      $r .= $r ? ' .' . $nl . $nl : '';
      $s = $this->getTerm($s, 's');
      $r .= $s;
      $first_p = 1;
      foreach ($ps as $p => $os) {
        if (!$os) continue;
        $p = $this->getTerm($p, 'p');
        $r .= $first_p ? ' ' : ' ;' . $nl . str_pad('', strlen($s) + 1);
        $r .= $p;
        $first_o = 1;
        if (!is_array($os)) {/* single literal o */
          $os = array(array('value' => $os, 'type' => 'literal'));
        }
        foreach ($os as $o) {
          $r .= $first_o ? ' ' : ' ,' . $nl . str_pad('', strlen($s) + strlen($p) + 2);
          $o = $this->getTerm($o, 'o', $p);
          $r .= $o;
          $first_o = 0;
        }
        $first_p = 0;
      }
    }
    $r .= $r ? ' .' : '';
    if ($raw) {
      return $r;
    }
    return $r ? $this->getHead() . $nl . $nl . $r : '';
  }
  
  /*  */

}
