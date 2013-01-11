<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 Atom Parser
author:   Benjamin Nowack
version:  2009-04-21 (Addition: support for link types)
*/

ARC2::inc('LegacyXMLParser');

class ARC2_AtomParser extends ARC2_LegacyXMLParser {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_AtomParser($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {/* reader */
    parent::__init();
    $this->triples = array();
    $this->target_encoding = '';
    $this->t_count = 0;
    $this->added_triples = array();
    $this->skip_dupes = false;
    $this->bnode_prefix = $this->v('bnode_prefix', 'arc'.substr(md5(uniqid(rand())), 0, 4).'b', $this->a);
    $this->bnode_id = 0;
    $this->cache = array();
    $this->allowCDataNodes = 0;
  }
  
  /*  */
  
  function done() {
    $this->extractRDF();
  }
  
  /*  */
  
  function setReader(&$reader) {
    $this->reader =& $reader;
  }
  
  function createBnodeID(){
    $this->bnode_id++;
    return '_:' . $this->bnode_prefix . $this->bnode_id;
  }
  
  function addT($t) {
    //if (!isset($t['o_datatype']))
    if ($this->skip_dupes) {
      //$h = md5(print_r($t, 1));
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

  function getTriples() {
    return $this->v('triples', array());
  }

  function countTriples() {
    return $this->t_count;
  }
  
  function getSimpleIndex($flatten_objects = 1, $vals = '') {
    return ARC2::getSimpleIndex($this->getTriples(), $flatten_objects, $vals);
  }

  /*  */

  function extractRDF() {
    $index = $this->getNodeIndex();
    //print_r($index);
    $this->rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    $this->atom = 'http://www.w3.org/2005/Atom';
    $this->rss = 'http://purl.org/rss/1.0/';
    $this->dc = 'http://purl.org/dc/elements/1.1/';
    $this->sioc = 'http://rdfs.org/sioc/ns#';
    $this->dct = 'http://purl.org/dc/terms/';
    $this->content = 'http://purl.org/rss/1.0/modules/content/';
    $this->enc = 'http://purl.oclc.org/net/rss_2.0/enc#';
    $this->mappings = array(
      'feed' => $this->rss . 'channel',
      'entry' => $this->rss . 'item',
      'title' => $this->rss . 'title',
      'link' => $this->rss . 'link',
      'summary' => $this->rss . 'description',
      'content' => $this->content . 'encoded',
      'id' => $this->dc . 'identifier',
      'author' => $this->dc . 'creator',
      'category' => $this->dc . 'subject',
      'updated' => $this->dc . 'date',
      'source' => $this->dc . 'source',
    );
    $this->dt_props = array(
      $this->dc . 'identifier',
      $this->rss . 'link'
    );
    foreach ($index as $p_id => $nodes) {
      foreach ($nodes as $pos => $node) {
        $tag = $this->v('tag', '', $node);
        if ($tag == 'feed') {
          $struct = $this->extractChannel($index[$node['id']]);
          $triples = ARC2::getTriplesFromIndex($struct);
          foreach ($triples as $t) {
            $this->addT($t);
          }
        }
        elseif ($tag == 'entry') {
          $struct = $this->extractItem($index[$node['id']]);
          $triples = ARC2::getTriplesFromIndex($struct);
          foreach ($triples as $t) {
            $this->addT($t);
          }
        }
      }
    }
  }
  
  function extractChannel($els) {
    list($props, $sub_index) = $this->extractProps($els, 'channel');
    $uri = $props[$this->rss . 'link'][0]['value'];
    return ARC2::getMergedIndex(array($uri => $props), $sub_index);
  }
  
  function extractItem($els) {
    list($props, $sub_index) = $this->extractProps($els, 'item');
    $uri = $props[$this->rss . 'link'][0]['value'];
    return ARC2::getMergedIndex(array($uri => $props), $sub_index);
  }
  
  function extractProps($els, $container) {
    $r = array($this->rdf . 'type' => array(array('value' => $this->rss . $container, 'type' => 'uri')));
    $sub_index = array();
    foreach ($els as $info) {
      /* key */
      $tag = $info['tag'];
      if (!preg_match('/^[a-z0-9]+\:/i', $tag)) {
        $k = isset($this->mappings[$tag]) ? $this->mappings[$tag] : '';
      }
      elseif (isset($this->mappings[$tag])) {
        $k = $this->mappings[$tag];
      }
      else {/* qname */
        $k = $this->expandPName($tag);
      }
      //echo $k . "\n";
      if (($container == 'channel') && ($k == $this->rss . 'item')) continue;
      /* val */
      $v = trim($info['cdata']);
      if (!$v) $v = $this->v('href uri', '', $info['a']);
      /* prop */
      if ($k) {
        /* content handling */
        if (in_array($k, array($this->rss . 'description', $this->content . 'encoded'))) {
          $v = $this->getNodeContent($info);
        }
        /* source handling */
        elseif ($k == $this->dc . 'source') {
          $sub_nodes = $this->node_index[$info['id']];
          foreach ($sub_nodes as $sub_pos => $sub_info) {
            if ($sub_info['tag'] == 'id') {
              $v = trim($sub_info['cdata']);
            }
          }
        }
        /* link handling */
        elseif ($k == $this->rss . 'link') {
          if ($link_type = $this->v('type', '', $info['a'])) {
            $k2 = $this->dc . 'format';
            if (!isset($sub_index[$v])) $sub_index[$v] = array();
            if (!isset($sub_index[$v][$k2])) $sub_index[$v][$k2] = array();
            $sub_index[$v][$k2][] = array('value' => $link_type, 'type' => 'literal');
          }
        }
        /* author handling */
        elseif ($k == $this->dc . 'creator') {
          $sub_nodes = $this->node_index[$info['id']];
          foreach ($sub_nodes as $sub_pos => $sub_info) {
            if ($sub_info['tag'] == 'name') {
              $v = trim($sub_info['cdata']);
            }
            if ($sub_info['tag'] == 'uri') {
              $k2 = $this->sioc . 'has_creator';
              $v2 = trim($sub_info['cdata']);
              if (!isset($r[$k2])) $r[$k2] = array();
              $r[$k2][] = array('value' => $v2, 'type' => 'uri');
            }
          }
        }
        /* date handling */
        elseif (in_array($k, array($this->dc . 'date', $this->dct . 'modified'))) {
          if (!preg_match('/^[0-9]{4}/', $v) && ($sub_v = strtotime($v)) && ($sub_v != -1)) {
            $tz = date('Z', $sub_v); /* timezone offset */
            $sub_v -= $tz; /* utc */
            $v = date('Y-m-d\TH:i:s\Z', $sub_v);
          }
        }
        /* tag handling */
        elseif ($k == $this->dc . 'subject') {
          $v = $this->v('term', '', $info['a']);
        }
        /* other attributes in closed tags */
        elseif (!$v && ($info['state'] == 'closed') && $info['a']) {
          foreach ($info['a'] as $sub_k => $sub_v) {
            if (!preg_match('/(xmlns|\:|type)/', $sub_k)) {
              $v = $sub_v;
              break;
            }
          }
        }
        if (!isset($r[$k])) $r[$k] = array();
        $r[$k][] = array('value' => $v, 'type' => in_array($k, $this->dt_props) || !preg_match('/^[a-z0-9]+\:[^\s]+$/is', $v) ? 'literal' : 'uri');
      }
    }
    return array($r, $sub_index);
  }
  
  function initXMLParser() {
    if (!isset($this->xml_parser)) {
      $enc = preg_match('/^(utf\-8|iso\-8859\-1|us\-ascii)$/i', $this->getEncoding(), $m) ? $m[1] : 'UTF-8';
      $parser = xml_parser_create($enc);
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


}
