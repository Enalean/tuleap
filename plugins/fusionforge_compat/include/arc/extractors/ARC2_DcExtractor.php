<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 DC Extractor
author:   Benjamin Nowack
version:  2008-04-09 (Fix: base URL (not doc URL) was used for annotations)
*/

ARC2::inc('RDFExtractor');

class ARC2_DcExtractor extends ARC2_RDFExtractor {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_DcExtractor($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->a['ns']['dc'] = 'http://purl.org/dc/elements/1.1/';
  }

  /*  */
  
  function extractRDF() {
    $t_vals = array();
    $t = '';
    foreach ($this->nodes as $n) {
      foreach (array('title', 'link', 'meta') as $tag) {
        if ($n['tag'] == $tag) {
          $m = 'extract' . ucfirst($tag);
          list ($t_vals, $t) = $this->$m($n, $t_vals, $t);
        }
      }
    }
    if ($t) {
      $doc = $this->getFilledTemplate($t, $t_vals, $n['doc_base']);
      $this->addTs(ARC2::getTriplesFromIndex($doc));
    }
  }
  
  /*  */

  function extractTitle($n, $t_vals, $t) {
    if ($t_vals['title'] = $this->getPlainContent($n)) {
      $t .= '<' . $n['doc_url'] . '> dc:title ?title . ';
    }
    return array($t_vals, $t);
  }
  
  /*  */

  function extractLink($n, $t_vals, $t) {
    if ($this->hasRel($n, 'alternate') || $this->hasRel($n, 'meta')) {
      if ($href = $this->v('href uri', '', $n['a'])) {
        $t .= '<' . $n['doc_url'] . '> rdfs:seeAlso <' . $href . '> . ';
        if ($v = $this->v('type', '', $n['a'])) {
          $t .= '<' .$href. '> dc:format "' . $v . '" . ';
        }
        if ($v = $this->v('title', '', $n['a'])) {
          $t .= '<' .$href. '> dc:title "' . $v . '" . ';
        }
      }
    }
    return array($t_vals, $t);
  }
  
  function extractMeta($n, $t_vals, $t) {
    if ($this->hasAttribute('http-equiv', $n, 'Content-Type') || $this->hasAttribute('http-equiv', $n, 'content-type')) {
      if ($v = $this->v('content', '', $n['a'])) {
        $t .= '<' . $n['doc_url'] . '> dc:format "' . $v . '" . ';
      }
    }
    return array($t_vals, $t);
  }
  
  /*  */
  
}
