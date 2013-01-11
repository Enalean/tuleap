<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

function: result format detection
author:   Benjamin Nowack
version:  2008-08-04 
*/

function ARC2_getPreferredFormat($default = 'plain') {
  $formats = array(
    'html' => 'HTML', 'text/html' => 'HTML', 'xhtml+xml' => 'HTML', 
    'rdfxml' => 'RDFXML', 'rdf+xml' => 'RDFXML',
    'ntriples' => 'NTriples', 'rdf+n3' => 'Turtle', 'x-turtle' => 'Turtle', 'turtle' => 'Turtle',
    'rdfjson' => 'RDFJSON', 'json' => 'RDFJSON',
    'xml' => 'XML',
    'legacyjson' => 'LegacyJSON'
  );
  $prefs = array();
  $o_vals = array();
  /* accept header */
  if ($vals = explode(',', $_SERVER['HTTP_ACCEPT'])) {
    foreach ($vals as $val) {
      if (preg_match('/(rdf\+n3|x\-turtle|rdf\+xml|text\/html|xhtml\+xml|xml|json)/', $val, $m)) {
        $o_vals[$m[1]] = 1;
        if (preg_match('/\;q\=([0-9\.]+)/', $val, $sub_m)) {
          $o_vals[$m[1]] = 1 * $sub_m[1];
        }
      }
    }
  }
  /* arg */
  if (isset($_GET['format'])) $o_vals[$_GET['format']] = 1.1;
  /* rank */
  arsort($o_vals);
  foreach ($o_vals as $val => $prio) {
    $prefs[] = $val;
  }
  /* default */
  $prefs[] = $default;
  foreach ($prefs as $pref) {
    if (isset($formats[$pref])) {
      return $formats[$pref];
    }
  }
}
