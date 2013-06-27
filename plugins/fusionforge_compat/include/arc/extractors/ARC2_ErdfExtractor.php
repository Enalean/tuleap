<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 eRDF Extractor (w/o link title generation)
author:   Benjamin Nowack
version:  2009-02-09 (Tweak: getRootNode returns 1st node if html tag is not found)
*/

ARC2::inc('RDFExtractor');

class ARC2_ErdfExtractor extends ARC2_RDFExtractor {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_ErdfExtractor($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
  }

  /*  */
  
  function extractRDF() {
    if (!isset($this->caller->detected_formats['erdf'])) return 0;
    $root_node = $this->getRootNode();
    $base = $this->getDocBase();
    $ns = $this->getNamespaces();
    $context = array(
      'base' => $base,
      'prev_res' => $base,
      'cur_res' => $base,
      'ns' => $ns,
      'lang' => '',
    );
    $this->processNode($root_node, $context);
  }
  
  /*  */
  
  function getRootNode() {
    foreach ($this->nodes as $id => $node) {
      if ($node['tag'] == 'html') {
        return $node;
      }
    }
    return $this->nodes[0];
  }
  
  function getNamespaces() {
    $r = array(
      'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
      'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#'
    );
    foreach ($this->nodes as $id => $node) {
      if (preg_match('/^(link|a)$/', $node['tag']) && isset($node['a']['rel']) && preg_match('/schema\.([^\s]+)/is', $node['a']['rel'], $m) && isset($node['a']['href uri'])) {
        $r[$m[1]] = $node['a']['href uri'];
      }
    }
    return $r;
  }

  /*  */
  
  function processNode($n, $ct) {
    /* context */
    //$ct['lang'] = $this->v('xml:lang', $ct['lang'], $n['a']);
    $ct['lang'] = '';
    $ct['prop_uris'] = $this->getPropertyURIs($n, $ct);
    $ct['prev_res'] = $ct['cur_res'];
    $ct['cur_res'] = $this->getCurrentResourceURI($n, $ct);
    $ct['cur_obj_id'] = $this->getCurrentObjectID($n, $ct);
    $ct['cur_obj_literal'] = $this->getCurrentObjectLiteral($n, $ct);
    /* triple production (http://research.talis.com/2005/erdf/wiki/Main/SummaryOfTripleProductionRules) */
    foreach ($ct['prop_uris'] as $type => $uris) {
      foreach ($uris as $uri) {
        $rdf_type = preg_match('/^ /', $uri) ? 1 : 0;
        /* meta + name */
        if (($type == 'name') && ($n['tag'] == 'meta')) {
          $t = array(
            's' => $ct['cur_res'],
            's_type' => 'uri',
            'p' => $uri, 
            'o' => $ct['cur_obj_literal']['value'],
            'o_type' => 'literal',
            'o_lang' => $ct['cur_obj_literal']['datatype'] ? '' : $ct['cur_obj_literal']['lang'],
            'o_datatype' => $ct['cur_obj_literal']['datatype'],
          );
          $this->addT($t);
        }
        /* class */
        if ($type == 'class') {
          if ($rdf_type) {
            $s = $this->v('href uri', $ct['cur_res'], $n['a']);
            $s = $this->v('src uri', $s, $n['a']);
            $t = array(
              's' => $s,
              's_type' => 'uri',
              'p' => $ct['ns']['rdf'] . 'type', 
              'o' => trim($uri),
              'o_type' => 'uri',
              'o_lang' => '',
              'o_datatype' => '',
            );
          }
          elseif (isset($n['a']['id'])) {/* used as object */
            $t = array(
              's' => $ct['prev_res'],
              's_type' => 'uri',
              'p' => $uri, 
              'o' => $ct['cur_res'],
              'o_type' => 'uri',
              'o_lang' => '',
              'o_datatype' => '',
            );
          }
          else {
            $t = array(
              's' => $ct['cur_res'],
              's_type' => 'uri',
              'p' => $uri, 
              'o' => $ct['cur_obj_literal']['value'],
              'o_type' => 'literal',
              'o_lang' => $ct['cur_obj_literal']['datatype'] ? '' : $ct['cur_obj_literal']['lang'],
              'o_datatype' => $ct['cur_obj_literal']['datatype'],
            );
            if (($o = $this->v('src uri', '', $n['a'])) || ($o = $this->v('href uri', '', $n['a']))) {
              if (!$ct['prop_uris']['rel'] && !$ct['prop_uris']['rev']) {
                $t['o'] = $o;
                $t['o_type'] = 'uri';
                $t['o_lang'] = '';
                $t['o_datatype'] = '';
              }
            }
          }
          $this->addT($t);
        }
        /* rel */
        if ($type == 'rel') {
          if (($o = $this->v('src uri', '', $n['a'])) || ($o = $this->v('href uri', '', $n['a']))) {
            $t = array(
              's' => $ct['cur_res'],
              's_type' => 'uri',
              'p' => $uri, 
              'o' => $o,
              'o_type' => 'uri',
              'o_lang' => '',
              'o_datatype' => '',
            );
            $this->addT($t);
          }
        }
        /* rev */
        if ($type == 'rev') {
          if (($s = $this->v('src uri', '', $n['a'])) || ($s = $this->v('href uri', '', $n['a']))) {
            $t = array(
              's' => $s,
              's_type' => 'uri',
              'p' => $uri, 
              'o' => $ct['cur_res'],
              'o_type' => 'uri',
              'o_lang' => '',
              'o_datatype' => '',
            );
            $this->addT($t);
          }
        }
      }
    }
    /* imgs */
    if ($n['tag'] == 'img') {
      if (($s = $this->v('src uri', '', $n['a'])) && $ct['cur_obj_literal']['value']) {
        $t = array(
          's' => $s,
          's_type' => 'uri',
          'p' => $ct['ns']['rdfs'] . 'label', 
          'o' => $ct['cur_obj_literal']['value'],
          'o_type' => 'literal',
          'o_lang' => $ct['cur_obj_literal']['datatype'] ? '' : $ct['cur_obj_literal']['lang'],
          'o_datatype' => $ct['cur_obj_literal']['datatype'],
        );
        $this->addT($t);
      }
    }
    /* anchors */
    if ($n['tag'] == 'a') {
      if (($s = $this->v('href uri', '', $n['a'])) && $ct['cur_obj_literal']['value']) {
        $t = array(
          's' => $s,
          's_type' => 'uri',
          'p' => $ct['ns']['rdfs'] . 'label', 
          'o' => $ct['cur_obj_literal']['value'],
          'o_type' => 'literal',
          'o_lang' => $ct['cur_obj_literal']['datatype'] ? '' : $ct['cur_obj_literal']['lang'],
          'o_datatype' => $ct['cur_obj_literal']['datatype'],
        );
        $this->addT($t);
      }
    }
    /* recurse */
    if ($n['tag'] == 'a') {
      $ct['cur_res'] = $ct['cur_obj_id'];
    }
    $sub_nodes = $this->getSubNodes($n);
    foreach ($sub_nodes as $sub_node) {
      $this->processNode($sub_node, $ct);
    }
  }

  /*  */
  
  function getPropertyURIs($n, $ct) {
    $r = array();
    foreach (array('rel', 'rev', 'class', 'name', 'src') as $type) {
      $r[$type] = array();
      $vals = $this->v($type . ' m', array(), $n['a']);
      foreach ($vals as $val) {
        if (!trim($val)) continue;
        list($uri, $sub_v) = $this->xQname(trim($val, '- '), $ct['base'], $ct['ns'], $type);
        if (!$uri) continue;
        $rdf_type = preg_match('/^-/', trim($val)) ? 1 : 0;
        $r[$type][] = $rdf_type ? ' ' . $uri : $uri;
      }
    }
    return $r;
  }

  function getCurrentResourceURI($n, $ct) {
    if (isset($n['a']['id'])) {
      list($r, $sub_v) = $this->xURI('#' . $n['a']['id'], $ct['base'], $ct['ns']);
      return $r;
    }
    return $ct['cur_res'];
  }
  
  function getCurrentObjectID($n, $ct) {
    foreach (array('href', 'src') as $a) {
      if (isset($n['a'][$a])) {
        list($r, $sub_v) = $this->xURI($n['a'][$a], $ct['base'], $ct['ns']);
        return $r;
      }
    }
    return $this->createBnodeID();
  }

  function getCurrentObjectLiteral($n, $ct) {
    $r = array('value' => '', 'lang' => $ct['lang'], 'datatype' => '');
    if (isset($n['a']['content'])) {
      $r['value'] = $n['a']['content'];
    }
    elseif (isset($n['a']['title'])) {
      $r['value'] = $n['a']['title'];
    }
    else {
      $r['value'] = $this->getPlainContent($n);
    }
    return $r;
  }
  
  /*  */
  
  function xURI($v, $base, $ns, $attr_type = '') {
    if ((list($sub_r, $sub_v) = $this->xQname($v, $base, $ns)) && $sub_r) {
      return array($sub_r, $sub_v);
    }
    if (preg_match('/^(rel|rev|class|name)$/', $attr_type) && preg_match('/^[a-z0-9]+$/', $v)) {
      return array(0, $v);
    }
    return array($this->calcURI($v, $base), '');
  }
  
  function xQname($v, $base, $ns) {
    if ($sub_r = $this->x('([a-z0-9\-\_]+)[\-\.]([a-z0-9\-\_]+)', $v)) {
      if (isset($ns[$sub_r[1]])) {
        return array($ns[$sub_r[1]] . $sub_r[2], '');
      }
    }
    return array(0, $v);
  }
  
  /*  */

}
