<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 SPARQL Result XML Parser
author:   Benjamin Nowack
version:  2008-08-28 (Addition: Support for "inserted" and "deleted")
*/

ARC2::inc('LegacyXMLParser');

class ARC2_SPARQLXMLResultParser extends ARC2_LegacyXMLParser {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_SPARQLXMLResultParser($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {/* reader */
    parent::__init();
    $this->srx = 'http://www.w3.org/2005/sparql-results#';
    $this->nsp[$this->srx] = 'srx';
    $this->allowCDataNodes = 0;
  }
  
  /*  */
  
  function done() {
  }
  
  /*  */
  
  function getVariables() {
    $r = array();
    foreach ($this->nodes as $node) {
      if ($node['tag'] == $this->srx . 'variable') {
        $r[] = $node['a']['name'];
      }
    }
    return $r;
  }
  
  function getRows() {
    $r = array();
    $index = $this->getNodeIndex();
    foreach ($this->nodes as $node) {
      if ($node['tag'] == $this->srx . 'result') {
        $row = array();
        $row_id = $node['id'];
        $bindings = isset($index[$row_id])? $index[$row_id] : array();
        foreach ($bindings as $binding) {
          $row = array_merge($row, $this->getBinding($binding));
        }
        if ($row) {
          $r[] = $row;
        }
      }
    }
    return $r;
  }

  function getBinding($node) {
    $r = array();
    $index = $this->getNodeIndex();
    $var = $node['a']['name'];
    $term = $index[$node['id']][0];
    $r[$var . ' type'] = preg_replace('/^uri$/', 'uri', substr($term['tag'], strlen($this->srx)));
    $r[$var] = ($r[$var . ' type'] == 'bnode') ? '_:' . $term['cdata'] : $term['cdata'];
    if (isset($term['a']['datatype'])) {
      $r[$var . ' datatype'] = $term['a']['datatype'];
    }
    elseif (isset($term['a'][$this->xml . 'lang'])) {
      $r[$var . ' lang'] = $term['a'][$this->xml . 'lang'];
    }
    return $r;
  }

  function getBooleanInsertedDeleted() {
    foreach ($this->nodes as $node) {
      if ($node['tag'] == $this->srx . 'boolean') {
        return ($node['cdata'] == 'true') ? array('boolean' => true) : array('boolean' => false);
      }
      elseif ($node['tag'] == $this->srx . 'inserted') {
        return array('inserted' => $node['cdata']);
      }
      elseif ($node['tag'] == $this->srx . 'deleted') {
        return array('deleted' => $node['cdata']);
      }
      elseif ($node['tag'] == $this->srx . 'results') {
        return '';
      }
    }
    return '';
  }

  /*  */
  
  function getStructure() {
    $r = array('variables' => $this->getVariables(), 'rows' => $this->getRows());
    /* boolean|inserted|deleted */
    if ($sub_r = $this->getBooleanInsertedDeleted()) {
      foreach ($sub_r as $k => $v) {
        $r[$k] = $v;
      }
    }
    return $r;
  }

  /*  */

  
}
