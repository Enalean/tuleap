<?php
/**
 * ARC2 RDF Serializer
 *
 * @author Benjamin Nowack
 * @license <http://arc.semsol.org/license>
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version 2009-11-09
*/

ARC2::inc('Class');

class ARC2_RDFSerializer extends ARC2_Class {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_RDFSerializer($a = '', &$caller) {/* ns */
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    foreach ($this->ns as $k => $v) {
      $this->nsp[$v] = $k;
    }
  }

  /*  */
  
  function xgetPName($v) {/* moved to merged getPName in ARC2_CLass */
    if (preg_match('/^([a-z0-9\_\-]+)\:([a-z\_][a-z0-9\_\-]*)$/i', $v, $m) && isset($this->ns[$m[1]])) {
      $this->used_ns = !in_array($this->ns[$m[1]], $this->used_ns) ? array_merge($this->used_ns, array($this->ns[$m[1]])) : $this->used_ns;
      return $v;
    }
    if (preg_match('/^(.*[\/\#])([a-z\_][a-z0-9\-\_]*)$/i', $v, $m)) {
      return $this->getPrefix($m[1]) . ':' . $m[2];
    }
    return 0;
  }
  
  /*  */
  
  function getSerializedTriples($triples, $raw = 0) {
    $index = ARC2::getSimpleIndex($triples, 0);
    return $this->getSerializedIndex($index, $raw);
  }
  
  function getSerializedIndex() {
    return '';
  }
  
  /*  */
  
}
