<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 Store DESCRIBE Query Handler
author:   Benjamin Nowack
version:  2008-01-09 (Tweak: label auto-detection is now optional)
*/

ARC2::inc('StoreSelectQueryHandler');

class ARC2_StoreDescribeQueryHandler extends ARC2_StoreSelectQueryHandler {

  function __construct($a = '', &$caller) {/* caller has to be a store */
    parent::__construct($a, $caller);
  }
  
  function ARC2_StoreDescribeQueryHandler($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {/* db_con */
    parent::__init();
    $this->store =& $this->caller;
    $this->detect_labels = $this->v('detect_describe_query_labels', 0, $this->a);
  }

  /*  */
  
  function runQuery($infos) {
    $ids = $infos['query']['result_uris'];
    if ($vars = $infos['query']['result_vars']) {
      $sub_r = parent::runQuery($infos);
      $rf = $this->v('result_format', '', $infos);
      if (in_array($rf, array('sql', 'structure', 'index'))) {
        return $sub_r;
      }
      $rows = $this->v('rows', array(), $sub_r);
      foreach ($rows as $row) {
        foreach ($vars as $info) {
          $val = isset($row[$info['var']]) ? $row[$info['var']] : '';
          if ($val && ($row[$info['var'] . ' type'] != 'literal') && !in_array($val, $ids)) {
            $ids[] = $val;
          }
        }
      }
    }
    $this->r = array();
    $this->described_ids = array();
    $this->ids = $ids;
    $this->added_triples = array();
    $is_sub_describe = 0;
    while ($this->ids) {
      $id = $this->ids[0];
      $this->described_ids[] = $id;
      if ($this->detect_labels) {
        $q = '
          CONSTRUCT { 
            <' . $id . '> ?p ?o . 
            ?o ?label_p ?o_label . 
            ?o <http://arc.semsol.org/ns/arc#label> ?o_label .
          } WHERE { 
            <' . $id . '> ?p ?o .
            OPTIONAL {
              ?o ?label_p ?o_label .
              FILTER REGEX(str(?label_p), "(name|label|title|summary|nick|fn)$", "i") 
            }
          }
        ';
      }
      else {
        $q = '
          CONSTRUCT { 
            <' . $id . '> ?p ?o . 
          } WHERE { 
            <' . $id . '> ?p ?o .
          }
        ';
      }
      $sub_r = $this->store->query($q);
      $sub_index = is_array($sub_r['result']) ? $sub_r['result'] : array();
      $this->mergeSubResults($sub_index, $is_sub_describe);
      $is_sub_describe = 1;
    }
    return $this->r;
  }
  
  /*  */
  
  function mergeSubResults($index, $is_sub_describe = 1) {
    foreach ($index as $s => $ps) {
      if (!isset($this->r[$s])) $this->r[$s] = array();
      foreach ($ps as $p => $os) {
        if (!isset($this->r[$s][$p])) $this->r[$s][$p] = array();
        foreach ($os as $o) {
          $id = md5($s . ' ' . $p . ' ' . serialize($o));
          if (!isset($this->added_triples[$id])) {
            if (1 || !$is_sub_describe) {
              $this->r[$s][$p][] = $o;
              if (is_array($o) && ($o['type'] == 'bnode') && !in_array($o['value'], $this->ids)) $this->ids[] = $o['value'];
            }
            elseif (!is_array($o) || ($o['type'] != 'bnode')) {
              $this->r[$s][$p][] = $o;
            }
            $this->added_triples[$id] = 1;
          }
        }
      }
    }
    /* adjust ids */
    $ids = $this->ids;
    $this->ids = array();
    foreach ($ids as $id) {
      if (!in_array($id, $this->described_ids)) $this->ids[] = $id;
    }
  }
  
  /*  */

}


