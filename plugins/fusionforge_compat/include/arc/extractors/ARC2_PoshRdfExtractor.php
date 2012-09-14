<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 poshRDF Extractor
author:   Benjamin Nowack
version:  
*/

ARC2::inc('ARC2_RDFExtractor');

class ARC2_PoshRdfExtractor extends ARC2_RDFExtractor {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_PoshRdfExtractor($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->terms = $this->v('posh_terms', array(), $this->a);
    $this->ns_prefix = 'posh';
    $this->a['ns'] += array(
      'an' => 'http://www.w3.org/2000/10/annotation-ns#',
      'content' => 'http://purl.org/rss/1.0/modules/content/',
      'dc' => 'http://purl.org/dc/elements/1.1/',
      'dct' => 'http://purl.org/dc/terms/',
      'foaf' => 'http://xmlns.com/foaf/0.1/',
      'geo' => 'http://www.w3.org/2003/01/geo/wgs84_pos#',
      'ical' => 'http://www.w3.org/2002/12/cal/icaltzd#',
      'owl' => 'http://www.w3.org/2002/07/owl#',
      'posh' => 'http://poshrdf.org/ns/posh/',
      'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
      'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
      'rev' => 'http://www.purl.org/stuff/rev#',
      'rss' => 'http://purl.org/rss/1.0/',
      'sioc' => 'http://rdfs.org/sioc/ns#',
      'skos' => 'http://www.w3.org/2008/05/skos#',
      'uri' => 'http://www.w3.org/2006/uri#',
      'vcard' => 'http://www.w3.org/2006/vcard/ns#',
      'xfn' => 'http://gmpg.org/xfn/11#',
      'xml' => 'http://www.w3.org/XML/1998/namespace',
      'xsd' => 'http://www.w3.org/2001/XMLSchema#',
    );
  }

  /*  */
  
  function extractRDF() {
    if (!isset($this->caller->detected_formats['posh-rdf'])) return 0;
    $n = $this->getRootNode();
    $base = $this->getDocBase();
    $context = array(
      'id' => $n['id'],
      'tag' => $n['tag'],
      'base' => $base,
      's' => array(array('_doc', $base)),
      'next_s' => array('_doc', $base),
      'ps' => array(),
      'ns' => $this->a['ns'],
      'lang' => '',
      'rpointer' => '',
    );
    $ct = $this->processNode($n, $context, 0, 1);
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

  function processNode($n, $ct, $level, $pos) {
    $n = $this->preProcessNode($n);
    /* local context */
    $lct = array_merge($ct, array(
      'ns' => array_merge($ct['ns'], $this->v('xmlns', array(), $n['a'])),
      'rpointer' => isset($n['a']['id']) ? $n['a']['id'] : ($n['tag'] == 'cdata' ? '' : $ct['rpointer'] . '/' . $pos),
      'tag' => $n['tag'],
      'id' => $n['id'],
      'lang' => $this->v('xml:lang', $ct['lang'], $n['a']),
    ));
    /* s stack */
    $next_s_key = $lct['next_s'][0];
    $next_s_val = $lct['next_s'][1];
    if ($lct['s'][0][0] != $next_s_key) {
      $lct['s'] = array_merge(array($lct['next_s']), $lct['s']);
    }
    else {
      $lct['s'][0][1] = $next_s_val;
    }
    /* new s */
    if ($this->hasClass($n, 'rdf-s')) {
      $lct['next_s'] = array($n['a']['class'], $this->getSubject($n, $lct));
      //echo "\ns: " . print_r($lct['next_s'], 1);
    }
    /* p */
    if ($this->hasClass($n, 'rdf-p') || $this->hasRel($n, 'rdf-p')) {
      if ($ps = $this->getPredicates($n, $lct['ns'])) {
        $lct['ps'] = $ps;
        $this->addPoshTypes($lct);
      }
    }
    /* o */
    $cls = $this->v('class', '', $n['a']);
    if ($lct['ps'] && preg_match('/(^|\s)rdf\-(o|o\-(xml|dateTime|float|integer|boolean))($|\s)/s', $cls, $m)) {
      $this->addTriples($n, $lct, $m[3]);
    }
    /* sub-nodes */
    if ($sub_nodes = $this->getSubNodes($n)) {
      $cur_ct = $lct;
      $sub_pos = 1;
      foreach ($sub_nodes as $i => $sub_node) {
        if (in_array($sub_node['tag'], array('cdata', 'comment'))) continue;
        $sub_ct = $this->processNode($sub_node, $cur_ct, $level + 1, $sub_pos);
        $sub_pos++;
        $cur_ct['next_s'] = $sub_ct['next_s'];
        $cur_ct['ps'] = $sub_ct['ps'];
      }
    }
    return $lct;
  }
  
  /*  */
  
  function getSubject($n, $ct) {
    foreach (array('href uri', 'src uri', 'title', 'value') as $k) {
      if (isset($n['a'][$k])) return $n['a'][$k];
    }
    /* rpointer */
    return $ct['base'] . '#resource(' . $ct['rpointer'] . ')';
  }
  
  function getPredicates($n, $ns) {
    $r = array();
    /* try pnames */
    $vals = array_merge($this->v('class m', array(), $n['a']), $this->v('rel m', array(), $n['a']));
    foreach ($vals as $val) {
      if (!preg_match('/^([a-z0-9]+)\-([a-z0-9\-\_]+)$/i', $val, $m)) continue;
      if (!isset($ns[$m[1]])) continue;
      if (preg_match('/^rdf-(s|p|o|o-(xml|dateTime|float|integer|boolean))$/', $val)) continue;
      $r[] = $ns[$m[1]] . $m[2];
    }
    /* try other attributes */
    if (!$r) {
      foreach (array('href uri', 'title') as $k) {
        if (isset($n['a'][$k])) {
          $r[] = $n['a'][$k];
          break;
        }
      }
    }
    return $r;
  }

  function addTriples($n, $ct, $o_type) {
    foreach (array('href uri', 'src uri', 'title', 'value') as $k) {
      if (isset($n['a'][$k])) {
        $node_o = $n['a'][$k];
        break;
      }
    }
    if (!isset($node_o) && $this->hasClass($n, 'rdf-s')) {
      $node_o = $ct['next_s'][1];
    }
    $lit_o = ($o_type == 'xml') ? $this->getContent($n) : $this->getPlainContent($n);
    $posh_ns = $ct['ns'][$this->ns_prefix];
    $rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    $xsd = 'http://www.w3.org/2001/XMLSchema#';
    foreach ($ct['ps'] as $p) {
      $p_key = str_replace($posh_ns, '', $p);
      /* dt or obj */
      $o = $this->isDatatypeProperty($p_key) ? $lit_o : (isset($node_o) ? $node_o : $lit_o);
      if (!$o) continue;
      if (!$s = $this->getContainerSubject($ct, $p_key)) continue;
      $lang = (($o == $lit_o) && !$o_type) ? $ct['lang'] : '';
      $o = $this->tweakObject($o, $p, $ct);
      $this->addT(array(
        's' => $this->getContainerSubject($ct, $p_key),
        's_type' => preg_match('/^\_\:/', $s) ? 'bnode' : 'uri',
        'p' => $p, 
        'o' => $o,
        'o_type' => $this->getObjectType($o, $p_key),
        'o_lang' => $lang,
        'o_datatype' => ($o_type == 'xml') ? $rdf . 'XMLLiteral' : ($o_type ? $xsd . $o_type : ''),
      ));
    }
  }

  function addPoshTypes($ct) {
    $posh_ns = $ct['ns'][$this->ns_prefix];
    foreach ($ct['ps'] as $p) {
      $p_key = str_replace($posh_ns, '', $p);
      if (!$this->isSubject($p_key)) continue;
      $s = $ct['next_s'][1];
      $this->addT(array(
        's' => $s,
        's_type' => preg_match('/^\_\:/', $s) ? 'bnode' : 'uri',
        'p' => $ct['ns']['rdf'] . 'type', 
        'o' => $posh_ns . ucfirst($p_key),
        'o_type' => 'uri',
        'o_lang' => '',
        'o_datatype' => '',
      ));
    }
  }
  
  /*  */
  
  function preProcessNode($n) {
    return $n;
  }
  
  function getContainerSubject($ct, $term) {
    if (!isset($this->terms[$term])) return $ct['s'][0][1];
    $scope = $this->v('scope', array(), $this->terms[$term]);
    if (!$scope) return $ct['s'][0][1];
    $scope_re = join('|', $scope);
    foreach ($ct['s'] as $s) {
      if (preg_match('/(^|\s)(' . $scope_re. ')($|\s)/s', str_replace($this->ns_prefix . '-', '', $s[0]))) return $s[1];
    }
    return 0;
  }
  
  function isSubject($term) {
    if (!isset($this->terms[$term])) return 0;
    return in_array('s', $this->terms[$term]);
  }
  
  function isDatatypeProperty($term) {
    if (!isset($this->terms[$term])) return 0;
    return in_array('plain', $this->terms[$term]);
  }
  
  function getObjectType($o, $term) {
    if ($this->isDatatypeProperty($term)) return 'literal';
    if (strpos($o, ' ')) return 'literal';
    return preg_match('/^([a-z0-9\_]+)\:[^\s]+$/s', $o, $m) ? ($m[1] == '_' ? 'bnode' : 'uri') : 'literal';
  }
  
  function tweakObject($o, $p, $ct) {
    return $o;
  }
  
  /*  */

}
