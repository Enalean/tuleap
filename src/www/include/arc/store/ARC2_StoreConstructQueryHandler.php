<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 RDF Store CONSTRUCT Query Handler
author:   Benjamin Nowack
version:  2008-02-11 (Fix: auto-adding DISTINCT to avoid unnecessary duplicates)
*/

ARC2::inc('StoreSelectQueryHandler');

class ARC2_StoreConstructQueryHandler extends ARC2_StoreSelectQueryHandler {

  function __construct($a = '', &$caller) {/* caller has to be a store */
    parent::__construct($a, $caller);
  }
  
  function ARC2_StoreConstructQueryHandler($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {/* db_con */
    parent::__init();
    $this->store =& $this->caller;
  }

  /*  */
  
  function runQuery($infos) {
    $this->infos = $infos;
    $this->buildResultVars();
    $this->infos['query']['distinct'] = 1;
    $sub_r = parent::runQuery($this->infos);
    $rf = $this->v('result_format', '', $infos);
    if (in_array($rf, array('sql', 'structure', 'index'))) {
      return $sub_r;
    }
    return $this->getResultIndex($sub_r);
  }
  
  /*  */
  
  function buildResultVars() {
    $r = array();
    foreach ($this->infos['query']['construct_triples'] as $t) {
      foreach (array('s', 'p', 'o') as $term) {
        if ($t[$term . '_type'] == 'var') {
          if (!in_array($t[$term], $r)) {
            $r[] = array('var' => $t[$term], 'aggregate' => '', 'alias' => '');
          }
        }
      }
    }
    $this->infos['query']['result_vars'] = $r;
  }

  /*  */

  function getResultIndex($qr) {
    $r = array();
    $added = array();
    $rows = $this->v('rows', array(), $qr);
    $cts = $this->infos['query']['construct_triples'];
    $bnc = 0;
    foreach ($rows as $row) {
      $bnc++;
      foreach ($cts as $ct) {
        $skip_t = 0;
        $t = array();
        foreach (array('s', 'p', 'o') as $term) {
          $val = $ct[$term];
          $type = $ct[$term . '_type'];
          $val = ($type == 'bnode') ? $val . $bnc : $val;
          if ($type == 'var') {
            $skip_t = !isset($row[$val]) ? 1 : $skip_t;
            $type = !$skip_t ? $row[$val . ' type'] : '';
            $val = (!$skip_t) ? $row[$val] : '';
          }
          $t[$term] = $val;
          $t[$term . '_type'] = $type;
          if (isset($row[$term . ' lang'])) {
            $t[$term . '_lang'] = $row[$term . ' lang'];
          }
          if (isset($row[$term . ' datatype'])) {
            $t[$term . '_datatype'] = $row[$term . ' datatype'];
          }
        }
        if (!$skip_t) {
          $s = $t['s'];
          $p = $t['p'];
          $o = $t['o'];
          if (!isset($r[$s])) {
            $r[$s] = array();
          }
          if (!isset($r[$s][$p])) {
            $r[$s][$p] = array();
          }
          $o = array('value' => $o);
          foreach (array('lang', 'type', 'datatype') as $suffix) {
            if (isset($t['o_' . $suffix]) && $t['o_' . $suffix]) {
              $o[$suffix] = $t['o_' . $suffix];
            }
          }
          if (!isset($added[md5($s . ' ' . $p . ' ' . serialize($o))])) {
            $r[$s][$p][] = $o;
            $added[md5($s . ' ' . $p . ' ' . serialize($o))] = 1;
          }
        }
      }
    }
    return $r;
  }
  
  /*  */

}


