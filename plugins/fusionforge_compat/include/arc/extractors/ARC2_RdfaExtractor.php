<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 RDFa Extractor
author:   Benjamin Nowack
version:  2009-05-29 (Fix: CURIEs support DOTs now)
*/

ARC2::inc('RDFExtractor');

class ARC2_RdfaExtractor extends ARC2_RDFExtractor {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_RdfaExtractor($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
  }

  /*  */
  
  function extractRDF() {
    //echo '<pre>' . htmlspecialchars(print_r($this->nodes, 1)) . '</pre>';
    if (!isset($this->caller->detected_formats['rdfa'])) return 0;
    $root_node = $this->getRootNode();
    //$base = $this->v('xml:base', $this->getDocBase(), $root_node['a']);
    $base = $this->getDocBase();
    $context = array(
      'base' => $base,
      'p_s' => $base,
      'p_o' => '',
      'ns' => array(),
      'inco_ts' => array(),
      'lang' => '',
    );
    $this->processNode($root_node, $context, 0);
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
  
  /*  */

  function processNode($n, $ct, $level) {
    if ($n['tag']=='cdata' || $n['tag']=='comment') return null; /* patch by tobyink */
    $ts_added = 0;
    /* step 1 */
    $lct = array();
    $lct['prev_s'] = $this->v('prev_s', $this->v('p_s', '', $ct), $ct);
    $lct['recurse'] = 1;
    $lct['skip'] = 0;
    $lct['new_s'] = '';
    $lct['cur_o_res'] = '';
    $lct['inco_ts'] = array();
    $lct['base'] = $ct['base'];
    //$lct['base'] = $this->v('xml:base', $ct['base'], $n['a']);
    /* step 2 */
    $lct['ns'] = array_merge($ct['ns'], $this->v('xmlns', array(), $n['a']));
    /* step 3 */
    $lct['lang'] = $this->v('xml:lang', $ct['lang'], $n['a']);
    /* step 4 */
    $rel_uris = $this->getAttributeURIs($n, $ct, $lct, 'rel');
    $rev_uris = $this->getAttributeURIs($n, $ct, $lct, 'rev');
    if (!$rel_uris && !$rev_uris) {
      foreach (array('about', 'src', 'resource', 'href') as $attr) {
        if (isset($n['a'][$attr]) && (list($uri, $sub_v) = $this->xURI($n['a'][$attr], $lct['base'], $lct['ns'], '', $lct)) && $uri) {
          $lct['new_s'] = $uri;
          break;
        }
      }
      if (!$lct['new_s']) {
        if (preg_match('/(head|body)/i', $n['tag'])) {
          $lct['new_s'] = $lct['base'];
        }
        elseif ($this->getAttributeURIs($n, $ct, $lct, 'typeof')) {
          $lct['new_s'] = $this->createBnodeID();
        }
        elseif ($ct['p_o']) {
          $lct['new_s'] = $ct['p_o'];
          //$lct['skip'] = 1;
          if(!isset($n['a']['property'])) $lct['skip'] = 1;/* patch by masaka */
        }
      }
    }
    /* step 5 */
    else {
      foreach (array('about', 'src') as $attr) {
        if (isset($n['a'][$attr]) && (list($uri, $sub_v) = $this->xURI($n['a'][$attr], $lct['base'], $lct['ns'], '', $lct)) && $uri) {
          $lct['new_s'] = $uri;
          break;
        }
      }
      if (!$lct['new_s']) {
        if (preg_match('/(head|body)/i', $n['tag'])) {
          $lct['new_s'] = $lct['base'];
        }
        elseif ($this->getAttributeURIs($n, $ct, $lct, 'typeof')) {
          $lct['new_s'] = $this->createBnodeID();
        }
        elseif ($ct['p_o']) {
          $lct['new_s'] = $ct['p_o'];
        }
      }
      foreach (array('resource', 'href') as $attr) {
        if (isset($n['a'][$attr]) && (list($uri, $sub_v) = $this->xURI($n['a'][$attr], $lct['base'], $lct['ns'], '', $lct)) && $uri) {
          $lct['cur_o_res'] = $uri;
          break;
        }
      }
    }
    /* step 6 */
    if ($lct['new_s']) {
      if ($uris = $this->getAttributeURIs($n, $ct, $lct, 'typeof')) {
        foreach ($uris as $uri) {
          $this->addT(array(
            's' => $lct['new_s'],
            's_type' => preg_match('/^\_\:/', $lct['new_s']) ? 'bnode' : 'uri',
            'p' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 
            'o' => $uri,
            'o_type' => 'uri',
            'o_lang' => '',
            'o_datatype' => '',
          ));
          $ts_added = 1;
        }
      }
      /* step 7 */
      if ($lct['cur_o_res']) {
        if ($rel_uris) {
          foreach ($rel_uris as $uri) {
            $this->addT(array(
              's' => $lct['new_s'],
              's_type' => preg_match('/^\_\:/', $lct['new_s']) ? 'bnode' : 'uri',
              'p' => $uri, 
              'o' => $lct['cur_o_res'],
              'o_type' => preg_match('/^\_\:/', $lct['cur_o_res']) ? 'bnode' : 'uri',
              'o_lang' => '',
              'o_datatype' => '',
            ));
            $ts_added = 1;
          }
        }
        if ($rev_uris) {
          foreach ($rev_uris as $uri) {
            $this->addT(array(
              's' => $lct['cur_o_res'],
              's_type' => preg_match('/^\_\:/', $lct['cur_o_res']) ? 'bnode' : 'uri',
              'p' => $uri, 
              'o' => $lct['new_s'],
              'o_type' => preg_match('/^\_\:/', $lct['new_s']) ? 'bnode' : 'uri',
              'o_lang' => '',
              'o_datatype' => '',
            ));
            $ts_added = 1;
          }
        }
      }
    }
    /* step 8 */
    if (!$lct['cur_o_res']) {
      if ($rel_uris || $rev_uris) {
        $lct['cur_o_res'] = $this->createBnodeID();
        foreach ($rel_uris as $uri) {
          $lct['inco_ts'][] = array('p' => $uri, 'dir' => 'fwd');
        }
        foreach ($rev_uris as $uri) {
          $lct['inco_ts'][] = array('p' => $uri, 'dir' => 'rev');
        }
      }
    }
    /* step 10 */
    if (!$lct['skip'] && ($new_s = $lct['new_s'])) {
    //if ($new_s = $lct['new_s']) {
      if ($uris = $this->getAttributeURIs($n, $ct, $lct, 'property')) {
        foreach ($uris as $uri) {
          $lct['cur_o_lit'] = $this->getCurrentObjectLiteral($n, $lct, $ct);
          $this->addT(array(
            's' => $lct['new_s'],
            's_type' => preg_match('/^\_\:/', $lct['new_s']) ? 'bnode' : 'uri',
            'p' => $uri, 
            'o' => $lct['cur_o_lit']['value'],
            'o_type' => 'literal',
            'o_lang' => $lct['cur_o_lit']['lang'],
            'o_datatype' => $lct['cur_o_lit']['datatype'],
          ));
          $ts_added = 1;
          if ($lct['cur_o_lit']['datatype'] == 'http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral') {
            $lct['recurse'] = 0;
          }
        }
      }
    }
    /* step 11 (10) */
    $complete_triples = 0;
    if ($lct['recurse']) {
      if ($lct['skip']) {
        $new_ct = array_merge($ct, array('base' => $lct['base'], 'lang' => $lct['lang'], 'ns' => $lct['ns']));
      }
      else {
        $new_ct = array(
          'base' => $lct['base'],
          'p_s' => $lct['new_s'] ? $lct['new_s'] : $ct['p_s'],
          'p_o' => $lct['cur_o_res'] ? $lct['cur_o_res'] : ($lct['new_s'] ? $lct['new_s'] : $ct['p_s']),
          'ns' => $lct['ns'],
          'inco_ts' => $lct['inco_ts'],
          'lang' => $lct['lang']
        );
      }
      $sub_nodes = $this->getSubNodes($n);
      foreach ($sub_nodes as $sub_node) {
        if ($this->processNode($sub_node, $new_ct, $level+1)) {
          $complete_triples = 1;
        }
      }
    }
    /* step 12 (11) */
    $other = 0;
    if ($ts_added || $complete_triples || ($lct['new_s'] && !preg_match('/^\_\:/', $lct['new_s'])) || ($other == 1)) {
    //if (!$lct['skip'] && ($complete_triples || ($lct['new_s'] && !preg_match('/^\_\:/', $lct['new_s'])))) {
      foreach ($ct['inco_ts'] as $inco_t) {
        if ($inco_t['dir'] == 'fwd') {
          $this->addT(array(
            's' => $ct['p_s'],
            's_type' => preg_match('/^\_\:/', $ct['p_s']) ? 'bnode' : 'uri',
            'p' => $inco_t['p'],
            'o' => $lct['new_s'],
            'o_type' => preg_match('/^\_\:/', $lct['new_s']) ? 'bnode' : 'uri',
            'o_lang' => '',
            'o_datatype' => '',
          ));
        }
        elseif ($inco_t['dir'] == 'rev') {
          $this->addT(array(
            's' => $lct['new_s'],
            's_type' => preg_match('/^\_\:/', $lct['new_s']) ? 'bnode' : 'uri',
            'p' => $inco_t['p'], 
            'o' => $ct['p_s'],
            'o_type' => preg_match('/^\_\:/', $ct['p_s']) ? 'bnode' : 'uri',
            'o_lang' => '',
            'o_datatype' => '',
          ));
        }
      }
    }
    /* step 13 (12) (result flag) */
    if ($ts_added) return 1;
    if ($lct['new_s'] && !preg_match('/^\_\:/', $lct['new_s'])) return 1;
    if ($complete_triples) return 1;
    return 0;
  }
  
  /*  */

  function getAttributeURIs($n, $ct, $lct, $attr) {
    $vals = ($val = $this->v($attr, '', $n['a'])) ? explode(' ', $val) : array();
    $r = array();
    foreach ($vals as $val) {
      if(!trim($val)) continue;
      if ((list($uri, $sub_v) = $this->xURI(trim($val), $lct['base'], $lct['ns'], $attr, $lct)) && $uri) {
        $r[] = $uri;
      }
    }
    return $r;
  }
  
  /*  */

  function getCurrentObjectLiteral($n, $lct, $ct) {
    $xml_val = $this->getContent($n);
    $plain_val = $this->getPlainContent($n, 0, 0);
    if (function_exists('html_entity_decode')) {
      $plain_val = html_entity_decode($plain_val, ENT_QUOTES);
    }
    $dt = $this->v('datatype', '', $n['a']);
    list($dt_uri, $sub_v) = $this->xURI($dt, $lct['base'], $lct['ns'], '', $lct);
    $dt = $dt ? $dt_uri : $dt;
    $r = array('value' => '', 'lang' => $lct['lang'], 'datatype' => $dt);
    if (isset($n['a']['content'])) {
      $r['value'] = $n['a']['content'];
      if (function_exists('html_entity_decode')) {
        $r['value'] = html_entity_decode($r['value'], ENT_QUOTES);
      }
    }
    elseif ($xml_val == $plain_val) {
      $r['value'] = $plain_val;
    }
    elseif (!preg_match('/[\<\>]/', $xml_val)) {
      $r['value'] = $xml_val;
    }
    elseif (isset($n['a']['datatype']) && ($dt != 'http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral')) {
      $r['value'] = $plain_val;
    }
    elseif (!isset($n['a']['datatype']) || ($dt == 'http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral')) {
      $r['value'] = $this->injectXMLDeclarations($xml_val, $lct['ns'], $lct['lang']);
      $r['datatype'] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral';
    }
    return $r;
  }
  
  function injectXMLDeclarations($val, $ns, $lang) {//@@todo proper node rebuilding */
    $lang_code = $lang ? ' xml:lang="' . $lang . '"' : '';
    /* ns */
    $val = preg_replace('/<([a-z0-9]+)([\>\s])/is', '<\\1 xmlns="http://www.w3.org/1999/xhtml"' . $lang_code . '\\2', $val);
    foreach ($ns as $prefix => $uri) {
      if ($prefix && ($pos = strpos(' ' . $val, '<' . $prefix . ':'))) {
        $val = substr($val, 0, $pos - 1) . preg_replace('/^(<' . $prefix . '\:[^\>\s]+)/', '\\1 xmlns:' . $prefix. '="' . $uri . '"' . $lang_code, substr($val, $pos - 1));
      }
    }
    /* remove accidentally added xml:lang and xmlns= */
    $val = preg_replace('/(\<[^\>]*)( xml\:lang[^\s\>]+)([^\>]*)(xml\:lang[^\s\>]+)/s', '\\1\\3\\4', $val);
    $val = preg_replace('/(\<[^\>]*)( xmlns=[^\s\>]+)([^\>]*)(xmlns=[^\s\>]+)/s', '\\1\\3\\4', $val);
    return $val;
  }
  
  /*  */
  
  function xURI($v, $base, $ns, $attr_type = '', $lct = '') {
    if ((list($sub_r, $sub_v) = $this->xBlankCURIE($v, $base, $ns)) && $sub_r) {
      return array($sub_r, $sub_v);
    }
    if ((list($sub_r, $sub_v) = $this->xSafeCURIE($v, $base, $ns, $lct)) && $sub_r) {
      return array($sub_r, $sub_v);
    }
    if ((list($sub_r, $sub_v) = $this->xCURIE($v, $base, $ns)) && $sub_r) {
      return array($sub_r, $sub_v);
    }
    if (preg_match('/^(rel|rev)$/', $attr_type) && preg_match('/^\s*(alternate|appendix|bookmark|cite|chapter|contents|copyright|glossary|help|icon|index|last|license|meta|next|p3pv1|prev|role|section|stylesheet|subsection|start|up)(\s|$)/is', $v, $m)) {
      return array('http://www.w3.org/1999/xhtml/vocab#' . strtolower($m[1]), preg_replace('/^\s*' . $m[1]. '/is', '', $v));
    }
    if (preg_match('/^(rel|rev)$/', $attr_type) && preg_match('/^[a-z0-9\.]+$/i', $v)) {
      return array(0, $v);
    }
    return array($this->calcURI($v, $base), '');
  }
  
  function xBlankCURIE($v, $base, $ns) {
    if ($sub_r = $this->x('\[\_\:\]', $v)) {
      $this->empty_bnode = isset($this->empty_bnode) ? $this->empty_bnode : $this->createBnodeID();
      return array($this->empty_bnode, '');
    }
    if ($sub_r = $this->x('\[?(\_\:[a-z0-9\_\-]+)\]?', $v)) {
      return array($sub_r[1], '');
    }
    return array(0, $v);
  }
  
  function xSafeCURIE($v, $base, $ns, $lct = '') {
    /* empty */
    if ($sub_r = $this->x('\[\]', $v)) {
      $r = $lct ? $lct['prev_s'] : $base;/* should be current subject value */
      return $sub_r[1] ? array($r, $sub_r[1]) : array($r, '');
    }
    if ($sub_r = $this->x('\[([^\:]*)\:([^\]]*)\]', $v)) {
      if (!$sub_r[1]) return array('http://www.w3.org/1999/xhtml/vocab#' . $sub_r[2], '');
      if (isset($ns[$sub_r[1]])) {
        return array($ns[$sub_r[1]] . $sub_r[2], '');
      }
    }
    return array(0, $v);
  }
  
  function xCURIE($v, $base, $ns) {
    if ($sub_r = $this->x('([a-z0-9\-\_]*)\:([^\s]+)', $v)) {
      if (!$sub_r[1]) return array('http://www.w3.org/1999/xhtml/vocab#' . $sub_r[2], '');
      if (isset($ns[$sub_r[1]])) {
        return array($ns[$sub_r[1]] . $sub_r[2], '');
      }
    }
    return array(0, $v);
  }
  
  /*  */

}
