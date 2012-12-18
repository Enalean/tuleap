<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 Store SemHTML Loader
author:   Benjamin Nowack
version:  2008-06-28 (Tweak: adjusted to normalized "literal" type)
*/

ARC2::inc('SemHTMLParser');

class ARC2_StoreSemHTMLLoader extends ARC2_SemHTMLParser {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_StoreSemHTMLLoader($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
  }

  /*  */
  
  function done() {
    $this->extractRDF();
  }
  
  function addT($t) {
    $this->caller->addT($t['s'], $t['p'], $t['o'], $t['s_type'], $t['o_type'], $t['o_datatype'], $t['o_lang']);
    $this->t_count++;
  }

  /*  */

}
