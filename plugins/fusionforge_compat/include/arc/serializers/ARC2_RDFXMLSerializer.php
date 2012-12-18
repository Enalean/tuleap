<?php
/**
 * ARC2 RDF/XML Serializer
 *
 * @author Benjamin Nowack
 * @license <http://arc.semsol.org/license>
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version 2010-01-30
 * 
*/

ARC2::inc('RDFSerializer');

class ARC2_RDFXMLSerializer extends ARC2_RDFSerializer {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_RDFXMLSerializer($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->content_header = 'application/rdf+xml';
    $this->pp_containers = $this->v('serializer_prettyprint_containers', 0, $this->a);
    $this->default_ns = $this->v('serializer_default_ns', '', $this->a);
    $this->type_nodes = $this->v('serializer_type_nodes', 0, $this->a);
  }

  /*  */
  
  function getTerm($v, $type) {
    if (!is_array($v)) {/* uri or bnode */
      if (preg_match('/^\_\:(.*)$/', $v, $m)) {
        return ' rdf:nodeID="' . $m[1] . '"';
      }
      if ($type == 's') {
        return ' rdf:about="' . htmlspecialchars($v) . '"';
      }
      if ($type == 'p') {
        if ($pn = $this->getPName($v)) {
          return $pn;
        }
        return 0;
      }
      if ($type == 'o') {
        $v = $this->expandPName($v);
        if (!preg_match('/^[a-z0-9]{2,}\:[^\s]+$/is', $v)) return $this->getTerm(array('value' => $v, 'type' => 'literal'), $type);
        return ' rdf:resource="' . htmlspecialchars($v) . '"';
      }
      if ($type == 'datatype') {
        $v = $this->expandPName($v);
        return ' rdf:datatype="' . htmlspecialchars($v) . '"';
      }
      if ($type == 'lang') {
        return ' xml:lang="' . htmlspecialchars($v) . '"';
      }
    }
    if ($v['type'] != 'literal') {
      return $this->getTerm($v['value'], 'o');
    }
    /* literal */
    $dt = isset($v['datatype']) ? $v['datatype'] : '';
    $lang = isset($v['lang']) ? $v['lang'] : '';
    if ($dt == 'http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral') {
      return ' rdf:parseType="Literal">' . $v['value'];
    }
    elseif ($dt) {
      return $this->getTerm($dt, 'datatype') . '>' . htmlspecialchars($v['value']);
    }
    elseif ($lang) {
      return $this->getTerm($lang, 'lang') . '>' . htmlspecialchars($v['value']);
    }
    return '>' . htmlspecialchars($v['value']);
  }

  function getPName($v, $connector = ':') {
    if ($this->default_ns && (strpos($v, $this->default_ns) === 0)) {
      $pname = substr($v, strlen($this->default_ns));
      if (!preg_match('/\//', $pname)) return $pname;
    }
    return parent::getPName($v, $connector);
  }
  
  function getHead() {
    $r = '';
    $nl = "\n";
    $r .= '<?xml version="1.0" encoding="UTF-8"?>';
    $r .= $nl . '<rdf:RDF';
    $first_ns = 1;
    foreach ($this->used_ns as $v) {
      $r .= $first_ns ? ' ' : $nl . '  ';
      $r .= 'xmlns:' . $this->nsp[$v] . '="' .$v. '"';
      $first_ns = 0;
    }
    if ($this->default_ns) {
      $r .= $first_ns ? ' ' : $nl . '  ';
      $r .= 'xmlns="' . $this->default_ns . '"';
    }
    $r .= '>';
    return $r;
  }
  
  function getFooter() {
    $r = '';
    $nl = "\n";
    $r .= $nl . $nl . '</rdf:RDF>';
    return $r;
  }
  
  function getSerializedIndex($index, $raw = 0) {
    $r = '';
    $nl = "\n";
    foreach ($index as $raw_s => $ps) {
      $r .= $r ? $nl . $nl : '';
      $s = $this->getTerm($raw_s, 's');
      $tag = 'rdf:Description';
      list($tag, $ps) = $this->getNodeTag($ps);
      $sub_ps = 0;
      /* pretty containers */
      if ($this->pp_containers && ($ctag = $this->getContainerTag($ps))) {
        $tag = 'rdf:' . $ctag;
        list($ps, $sub_ps) = $this->splitContainerEntries($ps);
      }
      $r .= '  <' . $tag . '' .$s . '>';
      $first_p = 1;
      foreach ($ps as $p => $os) {
        if (!$os) continue;
        if ($p = $this->getTerm($p, 'p')) {
          $r .= $nl . str_pad('', 4);
          $first_o = 1;
          if (!is_array($os)) {/* single literal o */
            $os = array(array('value' => $os, 'type' => 'literal'));
          }
          foreach ($os as $o) {
            $o = $this->getTerm($o, 'o');
            $r .= $first_o ? '' : $nl . '    ';
            $r .= '<' . $p;
            $r .= $o;
            $r .= preg_match('/\>/', $o) ? '</' . $p . '>' : '/>'; 
            $first_o = 0;
          }
          $first_p = 0;
        }
      }
      $r .= $r ? $nl . '  </' . $tag . '>' : '';
      if ($sub_ps) $r .= $nl . $nl . $this->getSerializedIndex(array($raw_s => $sub_ps), 1);
    }
    if ($raw) {
      return $r;
    }
    return $this->getHead() . $nl . $nl . $r . $this->getFooter();
  }

  function getNodeTag($ps) {
    if (!$this->type_nodes) return array('rdf:Description', $ps);
    $rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    $types = $this->v($rdf . 'type', array(), $ps);
    if (!$types) return array('rdf:Description', $ps);
    $type = array_shift($types);
    $ps[$rdf . 'type'] = $types;
    if (!is_array($type)) $type = array('value' => $type);
    return array($this->getPName($type['value']), $ps);
  }

  /*  */

  function getContainerTag($ps) {
    $rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    if (!isset($ps[$rdf . 'type'])) return '';
    $types = $ps[$rdf . 'type'];
    foreach ($types as $type) {
      if (!in_array($type['value'], array($rdf . 'Bag', $rdf . 'Seq', $rdf . 'Alt'))) return '';
      return str_replace($rdf, '', $type['value']);
    }
  }

  function splitContainerEntries($ps) {
    $rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    $items = array();
    $rest = array();
    foreach ($ps as $p => $os) {
      $p_short = str_replace($rdf, '', $p);
      if ($p_short === 'type') continue;
      if (preg_match('/^\_([0-9]+)$/', $p_short, $m)) {
        $items = array_merge($items, $os);
      }
      else {
        $rest[$p] = $os;
      }
    }
    if ($items) return array(array($rdf . 'li' => $items), $rest);
    return array($rest, 0);
  }

  /*  */
}
