<?php
/**
 * ARC2 RSS 1.0 Serializer
 *
 * @author Toby Inkster
 * @author Benjamin Nowack
 * @license <http://arc.semsol.org/license>
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version 2009-11-09
*/

ARC2::inc('RDFXMLSerializer');

class ARC2_RSS10Serializer extends ARC2_RDFXMLSerializer {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_RSS10Serializer($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->content_header = 'application/rss+xml';
    $this->default_ns = 'http://purl.org/rss/1.0/';
    $this->type_nodes = true;
  }

  /*  */
  
}
