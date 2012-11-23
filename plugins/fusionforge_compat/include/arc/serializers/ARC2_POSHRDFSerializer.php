<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 POSH RDF Serializer
author:   Benjamin Nowack
version:  2008-11-18 (Tweak: Updated to poshRDF spec draft)
*/

ARC2::inc('RDFSerializer');

class ARC2_POSHRDFSerializer extends ARC2_RDFSerializer {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_POSHRDFSerializer($a = '', &$caller) {/* ns */
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->content_header = 'text/html';
  }

  /*  */
  
  function getLabel($res, $ps = '') {
    if (!$ps) $ps = array();
    foreach ($ps as $p => $os) {
      if (preg_match('/[\/\#](name|label|summary|title|fn)$/i', $p)) {
        return $os[0]['value'];
      }
    }
    if (preg_match('/^\_\:/', $res)) return "An unnamed resource";
    return preg_replace("/^(.*[\/\#])([^\/\#]+)$/", '\\2', str_replace('_', ' ', $res));
  }
  
  function getSerializedIndex($index, $res = '') {
    $r = '';
    $n = "\n";
    if ($res) $index = array($res => $index[$res]);
    //return Trice::dump($index);
    foreach ($index as $s => $ps) {
      /* node */
      $r .= '
        <div class="rdf-view">
          <h3><a class="rdf-s" href="' . $s . '">' . $this->getLabel($s, $ps)  . '</a></h3>
      ';
      /* arcs */
      foreach ($ps as $p => $os) {
        $r .= '
          <div class="rdf-o-list">
            <a class="rdf-p" href="' . $p . '">' . ucfirst($this->getLabel($p)) . '</a>
        ';
        foreach ($os as $o) {
          $r .= $n . $this->getObjectValue($o);
        }
        $r .= '    
          </div>
        ';
      }
      /* node */
      $r .= '
        <div class="clb"></div>
        </div>
      ';
    }
    return $r;
  }
  
  function getObjectValue($o) {
    if ($o['type'] == 'uri') {
      if (preg_match('/(jpe?g|gif|png)$/i', $o['value'])) {
        return $this->getImageObjectValue($o);
      }
      return $this->getURIObjectValue($o);
    }
    if ($o['type'] == "bnode") {
      return $this->getBNodeObjectValue($o);
    }
    return $this->getLiteralObjectValue($o);
  }
  
  function getImageObjectValue($o) {
    return '<img class="rdf-o" src="' . htmlspecialchars($o['value']) . '" alt="img" />';
  }
  
  function getURIObjectValue($o) {
    $href = htmlspecialchars($o['value']);
    $label = $o['value'];
    $label = preg_replace('/^https?\:\/\/(www\.)?/', '', $label);
    return '<a class="rdf-o" href="' . $href . '">' . $label . '</a>';
  }

  function getBNodeObjectValue($o) {
    return '<div class="rdf-o" title="' . $o['value']. '">An unnamed resource</div>';
  }

  function getLiteralObjectValue($o) {
    return '<div class="rdf-o">' . $o['value'] . '</div>';
  }

  /*  */

}

