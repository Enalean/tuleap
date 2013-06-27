<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 Extractor
author:   Benjamin Nowack
version:  2008-12-09
*/

ARC2::inc('RDFExtractor');

class ARC2_TwitterProfilePicExtractor extends ARC2_RDFExtractor {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_TwitterProfilePicExtractor($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->a['ns']['foaf'] = 'http://xmlns.com/foaf/0.1/';
    $this->a['ns']['mf'] = 'http://poshrdf.org/ns/mf#';
  }

  /*  */
  
  function extractRDF() {
    $t_vals = array();
    $t = '';
    foreach ($this->nodes as $n) {
      if (isset($n['tag']) && ($n['tag'] == 'img') && ($this->v('id', '', $n['a']) == 'profile-image')) {
        $t_vals['vcard_id'] = $this->getDocID($n) . '#resource(side/1/2/1)';
        $t .= '?vcard_id mf:photo <' . $n['a']['src'] . '> . ';
        break;
      }
    }
    if ($t) {
      $doc = $this->getFilledTemplate($t, $t_vals, $n['doc_base']);
      $this->addTs(ARC2::getTriplesFromIndex($doc));
    }
  }

  /*  */
  
}
