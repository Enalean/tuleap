<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 Legacy XML Serializer
author:   Benjamin Nowack
version:  2008-08-04
*/

ARC2::inc('Class');

class ARC2_LegacyXMLSerializer extends ARC2_Class {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_LegacyXMLSerializer($a = '', &$caller) {/* ns */
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->content_header = 'text/xml';
  }

  /*  */
  
  function getSerializedArray($struct, $root = 1, $ind = '  ') {
    $n = "\n";
    $r = '';
    $is_flat = $this->isAssociativeArray($struct) ? 0 : 1;
    foreach ($struct as $k => $v) {
      $tag = $is_flat ? 'item' : preg_replace('/[\s]/s', '_', $k);
      $tag = preg_replace('/^.*([a-z0-9\-\_]+)$/Uis', '\\1', $tag);
      $r .= $n . $ind . '<' . $tag . '>' . (is_array($v) ? $this->getSerializedArray($v, 0, $ind . '  ') . $n . $ind : htmlspecialchars($v)) . '</' . $tag . '>';
    }
    if ($root) $r = $this->getHead() . $r . $this->getFooter();
    return $r;
  }
  
  /*  */

  function getHead() {
    $n = "\n";
    $r = '<?xml version="1.0"?>';
    $r .= $n . '<items>';
    return $r;
  }
  
  function getFooter() {
    $n = "\n";
    $r = $n . '</items>';
    return $r;
  }
  
  /*  */

  function isAssociativeArray($v) {
    foreach (array_keys($v) as $k => $val) {
      if ($k !== $val) return 1;
    }
    return 0;
  }
  
  /*  */

}

