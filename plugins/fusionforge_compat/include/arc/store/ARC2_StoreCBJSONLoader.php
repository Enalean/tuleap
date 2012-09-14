<?php
/**
 * ARC2 Store CrunchBase API JSON Loader
 *
 * @author Benjamin Nowack <bnowack@semsol.com>
 * @license http://arc.semsol.org/license
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version 2010-06-07
*/

ARC2::inc('CBJSONParser');

class ARC2_StoreCBJSONLoader extends ARC2_CBJSONParser {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_StoreCBJSONLoader($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
  }

  /*  */
  
  function done() {
    $this->extractRDF();
  }
  
  function addT($s, $p, $o, $s_type, $o_type, $o_dt = '', $o_lang = '') {
    $o = $this->toUTF8($o);
    $this->caller->addT($s, $p, $o, $s_type, $o_type, $o_dt, $o_lang);
    $this->t_count++;
  }
  
  /*  */

}
