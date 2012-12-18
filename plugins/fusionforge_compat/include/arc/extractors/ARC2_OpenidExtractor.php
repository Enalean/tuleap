<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 foaf:openid Extractor
author:   Benjamin Nowack
version:  2007-10-08
*/

ARC2::inc('RDFExtractor');

class ARC2_OpenidExtractor extends ARC2_RDFExtractor {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_OpenidExtractor($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->a['ns']['foaf'] = 'http://xmlns.com/foaf/0.1/';
  }

  /*  */
  
  function extractRDF() {
    $t_vals = array();
    $t = '';
    foreach ($this->nodes as $n) {
      if (isset($n['tag']) && $n['tag'] == 'link') {
        $m = 'extract' . ucfirst($n['tag']);
        list ($t_vals, $t) = $this->$m($n, $t_vals, $t);
      }
    }
    if ($t) {
      $doc = $this->getFilledTemplate($t, $t_vals, $n['doc_base']);
      $this->addTs(ARC2::getTriplesFromIndex($doc));
    }
  }
  
  /*  */

  function extractLink($n, $t_vals, $t) {
    if ($this->hasRel($n, 'openid.server')) {
      if ($href = $this->v('href uri', '', $n['a'])) {
        $t_vals['doc_owner'] = $this->getDocOwnerID($n);
        $t_vals['doc_id'] = $this->getDocID($n);
        $t .= '?doc_owner foaf:homepage ?doc_id ; foaf:openid ?doc_id . ';
      }
    }
    if ($this->hasRel($n, 'openid.delegate')) {
      if ($href = $this->v('href uri', '', $n['a'])) {
        $t_vals['doc_owner'] = $this->getDocOwnerID($n);
        $t .= '?doc_owner foaf:homepage <' . $href . '> ; foaf:openid <' . $href . '> . ';
      }
    }
    return array($t_vals, $t);
  }
  
  /*  */
  
}
