<?php
/**
 * ARC2 RDF/XML Parser
 *
 * @author Benjamin Nowack <bnowack@semsol.com>
 * @license http://arc.semsol.org/license
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version 2009-12-03
*/

ARC2::inc('RDFParser');

class ARC2_RDFXMLParser extends ARC2_RDFParser {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_RDFXMLParser($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {/* reader */
    parent::__init();
    $this->encoding = $this->v('encoding', false, $this->a);
    $this->state = 0;
    $this->x_lang = '';
    $this->x_base = $this->base;
    $this->xml = 'http://www.w3.org/XML/1998/namespace';
    $this->rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    $this->nsp = array($this->xml => 'xml', $this->rdf => 'rdf');
    $this->s_stack = array();
    $this->s_count = 0;
    $this->target_encoding = '';
  }
  
  /*  */

  function parse($path, $data = '', $iso_fallback = false) {
    /* reader */
    if (!$this->v('reader')) {
      ARC2::inc('Reader');
      $this->reader = & new ARC2_Reader($this->a, $this);
    }
    $this->reader->setAcceptHeader('Accept: application/rdf+xml; q=0.9, */*; q=0.1');
    $this->reader->activate($path, $data);
    $this->x_base = isset($this->a['base']) && $this->a['base'] ? $this->a['base'] : $this->reader->base;
    /* xml parser */
    $this->initXMLParser();
    /* parse */
    $first = true;
    while ($d = $this->reader->readStream()) {
      if (!$this->keep_time_limit) @set_time_limit($this->v('time_limit', 60, $this->a));
      if ($iso_fallback && $first) {
        $d = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n" . preg_replace('/^\<\?xml [^\>]+\?\>\s*/s', '', $d);
        $first = false;
      }
      if (!xml_parse($this->xml_parser, $d, false)) {
        $error_str = xml_error_string(xml_get_error_code($this->xml_parser));
        $line = xml_get_current_line_number($this->xml_parser);
        $this->tmp_error = 'XML error: "' . $error_str . '" at line ' . $line . ' (parsing as ' . $this->getEncoding() . ')';
        if (!$iso_fallback && preg_match("/Invalid character/i", $error_str)) {
          xml_parser_free($this->xml_parser);
          unset($this->xml_parser);
          $this->reader->closeStream();
          $this->__init();
          $this->encoding = 'ISO-8859-1';
          unset($this->xml_parser);
          unset($this->reader);
          return $this->parse($path, $data, true);
        }
        else {
          return $this->addError($this->tmp_error);
        }
      }
    }
    $this->target_encoding = xml_parser_get_option($this->xml_parser, XML_OPTION_TARGET_ENCODING);
    xml_parser_free($this->xml_parser);
    $this->reader->closeStream();
    unset($this->reader);
    return $this->done();
  }
  
  /*  */
  
  function initXMLParser() {
    if (!isset($this->xml_parser)) {
      $enc = preg_match('/^(utf\-8|iso\-8859\-1|us\-ascii)$/i', $this->getEncoding(), $m) ? $m[1] : 'UTF-8';
      $parser = xml_parser_create_ns($enc, '');
      xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
      xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
      xml_set_element_handler($parser, 'open', 'close');
      xml_set_character_data_handler($parser, 'cdata');
      xml_set_start_namespace_decl_handler($parser, 'nsDecl');
      xml_set_object($parser, $this);
      $this->xml_parser =& $parser;
    }
  }

  /*  */
  
  function getEncoding($src = 'config') {
    if ($src == 'parser') {
      return $this->target_encoding;
    }
    elseif (($src == 'config') && $this->encoding) {
      return $this->encoding;
    }
    return $this->reader->getEncoding();
  }
  
  /*  */
  
  function getTriples() {
    return $this->v('triples', array());
  }
  
  function countTriples() {
    return $this->t_count;
  }

  /*  */
  
  function pushS(&$s) {
    $s['pos'] = $this->s_count;
    $this->s_stack[$this->s_count] = $s;
    $this->s_count++;
  }
  
  function popS(){/* php 4.0.x-safe */
    $r = array();
    $this->s_count--;
    for ($i = 0, $i_max = $this->s_count; $i < $i_max; $i++) {
      $r[$i] = $this->s_stack[$i];
    }
    $this->s_stack = $r;
  }
  
  function updateS($s) {
    $this->s_stack[$s['pos']] = $s;
  }
  
  function getParentS() {
    return ($this->s_count && isset($this->s_stack[$this->s_count - 1])) ? $this->s_stack[$this->s_count - 1] : false;
  }
  
  function getParentXBase() {
    if ($p = $this->getParentS()) {
      return isset($p['p_x_base']) && $p['p_x_base'] ? $p['p_x_base'] : (isset($p['x_base']) ? $p['x_base'] : '');
    }
    return $this->x_base;
  }

  function getParentXLang() {
    if ($p = $this->getParentS()) {
      return isset($p['p_x_lang']) && $p['p_x_lang'] ? $p['p_x_lang'] : (isset($p['x_lang']) ? $p['x_lang'] : '');
    }
    return $this->x_lang;
  }

  /*  */
  
  function addT($s, $p, $o, $s_type, $o_type, $o_dt = '', $o_lang = '') {
    //echo "-----\nadding $s / $p / $o\n-----\n";
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

  function reify($t, $s, $p, $o, $s_type, $o_type, $o_dt = '', $o_lang = '') {
    $this->addT($t, $this->rdf.'type', $this->rdf.'Statement', 'uri', 'uri');
    $this->addT($t, $this->rdf.'subject', $s, 'uri', $s_type);
    $this->addT($t, $this->rdf.'predicate', $p, 'uri', 'uri');
    $this->addT($t, $this->rdf.'object', $o, 'uri', $o_type, $o_dt, $o_lang);
  }
  
  /*  */
  
  function open($p, $t, $a) {
    //echo "state is $this->state\n";
    //echo "opening $t\n";
    switch($this->state) {
      case 0: return $this->h0Open($t, $a);
      case 1: return $this->h1Open($t, $a);
      case 2: return $this->h2Open($t, $a);
      case 4: return $this->h4Open($t, $a);
      case 5: return $this->h5Open($t, $a);
      case 6: return $this->h6Open($t, $a);
      default: $this->addError('open() called at state ' . $this->state . ' in '.$t);
    }
  }

  function close($p, $t) {
    //echo "state is $this->state\n";
    //echo "closing $t\n";
    switch($this->state){
      case 1: return $this->h1Close($t);
      case 2: return $this->h2Close($t);
      case 3: return $this->h3Close($t);
      case 4: return $this->h4Close($t);
      case 5: return $this->h5Close($t);
      case 6: return $this->h6Close($t);
      default: $this->addError('close() called at state ' . $this->state . ' in '.$t);
    }
  }

  function cdata($p, $d) {
    //echo "state is $this->state\n";
    //echo "cdata\n";
    switch($this->state){
      case 4: return $this->h4Cdata($d);
      case 6: return $this->h6Cdata($d);
      default: return false;
    }
  }
  
  function nsDecl($p, $prf, $uri) {
    $this->nsp[$uri] = isset($this->nsp[$uri]) ? $this->nsp[$uri] : $prf;
  }

  /*  */
  
  function h0Open($t, $a) {
    $this->x_lang = $this->v($this->xml.'lang', $this->x_lang, $a);
    $this->x_base = $this->calcURI($this->v($this->xml.'base', $this->x_base, $a));
    $this->state = 1;
    if ($t !== $this->rdf.'RDF') {
      $this->h1Open($t, $a);
    }
  }
  
  /*  */

  function h1Open($t, $a) {
    $s = array(
      'x_base' => isset($a[$this->xml.'base']) ? $this->calcURI($a[$this->xml.'base']) : $this->getParentXBase(), 
      'x_lang' => isset($a[$this->xml.'lang']) ? $a[$this->xml.'lang'] : $this->getParentXLang(),
      'li_count' => 0,
    );
    /* ID */
    if (isset($a[$this->rdf.'ID'])) {
      $s['type'] = 'uri';
      $s['value'] = $this->calcURI('#'.$a[$this->rdf.'ID'], $s['x_base']);
    }
    /* about */
    elseif (isset($a[$this->rdf.'about'])) {
      $s['type'] = 'uri';
      $s['value'] = $this->calcURI($a[$this->rdf.'about'], $s['x_base']);
    }
    /* bnode */
    else {
      $s['type'] = 'bnode';
      if (isset($a[$this->rdf.'nodeID'])) {
        $s['value'] = '_:'.$a[$this->rdf.'nodeID'];
      }
      else {
        $s['value'] = $this->createBnodeID();
      }
    }
    /* sub-node */
    if ($this->state === 4) {
      $sup_s = $this->getParentS();
      /* new collection */
      if (isset($sup_s['o_is_coll']) && $sup_s['o_is_coll']) {
        $coll = array('value' => $this->createBnodeID(), 'type' => 'bnode', 'is_coll' => true, 'x_base' => $s['x_base'], 'x_lang' => $s['x_lang']);
        $this->addT($sup_s['value'], $sup_s['p'], $coll['value'], $sup_s['type'], $coll['type']);
        $this->addT($coll['value'], $this->rdf . 'first', $s['value'], $coll['type'], $s['type']);
        $this->pushS($coll);
      }
      /* new entry in existing coll */
      elseif (isset($sup_s['is_coll']) && $sup_s['is_coll']) {
        $coll = array('value' => $this->createBnodeID(), 'type' => 'bnode', 'is_coll' => true, 'x_base' => $s['x_base'], 'x_lang' => $s['x_lang']);
        $this->addT($sup_s['value'], $this->rdf . 'rest', $coll['value'], $sup_s['type'], $coll['type']);
        $this->addT($coll['value'], $this->rdf . 'first', $s['value'], $coll['type'], $s['type']);
        $this->pushS($coll);
      }
      /* normal sub-node */
      elseif(isset($sup_s['p']) && $sup_s['p']) {
        $this->addT($sup_s['value'], $sup_s['p'], $s['value'], $sup_s['type'], $s['type']);
      }
    }
    /* typed node */
    if ($t !== $this->rdf.'Description') {
      $this->addT($s['value'], $this->rdf.'type', $t, $s['type'], 'uri');
    }
    /* (additional) typing attr */
    if (isset($a[$this->rdf.'type'])) {
      $this->addT($s['value'], $this->rdf.'type', $a[$this->rdf.'type'], $s['type'], 'uri');
    }
    /* Seq|Bag|Alt */
    if (in_array($t, array($this->rdf.'Seq', $this->rdf.'Bag', $this->rdf.'Alt'))) {
      $s['is_con'] = true;
    }
    /* any other attrs (skip rdf and xml, except rdf:_, rdf:value, rdf:Seq) */
    foreach($a as $k => $v) {
      if (((strpos($k, $this->xml) === false) && (strpos($k, $this->rdf) === false)) || preg_match('/(\_[0-9]+|value|Seq|Bag|Alt|Statement|Property|List)$/', $k)) {
        if (strpos($k, ':')) {
          $this->addT($s['value'], $k, $v, $s['type'], 'literal', '', $s['x_lang']);
        }
      }
    }
    $this->pushS($s);
    $this->state = 2;
  }

  /*  */

  function h2Open($t, $a) {
    $s = $this->getParentS();
    foreach (array('p_x_base', 'p_x_lang', 'p_id', 'o_is_coll') as $k) {
      unset($s[$k]);
    }
    /* base */
    if (isset($a[$this->xml.'base'])) {
      $s['p_x_base'] = $this->calcURI($a[$this->xml.'base'], $s['x_base']);
    }
    $b = isset($s['p_x_base']) && $s['p_x_base'] ? $s['p_x_base'] : $s['x_base'];
    /* lang */
    if (isset($a[$this->xml.'lang'])) {
      $s['p_x_lang'] = $a[$this->xml.'lang'];
    }
    $l = isset($s['p_x_lang']) && $s['p_x_lang'] ? $s['p_x_lang'] : $s['x_lang'];
    /* adjust li */
    if ($t === $this->rdf.'li') {
      $s['li_count']++;
      $t = $this->rdf.'_'.$s['li_count'];
    }
    /* set p */
    $s['p'] = $t;
		/* reification */
    if (isset($a[$this->rdf.'ID'])) {
      $s['p_id'] = $a[$this->rdf.'ID'];
    }
    $o = array('value' => '', 'type' => '', 'x_base' => $b, 'x_lang' => $l);
    /* resource/rdf:resource */
    if (isset($a['resource'])) {
      $a[$this->rdf . 'resource'] = $a['resource'];
      unset($a['resource']);
    }
    if (isset($a[$this->rdf.'resource'])) {
      $o['value'] = $this->calcURI($a[$this->rdf.'resource'], $b);
      $o['type'] = 'uri';
      $this->addT($s['value'], $s['p'], $o['value'], $s['type'], $o['type']);
      /* type */
      if (isset($a[$this->rdf.'type'])) {
        $this->addT($o['value'], $this->rdf.'type', $a[$this->rdf.'type'], 'uri', 'uri');
      }
      /* reification */
      if (isset($s['p_id'])) {
        $this->reify($this->calcURI('#'.$s['p_id'], $b), $s['value'], $s['p'], $o['value'], $s['type'], $o['type']);
        unset($s['p_id']);
      }
      $this->state = 3;
    }
    /* named bnode */
    elseif (isset($a[$this->rdf.'nodeID'])) {
      $o['value'] = '_:' . $a[$this->rdf.'nodeID'];
      $o['type'] = 'bnode';
      $this->addT($s['value'], $s['p'], $o['value'], $s['type'], $o['type']);
      $this->state = 3;
      /* reification */
      if (isset($s['p_id'])) {
        $this->reify($this->calcURI('#'.$s['p_id'], $b), $s['value'], $s['p'], $o['value'], $s['type'], $o['type']);
      }
    }
    /* parseType */
    elseif (isset($a[$this->rdf.'parseType'])) {
      if ($a[$this->rdf.'parseType'] === 'Literal') {
        $s['o_xml_level'] = 0;
        $s['o_xml_data'] = '';
        $s['p_xml_literal_level'] = 0;
        $s['ns'] = array();
        $this->state = 6;
      }
      elseif ($a[$this->rdf.'parseType'] === 'Resource') {
        $o['value'] = $this->createBnodeID();
        $o['type'] = 'bnode';
        $o['has_closing_tag'] = 0;
        $this->addT($s['value'], $s['p'], $o['value'], $s['type'], $o['type']);
        $this->pushS($o);
        /* reification */
        if (isset($s['p_id'])) {
          $this->reify($this->calcURI('#'.$s['p_id'], $b), $s['value'], $s['p'], $o['value'], $s['type'], $o['type']);
          unset($s['p_id']);
        }
        $this->state = 2;
      }
      elseif ($a[$this->rdf.'parseType'] === 'Collection') {
        $s['o_is_coll'] = true;
        $this->state = 4;
      }
    }
    /* sub-node or literal */
    else {
      $s['o_cdata'] = '';
      if (isset($a[$this->rdf.'datatype'])) {
        $s['o_datatype'] = $a[$this->rdf.'datatype'];
      }
      $this->state = 4;
    }
    /* any other attrs (skip rdf and xml) */
    foreach($a as $k => $v) {
      if (((strpos($k, $this->xml) === false) && (strpos($k, $this->rdf) === false)) || preg_match('/(\_[0-9]+|value)$/', $k)) {
        if (strpos($k, ':')) {
          if (!$o['value']) {
            $o['value'] = $this->createBnodeID();
            $o['type'] = 'bnode';
            $this->addT($s['value'], $s['p'], $o['value'], $s['type'], $o['type']);
          }
          /* reification */
          if (isset($s['p_id'])) {
            $this->reify($this->calcURI('#'.$s['p_id'], $b), $s['value'], $s['p'], $o['value'], $s['type'], $o['type']);
            unset($s['p_id']);
          }
          $this->addT($o['value'], $k, $v, $o['type'], 'literal');
          $this->state = 3;
        }
      }
    }
    $this->updateS($s);
  }

  /*  */

  function h4Open($t, $a) {
    return $this->h1Open($t, $a);
  }
  
  /*  */

  function h5Open($t, $a) {
    $this->state = 4;
    return $this->h4Open($t, $a);
  }
  
  /*  */
  
  function h6Open($t, $a) {
    $s = $this->getParentS();
    $data = isset($s['o_xml_data']) ? $s['o_xml_data'] : '';
    $ns = isset($s['ns']) ? $s['ns'] : array();
    $parts = $this->splitURI($t);
    if (count($parts) === 1) {
      $data .= '<'.$t;
    }
    else {
      $ns_uri = $parts[0];
      $name = $parts[1];
      if (!isset($this->nsp[$ns_uri])) {
        foreach ($this->nsp as $tmp1 => $tmp2) {
          if (strpos($t, $tmp1) === 0) {
            $ns_uri = $tmp1;
            $name = substr($t, strlen($tmp1));
            break;
          }
        }
      }
      $nsp = $this->nsp[$ns_uri];
      $data .= $nsp ? '<' . $nsp . ':' . $name : '<' . $name;
      /* ns */
      if (!isset($ns[$nsp.'='.$ns_uri]) || !$ns[$nsp.'='.$ns_uri]) {
        $data .= $nsp ? ' xmlns:'.$nsp.'="'.$ns_uri.'"' : ' xmlns="'.$ns_uri.'"';
        $ns[$nsp.'='.$ns_uri] = true;
        $s['ns'] = $ns;
      }
    }
    foreach ($a as $k => $v) {
      $parts = $this->splitURI($k);
      if (count($parts) === 1) {
        $data .= ' '.$k.'="'.$v.'"';
      }
      else {
        $ns_uri = $parts[0];
        $name = $parts[1];
        $nsp = $this->nsp[$ns_uri];
        $data .= $nsp ? ' '.$nsp.':'.$name.'="'.$v.'"' : ' '.$name.'="'.$v.'"' ;
      }
    }
    $data .= '>';
    $s['o_xml_data'] = $data;
    $s['o_xml_level'] = isset($s['o_xml_level']) ? $s['o_xml_level'] + 1 : 1;
    if ($t == $s['p']) {/* xml container prop */
      $s['p_xml_literal_level'] = isset($s['p_xml_literal_level']) ? $s['p_xml_literal_level'] + 1 : 1;
    }
    $this->updateS($s);
  }

  /*  */

  function h1Close($t) {/* end of doc */
    $this->state = 0;
  }
  
  /*  */
  
  function h2Close($t) {/* expecting a prop, getting a close */
    if ($s = $this->getParentS()) {
      $has_closing_tag = (isset($s['has_closing_tag']) && !$s['has_closing_tag']) ? 0 : 1;
      $this->popS();
      $this->state = 5;
      if ($s = $this->getParentS()) {/* new s */
        if (!isset($s['p']) || !$s['p']) {/* p close after collection|parseType=Resource|node close after p close */
          $this->state = $this->s_count ? 4 : 1;
          if (!$has_closing_tag) {
            $this->state = 2;
          }
        }
        elseif (!$has_closing_tag) {
          $this->state = 2;
        }
      }
    }
  }
  
  /*  */
  
  function h3Close($t) {/* p close */
    $this->state = 2;
  }
  
  /*  */
  
  function h4Close($t) {/* empty p | pClose after cdata | pClose after collection */
    if ($s = $this->getParentS()) {
      $b = isset($s['p_x_base']) && $s['p_x_base'] ? $s['p_x_base'] : (isset($s['x_base']) ? $s['x_base'] : '');
      if (isset($s['is_coll']) && $s['is_coll']) {
        $this->addT($s['value'], $this->rdf . 'rest', $this->rdf . 'nil', $s['type'], 'uri');
        /* back to collection start */
        while ((!isset($s['p']) || ($s['p'] != $t))) {
          $sub_s = $s;
          $this->popS();
          $s = $this->getParentS();
        }
        /* reification */
        if (isset($s['p_id']) && $s['p_id']) {
          $this->reify($this->calcURI('#'.$s['p_id'], $b), $s['value'], $s['p'], $sub_s['value'], $s['type'], $sub_s['type']);
        }
        unset($s['p']);
        $this->updateS($s);
      }
      else {
        $dt = isset($s['o_datatype']) ? $s['o_datatype'] : '';
        $l = isset($s['p_x_lang']) && $s['p_x_lang'] ? $s['p_x_lang'] : (isset($s['x_lang']) ? $s['x_lang'] : '');
        $o = array('type' => 'literal', 'value' => $s['o_cdata']);
        $this->addT($s['value'], $s['p'], $o['value'], $s['type'], $o['type'], $dt, $l);
        /* reification */
        if (isset($s['p_id']) && $s['p_id']) {
          $this->reify($this->calcURI('#'.$s['p_id'], $b), $s['value'], $s['p'], $o['value'], $s['type'], $o['type'], $dt, $l);
        }
        unset($s['o_cdata']);
        unset($s['o_datatype']);
        unset($s['p']);
        $this->updateS($s);
      }
      $this->state = 2;
    }
  }
  
  /*  */
  
  function h5Close($t) {/* p close */
    if ($s = $this->getParentS()) {
      unset($s['p']);
      $this->updateS($s);
      $this->state = 2;
    }
  }

  /*  */

  function h6Close($t) {
    if ($s = $this->getParentS()) {
      $l = isset($s['p_x_lang']) && $s['p_x_lang'] ? $s['p_x_lang'] : (isset($s['x_lang']) ? $s['x_lang'] : '');
      $data = $s['o_xml_data'];
      $level = $s['o_xml_level'];
      if ($level === 0) {/* pClose */
        $this->addT($s['value'], $s['p'], trim($data, ' '), $s['type'], 'literal', $this->rdf.'XMLLiteral', $l);
        unset($s['o_xml_data']);
        $this->state = 2;
      }
      else {
        $parts = $this->splitURI($t);
        if (count($parts) == 1) {
          $data .= '</'.$t.'>';
        }
        else {
          $ns_uri = $parts[0];
          $name = $parts[1];
          if (!isset($this->nsp[$ns_uri])) {
            foreach ($this->nsp as $tmp1 => $tmp2) {
              if (strpos($t, $tmp1) === 0) {
                $ns_uri = $tmp1;
                $name = substr($t, strlen($tmp1));
                break;
              }
            }
          }
          $nsp = $this->nsp[$ns_uri];
          $data .= $nsp ? '</'.$nsp.':'.$name.'>' : '</'.$name.'>';
        }
        $s['o_xml_data'] = $data;
        $s['o_xml_level'] = $level - 1;
        if ($t == $s['p']) {/* xml container prop */
          $s['p_xml_literal_level']--;
        }
      }
      $this->updateS($s);
    }
  }
  
  /*  */
  
  function h4Cdata($d) {
    if ($s = $this->getParentS()) {
      $s['o_cdata'] = isset($s['o_cdata']) ? $s['o_cdata'] . $d : $d;
      $this->updateS($s);
    }
  }
  
  /*  */

  function h6Cdata($d) {
    if ($s = $this->getParentS()) {
      if (isset($s['o_xml_data']) || preg_match("/[\n\r]/", $d) || trim($d)) {
        $d = htmlspecialchars($d, ENT_NOQUOTES);
        $s['o_xml_data'] = isset($s['o_xml_data']) ? $s['o_xml_data'] . $d : $d;
      }
      $this->updateS($s);
    }
  }
  
  /*  */
  
}
