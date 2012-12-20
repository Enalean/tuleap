<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 Legacy XML Serializer
author:   Benjamin Nowack
version:  2008-08-04
*/

ARC2::inc('Class');

class ARC2_LegacyHTMLSerializer extends ARC2_Class {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_LegacyHTMLSerializer($a = '', &$caller) {/* ns */
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->content_header = 'text/html';
  }

  /*  */
  
  function getSerializedArray($struct, $root = 1, $ind = ' ') {
    $n = "\n";
    $r = '';
    $is_flat = $this->isAssociativeArray($struct) ? 0 : 1;
    foreach ($struct as $k => $v) {
      if (!$is_flat) $r .= $n . $ind . $ind . '<dt>' . $k . '</dt>';
      $r .= $n . $ind . $ind . '<dd>' . (is_array($v) ? $this->getSerializedArray($v, 0, $ind . $ind . $ind) . $n . $ind . $ind : htmlspecialchars($v)) . '</dd>';
    }
    return $n . $ind . '<dl>' . $r . $n . $ind . '</dl>';
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

