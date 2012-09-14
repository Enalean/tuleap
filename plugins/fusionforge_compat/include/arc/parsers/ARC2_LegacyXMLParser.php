<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 Legaxy XML Parser
author:   Benjamin Nowack
version:  2008-10-04 (Fix: nsDecl led to warnings when uri was an array.)
*/

ARC2::inc('Class');

class ARC2_LegacyXMLParser extends ARC2_Class {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_LegacyXMLParser($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {/* reader */
    parent::__init();
    $this->encoding = $this->v('encoding', false, $this->a);
    $this->state = 0;
    $this->x_base = $this->base;
    $this->xml = 'http://www.w3.org/XML/1998/namespace';
    $this->rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    $this->nsp = array($this->xml => 'xml', $this->rdf => 'rdf');
    $this->allowCDataNodes = 1;
    $this->target_encoding = '';
    $this->keep_cdata_ws = $this->v('keep_cdata_whitespace', 0, $this->a);
  }
  
  /*  */

  function setReader(&$reader) {
    $this->reader =& $reader;
  }

  function parse($path, $data = '', $iso_fallback = false) {
    $this->nodes = array();
    $this->node_count = 0;
    $this->level = 0;
    /* reader */
    if (!$this->v('reader')) {
      ARC2::inc('Reader');
      $this->reader = & new ARC2_Reader($this->a, $this);
    }
    $this->reader->setAcceptHeader('Accept: application/xml; q=0.9, */*; q=0.1');
    $this->reader->activate($path, $data);
    $this->x_base = isset($this->a['base']) && $this->a['base'] ? $this->a['base'] : $this->reader->base;
    $this->base = $this->x_base;
    $this->doc_url = $this->reader->base;
    /* xml parser */
    $this->initXMLParser();
    /* parse */
    $first = true;
    while ($d = $this->reader->readStream(1)) {
      if ($iso_fallback && $first) {
        $d = '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\n" . preg_replace('/^\<\?xml [^\>]+\?\>\s*/s', '', $d);
      }
      if (!xml_parse($this->xml_parser, $d, false)) {
        $error_str = xml_error_string(xml_get_error_code($this->xml_parser));
        $line = xml_get_current_line_number($this->xml_parser);
        if (!$iso_fallback && preg_match("/Invalid character/i", $error_str)) {
          xml_parser_free($this->xml_parser);
          unset($this->xml_parser);
          $this->reader->closeStream();
          unset($this->reader);
          $this->__init();
          $this->encoding = 'ISO-8859-1';
          $this->initXMLParser();
          return $this->parse($path, $data, true);
        }
        else {
          return $this->addError('XML error: "' . $error_str . '" at line ' . $line . ' (parsing as ' . $this->getEncoding() . ')');
        }
      }
      $first = false;
    }
    $this->target_encoding = xml_parser_get_option($this->xml_parser, XML_OPTION_TARGET_ENCODING);
    xml_parser_free($this->xml_parser);
    $this->reader->closeStream();
    unset($this->reader);
    return $this->done();
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
  
  function done() {
  
  }
  
  /*  */
  
  function getStructure() {
    return array('nodes' => $this->v('nodes', array()));
  }
  
  /*  */

  function getNodeIndex(){
    if (!isset($this->node_index)) {
      /* index by parent */
      $index = array();
      for ($i = 0, $i_max = count($this->nodes); $i < $i_max; $i++) {
        $node = $this->nodes[$i];
        $node['id'] = $i;
        $node['doc_base'] = $this->base;
        if (isset($this->doc_url)) $node['doc_url'] = $this->doc_url;
        $this->updateNode($node);
        $p_id = $node['p_id'];
        if (!isset($index[$p_id])) {
          $index[$p_id] = array();
        }
        $index[$p_id][$node['pos']] = $node;
      }
      $this->node_index = $index;
    }
    return $this->node_index;
  }

  function getNodes() {
    return $this->nodes;
  }
  
  function getSubNodes($n) {
    return $this->v($n['id'], array(), $this->getNodeIndex());
  }
  
  function getNodeContent($n, $outer = 0, $trim = 1) {
    //echo '<pre>' . htmlspecialchars(print_r($n, 1)) . '</pre>';
    if ($n['tag'] == 'cdata') {
      $r = $n['a']['value'];
    }
    else {
      $r = '';
      if ($outer) {
        $r .= '<' . $n['tag'];
        asort($n['a']);
        if (isset($n['a']['xmlns']) && $n['a']['xmlns']['']) {
          $r .= ' xmlns="' . $n['a']['xmlns'][''] . '"';
        }
        foreach ($n['a'] as $a => $val) {
          $r .= preg_match('/^[^\s]+$/', $a) && !is_array($val) ? ' ' . $a . '="' . addslashes($val) . '"' : '';
        }
        $r .= $n['empty'] ? '/>' : '>';
      }
      if (!$n['empty']) {
        $r .= $this->v('cdata', '', $n);
        $sub_nodes = $this->getSubNodes($n);
        foreach ($sub_nodes as $sub_n) {
          $r .= $this->getNodeContent($sub_n, 1, 0);
        }
        if ($outer) {
          $r .= '</' . $n['tag'] . '>';
        }
      }
    }
    return ($trim && !$this->keep_cdata_ws) ? trim($r) : $r;
  }

  /*  */
  
  function pushNode($n) {
    $n['id'] = $this->node_count;
    $this->nodes[$this->node_count] = $n;
    $this->node_count++;
  }
  
  function getCurNode($t = '') {
    $i = 1;
    do {
      $r = $this->node_count ? $this->nodes[$this->node_count - $i] : 0;
      $found = (!$t || ($r['tag'] == $t)) ? 1 : 0;
      $i++;
    } while (!$found && isset($this->nodes[$this->node_count - $i]));
    return $r;
  }
  
  function updateNode($node) {/* php4-save */
    $this->nodes[$node['id']] = $node;
  }

  /*  */

  function initXMLParser() {
    if (!isset($this->xml_parser)) {
      $enc = preg_match('/^(utf\-8|iso\-8859\-1|us\-ascii)$/i', $this->getEncoding(), $m) ? $m[1] : 'UTF-8';
      $parser = xml_parser_create_ns($enc, '');
      xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
      xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
      xml_set_element_handler($parser, 'open', 'close');
      xml_set_character_data_handler($parser, 'cData');
      xml_set_start_namespace_decl_handler($parser, 'nsDecl');
      xml_set_object($parser, $this);
      $this->xml_parser =& $parser;
    }
  }

  /*  */
  
  function open($p, $t, $a) {
    $t_exact = $t;
    //echo "<br />\n".'opening '.$t . ' ' . print_r($a, 1); flush();
    //echo "<br />\n".'opening '.$t; flush();
    $t = strpos($t, ':') ? $t : strtolower($t);
    /* base check */
    $base = '';
    if (($t == 'base') && isset($a['href'])) {
      $this->base = $a['href'];
      $base = $a['href'];
    }
    /* URIs */
    foreach (array('href', 'src', 'id') as $uri_a) {
      if (isset($a[$uri_a])) {
        $a[$uri_a . ' uri'] = ($uri_a == 'id') ? $this->calcURI('#'.$a[$uri_a]) : $this->calcURI($a[$uri_a]);
      }
    }
    /* ns */
    if ($a) {
      foreach ($a as $k => $v) {
        if (strpos($k, 'xmlns') === 0) {
          $this->nsDecl($p, trim(substr($k, 5), ':'), $v);
        }
      }
    }
    /* node */
    $node = array(
      'tag' => $t,
      'tag_exact' => $t_exact,
      'a' => $a, 
      'level' => $this->level, 
      'pos' => 0,
      'p_id' => $this->node_count-1,
      'state' => 'open',
      'empty' => 0,
      'cdata' =>''
    );
    if ($base) {
      $node['base'] = $base;
    }
    /* parent/sibling */
    if ($this->node_count) {
      $l = $this->level;
      $prev_node = $this->getCurNode();
      if ($prev_node['level'] == $l) {
        $node['p_id'] = $prev_node['p_id'];
        $node['pos'] = $prev_node['pos']+1;
      }
      elseif($prev_node['level'] > $l) {
        while($prev_node['level'] > $l) {
          if (!isset($this->nodes[$prev_node['p_id']])) {
            //$this->addError('nesting mismatch: tag is ' . $t . ', level is ' . $l . ', prev_level is ' . $prev_node['level'] . ', prev_node p_id is ' . $prev_node['p_id']);
            break;
          }
          $prev_node = $this->nodes[$prev_node['p_id']];
        }
        $node['p_id'] = $prev_node['p_id'];
        $node['pos'] = $prev_node['pos']+1;
      }
    }
    $this->pushNode($node);
    $this->level++;
    /* cdata */
    $this->cur_cdata="";
  }

  function close($p, $t, $empty = 0) {
    //echo "<br />\n".'closing '.$t; flush();
    $node = $this->getCurNode($t);
    $node['state'] = 'closed';
    $node['empty'] = $empty;
    $this->updateNode($node);
    $this->level--;
  }

  function cData($p, $d) {
    //echo trim($d) ? "<br />\n".'cdata: ' . $d : ''; flush();
    $node = $this->getCurNode();
    if($node['state'] == 'open') {
      $node['cdata'] .= $d;
      $this->updateNode($node);
    }
    else {/* cdata is sibling of node */
      if ($this->allowCDataNodes) {
        $this->open($p, 'cdata', array('value' => $d));
        $this->close($p, 'cdata');
      }
    }
  }
  
  function nsDecl($p, $prf, $uri) {
    if (is_array($uri)) return 1;
    $this->ns[$prf] = $uri;
    $this->nsp[$uri] = isset($this->nsp[$uri]) ? $this->nsp[$uri] : $prf;
  }

  /*  */
  
}