<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 RSS Parser
author:   Benjamin Nowack
version:  2008-02-10
*/

ARC2::inc('LegacyXMLParser');

class ARC2_RSSParser extends ARC2_LegacyXMLParser {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_RSSParser($a = '', &$caller) {
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
    $this->rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    $this->rss = 'http://purl.org/rss/1.0/';
    $this->dc = 'http://purl.org/dc/elements/1.1/';
    $this->dct = 'http://purl.org/dc/terms/';
    $this->content = 'http://purl.org/rss/1.0/modules/content/';
    $this->enc = 'http://purl.oclc.org/net/rss_2.0/enc#';
    $this->mappings = array(
      'channel' => $this->rss . 'channel',
      'item' => $this->rss . 'item',
      'title' => $this->rss . 'title',
      'link' => $this->rss . 'link',
      'description' => $this->rss . 'description',
      'guid' => $this->dc . 'identifier',
      'author' => $this->dc . 'creator',
      'category' => $this->dc . 'subject',
      'pubDate' => $this->dc . 'date',
      'pubdate' => $this->dc . 'date',
      'source' => $this->dc . 'source',
      'enclosure' => $this->enc . 'enclosure',
    );
    $this->dt_props = array(
      $this->dc . 'identifier',
      $this->rss . 'link'
    );
    foreach ($index as $p_id => $nodes) {
      foreach ($nodes as $pos => $node) {
        $tag = $this->v('tag', '', $node);
        if ($tag == 'channel') {
          $struct = $this->extractChannel($index[$node['id']]);
          $triples = ARC2::getTriplesFromIndex($struct);
          foreach ($triples as $t) {
            $this->addT($t);
          }
        }
        elseif ($tag == 'item') {
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
    $res = array($this->rdf . 'type' => array(array('value' => $this->rss . 'channel', 'type' => 'uri')));
    $res = array_merge($res, $this->extractProps($els, 'channel'));
    return array($res[$this->rss . 'link'][0]['value'] => $res);
  }
  
  function extractItem($els) {
    $res = array($this->rdf . 'type' => array(array('value' => $this->rss . 'item', 'type' => 'uri')));
    $res = array_merge($res, $this->extractProps($els, 'item'));
    if (isset($res[$this->rss . 'link'])) return array($res[$this->rss . 'link'][0]['value'] => $res);
    if (isset($res[$this->dc . 'identifier'])) return array($res[$this->dc . 'identifier'][0]['value'] => $res);
  }
  
  function extractProps($els, $container) {
    $res = array();
    foreach ($els as $info) {
      /* key */
      $tag = $info['tag'];
      if (!preg_match('/^[a-z0-9]+\:/i', $tag)) {
        $k = isset($this->mappings[$tag]) ? $this->mappings[$tag] : '';
      }
      else {
        $k = $tag;
      }
      if (($container == 'channel') && ($k == $this->rss . 'item')) continue;
      /* val */
      $v = $info['cdata'];
      if (!$v) $v = $this->v('url', '', $info['a']);
      if (!$v) $v = $this->v('href', '', $info['a']);
      /* prop */
      if ($k) {
        /* enclosure handling */
        if ($k == $this->enc . 'enclosure') {
          $sub_res = array();
          foreach (array('length', 'type') as $attr) {
            if ($attr_v = $this->v($attr, 0, $info['a'])) {
              $sub_res[$this->enc . $attr] = array(array('value' => $attr_v, 'type' => 'literal'));
            }
          }
          $struct[$v] = $sub_res;
        }
        /* date handling */
        if (in_array($k, array($this->dc . 'date', $this->dct . 'modified'))) {
          if (!preg_match('/^[0-9]{4}/', $v) && ($sub_v = strtotime($v)) && ($sub_v != -1)) {
            $tz = date('Z', $sub_v); /* timezone offset */
            $sub_v -= $tz; /* utc */
            $v = date('Y-m-d\TH:i:s\Z', $sub_v);
          }
        }
        if (!isset($res[$k])) $res[$k] = array();
        $res[$k][] = array('value' => $v, 'type' => in_array($k, $this->dt_props) || !preg_match('/^[a-z0-9]+\:[^\s]+$/is', $v) ? 'literal' : 'uri');
      }
    }
    return $res;
  }
  
  /*  */

  
}
