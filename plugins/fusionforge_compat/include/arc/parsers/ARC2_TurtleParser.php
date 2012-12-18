<?php
/**
 * ARC2 SPARQL-enhanced Turtle Parser
 *
 * @author Benjamin Nowack
 * @license <http://arc.semsol.org/license>
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version 2010-04-11
*/

ARC2::inc('RDFParser');

class ARC2_TurtleParser extends ARC2_RDFParser {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_TurtleParser($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {/* reader */
    parent::__init();
    $this->state = 0;
    $this->xml = 'http://www.w3.org/XML/1998/namespace';
    $this->rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    $this->xsd = 'http://www.w3.org/2001/XMLSchema#';
    $this->nsp = array($this->xml => 'xml', $this->rdf => 'rdf', $this->xsd => 'xsd');
    $this->unparsed_code = '';
    $this->max_parsing_loops = $this->v('turtle_max_parsing_loops', 500, $this->a);
  }
  
  /*  */
  
  function x($re, $v, $options = 'si') {
    $v = preg_replace('/^[\xA0\xC2]+/', ' ', $v);
    while (preg_match('/^\s*(\#[^\xd\xa]*)(.*)$/si', $v, $m)) {/* comment removal */
      $v = $m[2];
    }
    return ARC2::x($re, $v, $options);
    //$this->unparsed_code = ($sub_r && count($sub_r)) ? $sub_r[count($sub_r) - 1] : '';
  }

  function createBnodeID(){
    $this->bnode_id++;
    return '_:' . $this->bnode_prefix . $this->bnode_id;
  }

  /*  */
  
  function addT($t) {
    if ($this->skip_dupes) {
      $h = md5(serialize($t));
      if (!isset($this->added_triples[$h])) {
        $this->triples[$this->t_count] = $t;
        $this->t_count++;
        $this->added_triples[$h] = true;
      }
    }
    else {
      $this->triples[$this->t_count] = $t;
      $this->t_count++;
    }
  }

  /*  */

  function getTriples() {
    return $this->v('triples', array());
  }
  
  function countTriples() {
    return $this->t_count;
  }
  
  /*  */
  
  function getUnparsedCode() {
    return $this->v('unparsed_code', '');
  }
  
  /*  */
  
  function setDefaultPrefixes() {
    $this->prefixes = array(
      'rdf:' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
      'rdfs:' => 'http://www.w3.org/2000/01/rdf-schema#',
      'owl:' => 'http://www.w3.org/2002/07/owl#',
      'xsd:' => 'http://www.w3.org/2001/XMLSchema#',
    );
    if ($ns = $this->v('ns', array(), $this->a)) {
      foreach ($ns as $p => $u) $this->prefixes[$p . ':'] = $u;
    }
  }
  

  function parse($path, $data = '', $iso_fallback = false) {
    $this->setDefaultPrefixes();
    /* reader */
    if (!$this->v('reader')) {
      ARC2::inc('Reader');
      $this->reader = & new ARC2_Reader($this->a, $this);
    }
    $this->reader->setAcceptHeader('Accept: application/x-turtle; q=0.9, */*; q=0.1');
    $this->reader->activate($path, $data);
    $this->base = $this->v1('base', $this->reader->base, $this->a);
    $this->r = array('vars' => array());
    /* parse */
    $buffer = '';
    $more_triples = array();
    $sub_v = '';
    $sub_v2 = '';
    $loops = 0;
    $prologue_done = 0;
    while ($d = $this->reader->readStream(0)) {
      $buffer .= $d;
      $sub_v = $buffer;
      do {
        $proceed = 0;
        if (!$prologue_done) {
          $proceed = 1;
          if ((list($sub_r, $sub_v) = $this->xPrologue($sub_v)) && $sub_r) {
            $loops = 0;
            $sub_v .= $this->reader->readStream(0, 128);
            /* we might have missed the final DOT in the previous prologue loop */
            if ($sub_r = $this->x('\.', $sub_v)) $sub_v = $sub_r[1];
            if ($this->x("\@?(base|prefix)", $sub_v)) {/* more prologue to come, use outer loop */
              $proceed = 0;
            }
          }
          else {
            $prologue_done = 1;
          }
        }
        if ($prologue_done && (list($sub_r, $sub_v, $more_triples, $sub_v2) = $this->xTriplesBlock($sub_v)) && is_array($sub_r)) {
          $proceed = 1;
          $loops = 0;
          foreach ($sub_r as $t) {
            $this->addT($t);
          }
        }
      } while ($proceed);
      $loops++;
      $buffer = $sub_v;
      if ($loops > $this->max_parsing_loops) {/* most probably a parser or code bug, might also be a huge object value, though */
        $this->addError('too many loops: ' . $loops . '. Could not parse "' . substr($buffer, 0, 200) . '..."');
        break;
      }
    }
    foreach ($more_triples as $t) {
      $this->addT($t);
    }
    $sub_v = count($more_triples) ? $sub_v2 : $sub_v;
    $buffer = $sub_v;
    $this->unparsed_code = $buffer;
    $this->reader->closeStream();
    unset($this->reader);
    /* remove trailing comments */
    while (preg_match('/^\s*(\#[^\xd\xa]*)(.*)$/si', $this->unparsed_code, $m)) $this->unparsed_code = $m[2];
    if ($this->unparsed_code && !$this->getErrors()) {
      $rest = preg_replace('/[\x0a|\x0d]/i', ' ', substr($this->unparsed_code, 0, 30));
      if (trim($rest)) $this->addError('Could not parse "' . $rest . '"');
    }
    return $this->done();
  }

  function xPrologue($v) {
    $r = 0;
    if (!$this->t_count) {
      if ((list($sub_r, $v) = $this->xBaseDecl($v)) && $sub_r) {
        $this->base = $sub_r;
        $r = 1;
      }
      while ((list($sub_r, $v) = $this->xPrefixDecl($v)) && $sub_r) {
        $this->prefixes[$sub_r['prefix']] = $sub_r['uri'];
        $r = 1;
      }
    }
    return array($r, $v);
  }
  
  /* 3 */

  function xBaseDecl($v) {
    if ($r = $this->x("\@?base\s+", $v)) {
      if ((list($r, $sub_v) = $this->xIRI_REF($r[1])) && $r) {
        if ($sub_r = $this->x('\.', $sub_v)) {
          $sub_v = $sub_r[1];
        }
        return array($r, $sub_v);
      }
    }
    return array(0, $v);
  }
  
  /* 4 */
  
  function xPrefixDecl($v) {
    if ($r = $this->x("\@?prefix\s+", $v)) {
      if ((list($r, $sub_v) = $this->xPNAME_NS($r[1])) && $r) {
        $prefix = $r;
        if((list($r, $sub_v) = $this->xIRI_REF($sub_v)) && $r) {
          $uri = $this->calcURI($r, $this->base);
          if ($sub_r = $this->x('\.', $sub_v)) {
            $sub_v = $sub_r[1];
          }
          return array(array('prefix' => $prefix, 'uri_ref' => $r, 'uri' => $uri), $sub_v);
        }
      }
    }
    return array(0, $v);
  }

  /* 21.., 32.. */
  
  function xTriplesBlock($v) {
    $pre_r = array();
    $r = array();
    $state = 1;
    $sub_v = $v;
    $buffer = $sub_v;
    do {
      $proceed = 0;
      if ($state == 1) {/* expecting subject */
        $t = array('type' => 'triple', 's' => '', 'p' => '', 'o' => '', 's_type' => '', 'p_type' => '', 'o_type' => '', 'o_datatype' => '', 'o_lang' => '');
        if ((list($sub_r, $sub_v) = $this->xVarOrTerm($sub_v)) && $sub_r) {
          $t['s'] = $sub_r['value'];
          $t['s_type'] = $sub_r['type'];
          $state = 2;
          $proceed = 1;
          if ($sub_r = $this->x('(\}|\.)', $sub_v)) {
            if ($t['s_type'] == 'placeholder') {
              $state = 4;
            }
            else {
              $this->addError('"' . $sub_r[1]. '" after subject found.');
            }
          }
        }
        elseif ((list($sub_r, $sub_v) = $this->xCollection($sub_v)) && $sub_r) {
          $t['s'] = $sub_r['id'];
          $t['s_type'] = $sub_r['type'];
          $pre_r = array_merge($pre_r, $sub_r['triples']);
          $state = 2;
          $proceed = 1;
          if ($sub_r = $this->x('\.', $sub_v)) {
            $this->addError('DOT after subject found.');
          }
        }
        elseif ((list($sub_r, $sub_v) = $this->xBlankNodePropertyList($sub_v)) && $sub_r) {
          $t['s'] = $sub_r['id'];
          $t['s_type'] = $sub_r['type'];
          $pre_r = array_merge($pre_r, $sub_r['triples']);
          $state = 2;
          $proceed = 1;
        }
        elseif ($sub_r = $this->x('\.', $sub_v)) {
          $this->addError('Subject expected, DOT found.' . $sub_v);
        }
      }
      if ($state == 2) {/* expecting predicate */
        if ($sub_r = $this->x('a\s+', $sub_v)) {
          $sub_v = $sub_r[1];
          $t['p'] = $this->rdf . 'type';
          $t['p_type'] = 'uri';
          $state = 3;
          $proceed = 1;
        }
        elseif ((list($sub_r, $sub_v) = $this->xVarOrTerm($sub_v)) && $sub_r) {
          if ($sub_r['type'] == 'bnode') {
            $this->addError('Blank node used as triple predicate');
          }
          $t['p'] = $sub_r['value'];
          $t['p_type'] = $sub_r['type'];
          $state = 3;
          $proceed = 1;
        }
        elseif ($sub_r = $this->x('\.', $sub_v)) {
          $state = 4;          
        }
        elseif ($sub_r = $this->x('\}', $sub_v)) {
          $buffer = $sub_v;
          $r = array_merge($r, $pre_r);
          $pre_r = array();
          $proceed = 0;
        }
      }
      if ($state == 3) {/* expecting object */
        if ((list($sub_r, $sub_v) = $this->xVarOrTerm($sub_v)) && $sub_r) {
          $t['o'] = $sub_r['value'];
          $t['o_type'] = $sub_r['type'];
          $t['o_lang'] = $this->v('lang', '', $sub_r);
          $t['o_datatype'] = $this->v('datatype', '', $sub_r);
          $pre_r[] = $t;
          $state = 4;
          $proceed = 1;
        }
        elseif ((list($sub_r, $sub_v) = $this->xCollection($sub_v)) && $sub_r) {
          $t['o'] = $sub_r['id'];
          $t['o_type'] = $sub_r['type'];
          $pre_r = array_merge($pre_r, array($t), $sub_r['triples']);
          $state = 4;
          $proceed = 1;
        }
        elseif ((list($sub_r, $sub_v) = $this->xBlankNodePropertyList($sub_v)) && $sub_r) {
          $t['o'] = $sub_r['id'];
          $t['o_type'] = $sub_r['type'];
          $pre_r = array_merge($pre_r, array($t), $sub_r['triples']);
          $state = 4;
          $proceed = 1;
        }
      }
      if ($state == 4) {/* expecting . or ; or , or } */
        if ($sub_r = $this->x('\.', $sub_v)) {
          $sub_v = $sub_r[1];
          $buffer = $sub_v;
          $r = array_merge($r, $pre_r);
          $pre_r = array();
          $state = 1;
          $proceed = 1;
        }
        elseif ($sub_r = $this->x('\;', $sub_v)) {
          $sub_v = $sub_r[1];
          $state = 2;
          $proceed = 1;
        }
        elseif ($sub_r = $this->x('\,', $sub_v)) {
          $sub_v = $sub_r[1];
          $state = 3;
          $proceed = 1;
          if ($sub_r = $this->x('\}', $sub_v)) {
            $this->addError('Object expected, } found.');
          }
        }
        if ($sub_r = $this->x('(\}|\{|OPTIONAL|FILTER|GRAPH)', $sub_v)) {
          $buffer = $sub_v;
          $r = array_merge($r, $pre_r);
          $pre_r = array();
          $proceed = 0;
        }
      }
    } while ($proceed);
    return count($r) ? array($r, $buffer, $pre_r, $sub_v) : array(0, $buffer, $pre_r, $sub_v);
  }
  
  /* 39.. */
  
  function xBlankNodePropertyList($v) {
    if ($sub_r = $this->x('\[', $v)) {
      $sub_v = $sub_r[1];
      $s = $this->createBnodeID();
      $r = array('id' => $s, 'type' => 'bnode', 'triples' => array());
      $t = array('type' => 'triple', 's' => $s, 'p' => '', 'o' => '', 's_type' => 'bnode', 'p_type' => '', 'o_type' => '', 'o_datatype' => '', 'o_lang' => '');
      $state = 2;
      $closed = 0;
      do {
        $proceed = 0;
        if ($state == 2) {/* expecting predicate */
          if ($sub_r = $this->x('a\s+', $sub_v)) {
            $sub_v = $sub_r[1];
            $t['p'] = $this->rdf . 'type';
            $t['p_type'] = 'uri';
            $state = 3;
            $proceed = 1;
          }
          elseif ((list($sub_r, $sub_v) = $this->xVarOrTerm($sub_v)) && $sub_r) {
            $t['p'] = $sub_r['value'];
            $t['p_type'] = $sub_r['type'];
            $state = 3;
            $proceed = 1;
          }
        }
        if ($state == 3) {/* expecting object */
          if ((list($sub_r, $sub_v) = $this->xVarOrTerm($sub_v)) && $sub_r) {
            $t['o'] = $sub_r['value'];
            $t['o_type'] = $sub_r['type'];
            $t['o_lang'] = $this->v('lang', '', $sub_r);
            $t['o_datatype'] = $this->v('datatype', '', $sub_r);
            $r['triples'][] = $t;
            $state = 4;
            $proceed = 1;
          }
          elseif ((list($sub_r, $sub_v) = $this->xCollection($sub_v)) && $sub_r) {
            $t['o'] = $sub_r['id'];
            $t['o_type'] = $sub_r['type'];
            $r['triples'] = array_merge($r['triples'], array($t), $sub_r['triples']);
            $state = 4;
            $proceed = 1;
          }
          elseif((list($sub_r, $sub_v) = $this->xBlankNodePropertyList($sub_v)) && $sub_r) {
            $t['o'] = $sub_r['id'];
            $t['o_type'] = $sub_r['type'];
            $r['triples'] = array_merge($r['triples'], array($t), $sub_r['triples']);
            $state = 4;
            $proceed = 1;
          }
        }
        if ($state == 4) {/* expecting . or ; or , or ] */
          if ($sub_r = $this->x('\.', $sub_v)) {
            $sub_v = $sub_r[1];
            $state = 1;
            $proceed = 1;
          }
          if ($sub_r = $this->x('\;', $sub_v)) {
            $sub_v = $sub_r[1];
            $state = 2;
            $proceed = 1;
          }
          if ($sub_r = $this->x('\,', $sub_v)) {
            $sub_v = $sub_r[1];
            $state = 3;
            $proceed = 1;
          }
          if ($sub_r = $this->x('\]', $sub_v)) {
            $sub_v = $sub_r[1];
            $proceed = 0;
            $closed = 1;
          }
        }
      } while ($proceed);
      if ($closed) {
        return array($r, $sub_v);
      }
      return array(0, $v);
    }
    return array(0, $v);
  }
  
  /* 40.. */
  
  function xCollection($v) {
    if ($sub_r = $this->x('\(', $v)) {
      $sub_v = $sub_r[1];
      $s = $this->createBnodeID();
      $r = array('id' => $s, 'type' => 'bnode', 'triples' => array());
      $closed = 0;
      do {
        $proceed = 0;
        if ((list($sub_r, $sub_v) = $this->xVarOrTerm($sub_v)) && $sub_r) {
          $r['triples'][] = array('type' => 'triple', 's' => $s, 'p' => $this->rdf . 'first', 'o' => $sub_r['value'], 's_type' => 'bnode', 'p_type' => 'uri', 'o_type' => $sub_r['type'], 'o_lang' => $this->v('lang', '', $sub_r), 'o_datatype' => $this->v('datatype', '', $sub_r));
          $proceed = 1;
        }
        elseif ((list($sub_r, $sub_v) = $this->xCollection($sub_v)) && $sub_r) {
          $r['triples'][] = array('type' => 'triple', 's' => $s, 'p' => $this->rdf . 'first', 'o' => $sub_r['id'], 's_type' => 'bnode', 'p_type' => 'uri', 'o_type' => $sub_r['type'], 'o_lang' => '', 'o_datatype' => '');
          $r['triples'] = array_merge($r['triples'], $sub_r['triples']);
          $proceed = 1;
        }
        elseif((list($sub_r, $sub_v) = $this->xBlankNodePropertyList($sub_v)) && $sub_r) {
          $r['triples'][] = array('type' => 'triple', 's' => $s, 'p' => $this->rdf . 'first', 'o' => $sub_r['id'], 's_type' => 'bnode', 'p_type' => 'uri', 'o_type' => $sub_r['type'], 'o_lang' => '', 'o_datatype' => '');
          $r['triples'] = array_merge($r['triples'], $sub_r['triples']);
          $proceed = 1;
        }
        if ($proceed) {
          if ($sub_r = $this->x('\)', $sub_v)) {
            $sub_v = $sub_r[1];
            $r['triples'][] = array('type' => 'triple', 's' => $s, 'p' => $this->rdf . 'rest', 'o' => $this->rdf . 'nil', 's_type' => 'bnode', 'p_type' => 'uri', 'o_type' => 'uri', 'o_lang' => '', 'o_datatype' => '');
            $closed = 1;
            $proceed = 0;
          }
          else {
            $next_s = $this->createBnodeID();
            $r['triples'][] = array('type' => 'triple', 's' => $s, 'p' => $this->rdf . 'rest', 'o' => $next_s, 's_type' => 'bnode', 'p_type' => 'uri', 'o_type' => 'bnode', 'o_lang' => '', 'o_datatype' => '');
            $s = $next_s;
          }
        }
      } while ($proceed);
      if ($closed) {
        return array($r, $sub_v);
      }
    }
    return array (0, $v);
  }
  
  /* 42 */
  
  function xVarOrTerm($v) {
    if ((list($sub_r, $sub_v) = $this->xVar($v)) && $sub_r) {
      return array($sub_r, $sub_v);
    }
    elseif ((list($sub_r, $sub_v) = $this->xGraphTerm($v)) && $sub_r) {
      return array($sub_r, $sub_v);
    }
    return array(0, $v);
  }
  
  /* 44, 74.., 75.. */
  
  function xVar($v) {
    if ($r = $this->x('(\?|\$)([^\s]+)', $v)) {
      if ((list($sub_r, $sub_v) = $this->xVARNAME($r[2])) && $sub_r) {
        if (!in_array($sub_r, $this->r['vars'])) {
          $this->r['vars'][] = $sub_r;
        }
        return array(array('value' => $sub_r, 'type' => 'var'), $sub_v . $r[3]);
      }
    }
    return array(0, $v);
  }

  /* 45 */
  
  function xGraphTerm($v) {
    foreach (array(
      'IRIref' => 'uri', 
      'RDFLiteral' => 'literal', 
      'NumericLiteral' => 'literal', 
      'BooleanLiteral' => 'literal', 
      'BlankNode' => 'bnode', 
      'NIL' => 'uri',
      'Placeholder' => 'placeholder'
    ) as $term => $type) {
      $m = 'x' . $term;
      if ((list($sub_r, $sub_v) = $this->$m($v)) && $sub_r) {
        if (!is_array($sub_r)) {
          $sub_r = array('value' => $sub_r);
        }
        $sub_r['type'] = $this->v1('type', $type, $sub_r);
        return array($sub_r, $sub_v);
      }
    }
    return array(0, $v);
  }

  /* 60 */
  
  function xRDFLiteral($v) {
    if ((list($sub_r, $sub_v) = $this->xString($v)) && $sub_r) {
      $sub_r['value'] = $this->unescapeNtripleUTF($sub_r['value']);
      $r = $sub_r;
      if ((list($sub_r, $sub_v) = $this->xLANGTAG($sub_v)) && $sub_r) {
        $r['lang'] = $sub_r;
      }
      elseif (!$this->x('\s', $sub_v) && ($sub_r = $this->x('\^\^', $sub_v)) && (list($sub_r, $sub_v) = $this->xIRIref($sub_r[1])) && $sub_r[1]) {
        $r['datatype'] = $sub_r;
      }
      return array($r, $sub_v);
    }
    return array(0, $v);
  }

  /* 61.., 62.., 63.., 64.. */  
  
  function xNumericLiteral($v) {
    $sub_r = $this->x('(\-|\+)?', $v);
    $prefix = $sub_r[1];
    $sub_v = $sub_r[2];
    foreach (array('DOUBLE' => 'double', 'DECIMAL' => 'decimal', 'INTEGER' => 'integer') as $type => $xsd) {
      $m = 'x' . $type;
      if ((list($sub_r, $sub_v) = $this->$m($sub_v)) && ($sub_r !== false)) {
        $r = array('value' => $prefix . $sub_r, 'type' => 'literal', 'datatype' => $this->xsd . $xsd);
        return array($r, $sub_v);
      }
    }
    return array(0, $v);
  }
  
  /* 65.. */
  
  function xBooleanLiteral($v) {
    if ($r = $this->x('(true|false)', $v)) {
      return array($r[1], $r[2]);
    }
    return array(0, $v);
  }

  /* 66.., 87.., 88.., 89.., 90.., 91.. */
  
  function xString($v) {/* largely simplified, may need some tweaks in following revisions */
    $sub_v = $v;
    if (!preg_match('/^\s*([\']{3}|\'|[\"]{3}|\")(.*)$/s', $sub_v, $m)) return array(0, $v);
    $delim = $m[1];
    $rest = $m[2];
    $sub_types = array("'''" => 'literal_long1', '"""' => 'literal_long2', "'" => 'literal1', '"' => 'literal2');
    $sub_type = $sub_types[$delim];
    $pos = 0;
    $r = false;
    do {
      $proceed = 0;
      $delim_pos = strpos($rest, $delim, $pos);
      if ($delim_pos === false) break;
      $new_rest = substr($rest, $delim_pos + strlen($delim));
      $r = substr($rest, 0, $delim_pos);
      if (!preg_match('/([\x5c]+)$/s', $r, $m) || !(strlen($m[1]) % 2)) {
        $rest = $new_rest;
      }
      else {
        $r = false;
        $pos = $delim_pos + 1;
        $proceed = 1;
      }
    } while ($proceed);
    if ($r !== false) {
      return array(array('value' => $this->toUTF8($r) , 'type' => 'literal', 'sub_type' => $sub_type), $rest);
    }
    return array(0, $v);
  }
  
  /* 67 */
  
  function xIRIref($v) {
    if ((list($r, $v) = $this->xIRI_REF($v)) && $r) {
      return array($this->calcURI($r, $this->base), $v);
    }
    elseif ((list($r, $v) = $this->xPrefixedName($v)) && $r) {
      return array($r, $v);
    }
    return array(0, $v);
  }
  
  /* 68 */
  
  function xPrefixedName($v) {
    if ((list($r, $v) = $this->xPNAME_LN($v)) && $r) {
      return array($r, $v);
    }
    elseif ((list($r, $sub_v) = $this->xPNAME_NS($v)) && $r) {
      return isset($this->prefixes[$r]) ? array($this->prefixes[$r], $sub_v) : array(0, $v);
    }
    return array(0, $v);
  }
  
  /* 69.., 73.., 93, 94..  */
  
  function xBlankNode($v) {
    if (($r = $this->x('\_\:', $v)) && (list($r, $sub_v) = $this->xPN_LOCAL($r[1])) && $r) {
      return array(array('type' => 'bnode', 'value' => '_:' . $r), $sub_v);
    }
    if ($r = $this->x('\[[\x20\x9\xd\xa]*\]', $v)) {
      return array(array('type' => 'bnode', 'value' => $this->createBnodeID()), $r[1]);
    }
    return array(0, $v);
  }

  /* 70.. @@sync with SPARQLParser */
  
  function xIRI_REF($v) {
    //if ($r = $this->x('\<([^\<\>\"\{\}\|\^\'[:space:]]*)\>', $v)) {
    if (($r = $this->x('\<(\$\{[^\>]*\})\>', $v)) && ($sub_r = $this->xPlaceholder($r[1]))) {
      return array($r[1], $r[2]);
    }
    elseif ($r = $this->x('\<\>', $v)) {
      return array(true, $r[1]);
    }
    elseif ($r = $this->x('\<([^\s][^\<\>]*)\>', $v)) {
      return array($r[1] ? $r[1] : true, $r[2]);
    }
    return array(0, $v);
  }
  
  /* 71 */
  
  function xPNAME_NS($v) {
    list($r, $sub_v) = $this->xPN_PREFIX($v);
    $prefix = $r ? $r : '';
    return ($r = $this->x("\:", $sub_v)) ? array($prefix . ':', $r[1]) : array(0, $v);
  }

  /* 72 */
  
  function xPNAME_LN($v) {
    if ((list($r, $sub_v) = $this->xPNAME_NS($v)) && $r) {
      if (!$this->x('\s', $sub_v) && (list($sub_r, $sub_v) = $this->xPN_LOCAL($sub_v)) && $sub_r) {
        if (!isset($this->prefixes[$r])) {
          return array(0, $v);
        }
        return array($this->prefixes[$r] . $sub_r, $sub_v);
      }
    }
    return array(0, $v);
  }
  
  /* 76 */
  
  function xLANGTAG($v) {
    if (!$this->x('\s', $v) && ($r = $this->x('\@([a-z]+(\-[a-z0-9]+)*)', $v))) {
      return array($r[1], $r[3]);
    }
    return array(0, $v);
  }
  
  /* 77.. */
  
  function xINTEGER($v) {
    if ($r = $this->x('([0-9]+)', $v)) {
      return array($r[1], $r[2]);
    }
    return array(false, $v);
  }

  /* 78.. */

  function xDECIMAL($v) {
    if ($r = $this->x('([0-9]+\.[0-9]*)', $v)) {
      return array($r[1], $r[2]);
    }
    if ($r = $this->x('(\.[0-9]+)', $v)) {
      return array($r[1], $r[2]);
    }
    return array(false, $v);
  }

  /* 79.., 86.. */

  function xDOUBLE($v) {
    if ($r = $this->x('([0-9]+\.[0-9]*E[\+\-]?[0-9]+)', $v)) {
      return array($r[1], $r[2]);
    }
    if ($r = $this->x('(\.[0-9]+E[\+\-]?[0-9]+)', $v)) {
      return array($r[1], $r[2]);
    }
    if ($r = $this->x('([0-9]+E[\+\-]?[0-9]+)', $v)) {
      return array($r[1], $r[2]);
    }
    return array(false, $v);
  }
  
  /* 92 */
  
  function xNIL($v) {
    if ($r = $this->x('\([\x20\x9\xd\xa]*\)', $v)) {
      return array(array('type' => 'uri', 'value' => $this->rdf . 'nil'), $r[1]);
    }
    return array(0, $v);
  }

  /* 95.. */
  
  function xPN_CHARS_BASE($v) {
    if ($r = $this->x("([a-z]+|\\\u[0-9a-f]{1,4})", $v)) {
      return array($r[1], $r[2]);
    }
    return array(0, $v);
  }

  /* 96 */
  
  function xPN_CHARS_U($v) {
    if ((list($r, $sub_v) = $this->xPN_CHARS_BASE($v)) && $r) {
      return array($r, $sub_v);
    }
    elseif ($r = $this->x("(_)", $v)) {
      return array($r[1], $r[2]);
    }
    return array(0, $v);
  }

  /* 97.. */
  
  function xVARNAME($v) {
    $r = '';
    do {
      $proceed = 0;
      if ($sub_r = $this->x('([0-9]+)', $v)) {
        $r .= $sub_r[1];
        $v = $sub_r[2];
        $proceed = 1;
      }
      elseif ((list($sub_r, $sub_v) = $this->xPN_CHARS_U($v)) && $sub_r) {
        $r .= $sub_r;
        $v = $sub_v;
        $proceed = 1;
      }
      elseif ($r && ($sub_r = $this->x('([\xb7\x300-\x36f]+)', $v))) {
        $r .= $sub_r[1];
        $v = $sub_r[2];
        $proceed = 1;
      }
    } while ($proceed);
    return array($r, $v);
  }

  /* 98.. */
  
  function xPN_CHARS($v) {
    if ((list($r, $sub_v) = $this->xPN_CHARS_U($v)) && $r) {
      return array($r, $sub_v);
    }
    elseif ($r = $this->x('([\-0-9\xb7\x300-\x36f])', $v)) {
      return array($r[1], $r[2]);
    }
    return array(false, $v);
  }

  /* 99 */
  
  function xPN_PREFIX($v) {
    if ($sub_r = $this->x("([^\s\:\(\)\{\}\;\,]+)", $v, 's')) {/* accelerator */
      return array($sub_r[1], $sub_r[2]);/* @@testing */
    }
    if ((list($r, $sub_v) = $this->xPN_CHARS_BASE($v)) && $r) {
      do {
        $proceed = 0;
        list($sub_r, $sub_v) = $this->xPN_CHARS($sub_v);
        if ($sub_r !== false) {
          $r .= $sub_r;
          $proceed = 1;
        }
        elseif ($sub_r = $this->x("\.", $sub_v)) {
          $r .= '.';
          $sub_v = $sub_r[1];
          $proceed = 1;
        }
      } while ($proceed);
      list($sub_r, $sub_v) = $this->xPN_CHARS($sub_v);
      $r .= $sub_r ? $sub_r : '';
    }
    return array($r, $sub_v);
  }
  
  /* 100 */
  
  function xPN_LOCAL($v) {
    if (($sub_r = $this->x("([^\s\(\)\{\}\[\]\;\,\.]+)", $v, 's')) && !preg_match('/^\./', $sub_r[2])) {/* accelerator */
      return array($sub_r[1], $sub_r[2]);/* @@testing */
    }
    $r = '';
    $sub_v = $v;
    do {
      $proceed = 0;
      if ($this->x('\s', $sub_v)) {
        return array($r, $sub_v);
      }
      if ($sub_r = $this->x('([0-9])', $sub_v)) {
        $r .= $sub_r[1];
        $sub_v = $sub_r[2];
        $proceed = 1;
      }
      elseif ((list($sub_r, $sub_v) = $this->xPN_CHARS_U($sub_v)) && $sub_r) {
        $r .= $sub_r;
        $proceed = 1;
      }
      elseif ($r) {
        if (($sub_r = $this->x('(\.)', $sub_v)) && !preg_match('/^[\s\}]/s', $sub_r[2])) {
          $r .= $sub_r[1];
          $sub_v = $sub_r[2];
        }
        if ((list($sub_r, $sub_v) = $this->xPN_CHARS($sub_v)) && $sub_r) {
          $r .= $sub_r;
          $proceed = 1;
        }
      }
    } while ($proceed);
    return array($r, $sub_v);
  }
  
  /*  */
  
  function unescapeNtripleUTF($v) {
    if (strpos($v, '\\') === false) return $v;
    $mappings = array('t' => "\t", 'n' => "\n", 'r' => "\r", '\"' => '"', '\'' => "'");
    foreach ($mappings as $in => $out) {
      $v = preg_replace('/\x5c([' . $in . '])/', $out, $v);
    }
    if (strpos(strtolower($v), '\u') === false) return $v;
    while (preg_match('/\\\(U)([0-9A-F]{8})/', $v, $m) || preg_match('/\\\(u)([0-9A-F]{4})/', $v, $m)) {
      $no = hexdec($m[2]);
  		if ($no < 128) $char = chr($no);
      else if ($no < 2048) $char = chr(($no >> 6) + 192) . chr(($no & 63) + 128);
      else if ($no < 65536) $char = chr(($no >> 12) + 224) . chr((($no >> 6) & 63) + 128) . chr(($no & 63) + 128);
  		else if ($no < 2097152) $char = chr(($no >> 18) + 240) . chr((($no >> 12) & 63) + 128) . chr((($no >> 6) & 63) + 128) . chr(($no & 63) + 128);
      else $char= '';
      $v = str_replace('\\' . $m[1] . $m[2], $char, $v);
    }
    return $v;
  }
  
  /*  */
  
  function xPlaceholder($v) {
    //if ($r = $this->x('(\?|\$)\{([^\}]+)\}', $v)) {
    if ($r = $this->x('(\?|\$)', $v)) {
      if (preg_match('/(\{(?:[^{}]+|(?R))*\})/', $r[2], $m) && strpos(trim($r[2]), $m[1]) === 0) {
        $ph = substr($m[1], 1, -1);
        $rest = substr(trim($r[2]), strlen($m[1]));
        if (!isset($this->r['placeholders'])) $this->r['placeholders'] = array();
        if (!in_array($ph, $this->r['placeholders'])) $this->r['placeholders'][] = $ph;
        return array(array('value' => $ph, 'type' => 'placeholder'), $rest);
      }
    }
    return array(0, $v);
  }
  
  /*  */
}
