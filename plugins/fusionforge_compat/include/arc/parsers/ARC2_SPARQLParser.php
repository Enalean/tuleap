<?php
/**
 * ARC2 SPARQL Parser
 *
 * @author Benjamin Nowack
 * @license <http://arc.semsol.org/license>
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version 2010-04-11
*/

ARC2::inc('TurtleParser');

class ARC2_SPARQLParser extends ARC2_TurtleParser {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_SPARQLParser($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->bnode_prefix = $this->v('bnode_prefix', 'arc'.substr(md5(uniqid(rand())), 0, 4).'b', $this->a);
    $this->bnode_id = 0;
    $this->bnode_pattern_index = array('patterns' => array(), 'bnodes' => array());
  }

  /*  */

  function parse($q, $src = '') {
    $this->setDefaultPrefixes();
    $this->base = $src ? $this->calcBase($src) : ARC2::getRequestURI();
    $this->r = array(
      'base' => '',
      'vars' => array(),
      'prefixes' => array()
    );
    $this->unparsed_code = $q;
    list($r, $v) = $this->xQuery($q);
    if ($r) {
      $this->r['query'] = $r;
      $this->unparsed_code = trim($v);
    }
    elseif (!$this->getErrors() && !$this->unparsed_code) {
      $this->addError('Query not properly closed');
    }
    $this->r['prefixes'] = $this->prefixes;
    $this->r['base'] = $this->base;
    /* remove trailing comments */
    while (preg_match('/^\s*(\#[^\xd\xa]*)(.*)$/si', $this->unparsed_code, $m)) $this->unparsed_code = $m[2];
    if ($this->unparsed_code && !$this->getErrors()) {
      $rest = preg_replace('/[\x0a|\x0d]/i', ' ', substr($this->unparsed_code, 0, 30));
      $msg = trim($rest) ? 'Could not properly handle "' . $rest . '"' : 'Syntax error, probably an incomplete pattern';
      $this->addError($msg);
    }
  }
  
  function getQueryInfos() {
    return $this->v('r', array());
  }

  /* 1 */
  
  function xQuery($v) {
    list($r, $v) = $this->xPrologue($v);
    foreach (array('Select', 'Construct', 'Describe', 'Ask') as $type) {
      $m = 'x' . $type . 'Query';
      if ((list($r, $v) = $this->$m($v)) && $r) {
        return array($r, $v);
      }
    }
    return array(0, $v);
  }

  /* 2 */

  function xPrologue($v) {
    $r = 0;
    if ((list($sub_r, $v) = $this->xBaseDecl($v)) && $sub_r) {
      $this->base = $sub_r;
      $r = 1;
    }
    while ((list($sub_r, $v) = $this->xPrefixDecl($v)) && $sub_r) {
      $this->prefixes[$sub_r['prefix']] = $sub_r['uri'];
      $r = 1;
    }
    return array($r, $v);
  }

  /* 5.. */
  
  function xSelectQuery($v) {
    if ($sub_r = $this->x('SELECT\s+', $v)) {
      $r = array(
        'type' => 'select',
        'result_vars' => array(),
        'dataset' => array(),
      );
      $all_vars = 0;
      $sub_v = $sub_r[1];
      /* distinct, reduced */
      if ($sub_r = $this->x('(DISTINCT|REDUCED)\s+', $sub_v)) {
        $r[strtolower($sub_r[1])] = 1;
        $sub_v = $sub_r[2];
      }
      /* result vars */
      if ($sub_r = $this->x('\*\s+', $sub_v)) {
        $all_vars = 1;
        $sub_v = $sub_r[1];
      }
      else {
        while ((list($sub_r, $sub_v) = $this->xResultVar($sub_v)) && $sub_r) {
          $r['result_vars'][] = $sub_r;
        }
      }
      if (!$all_vars && !count($r['result_vars'])) {
        $this->addError('No result bindings specified.');
      }
      /* dataset */
      while ((list($sub_r, $sub_v) = $this->xDatasetClause($sub_v)) && $sub_r) {
        $r['dataset'][] = $sub_r;
      }
      /* where */
      if ((list($sub_r, $sub_v) = $this->xWhereClause($sub_v)) && $sub_r) {
        $r['pattern'] = $sub_r;
      }
      else {
        return array(0, $v);
      }
      /* solution modifier */
      if ((list($sub_r, $sub_v) = $this->xSolutionModifier($sub_v)) && $sub_r) {
        $r = array_merge($r, $sub_r);
      }
      /* all vars */
      if ($all_vars) {
        foreach ($this->r['vars'] as $var) {
          $r['result_vars'][] = array('var' => $var, 'aggregate' => 0, 'alias' => '');
        }
        if (!$r['result_vars']) {
          $r['result_vars'][] = '*';
        }
      }
      return array($r, $sub_v);
    }
    return array(0, $v);
  }
  
  function xResultVar($v) {
    return $this->xVar($v);
  }

  /* 6.. */
  
  function xConstructQuery($v) {
    if ($sub_r = $this->x('CONSTRUCT\s*', $v)) {
      $r = array(
        'type' => 'construct',
        'dataset' => array(),
      );
      $sub_v = $sub_r[1];
      /* construct template */
      if ((list($sub_r, $sub_v) = $this->xConstructTemplate($sub_v)) && is_array($sub_r)) {
        $r['construct_triples'] = $sub_r;
      }
      else {
        $this->addError('Construct Template not found');
        return array(0, $v);
      }
      /* dataset */
      while ((list($sub_r, $sub_v) = $this->xDatasetClause($sub_v)) && $sub_r) {
        $r['dataset'][] = $sub_r;
      }
      /* where */
      if ((list($sub_r, $sub_v) = $this->xWhereClause($sub_v)) && $sub_r) {
        $r['pattern'] = $sub_r;
      }
      else {
        return array(0, $v);
      }
      /* solution modifier */
      if ((list($sub_r, $sub_v) = $this->xSolutionModifier($sub_v)) && $sub_r) {
        $r = array_merge($r, $sub_r);
      }
      return array($r, $sub_v);
    }
    return array(0, $v);
  }

  /* 7.. */
  
  function xDescribeQuery($v) {
    if ($sub_r = $this->x('DESCRIBE\s+', $v)) {
      $r = array(
        'type' => 'describe',
        'result_vars' => array(),
        'result_uris' => array(),
        'dataset' => array(),
      );
      $sub_v = $sub_r[1];
      $all_vars = 0;
      /* result vars/uris */
      if ($sub_r = $this->x('\*\s+', $sub_v)) {
        $all_vars = 1;
        $sub_v = $sub_r[1];
      }
      else {
        do {
          $proceed = 0;
          if ((list($sub_r, $sub_v) = $this->xResultVar($sub_v)) && $sub_r) {
            $r['result_vars'][] = $sub_r;
            $proceed = 1;
          }
          if ((list($sub_r, $sub_v) = $this->xIRIref($sub_v)) && $sub_r) {
            $r['result_uris'][] = $sub_r;
            $proceed =1;
          }
        } while ($proceed);
      }
      if (!$all_vars && !count($r['result_vars']) && !count($r['result_uris'])) {
        $this->addError('No result bindings specified.');
      }
      /* dataset */
      while ((list($sub_r, $sub_v) = $this->xDatasetClause($sub_v)) && $sub_r) {
        $r['dataset'][] = $sub_r;
      }
      /* where */
      if ((list($sub_r, $sub_v) = $this->xWhereClause($sub_v)) && $sub_r) {
        $r['pattern'] = $sub_r;
      }
      /* solution modifier */
      if ((list($sub_r, $sub_v) = $this->xSolutionModifier($sub_v)) && $sub_r) {
        $r = array_merge($r, $sub_r);
      }
      /* all vars */
      if ($all_vars) {
        foreach ($this->r['vars'] as $var) {
          $r['result_vars'][] = array('var' => $var, 'aggregate' => 0, 'alias' => '');
        }
      }
      return array($r, $sub_v);
    }
    return array(0, $v);
  }

  /* 8.. */
  
  function xAskQuery($v) {
    if ($sub_r = $this->x('ASK\s+', $v)) {
      $r = array(
        'type' => 'ask',
        'dataset' => array(),
      );
      $sub_v = $sub_r[1];
      /* dataset */
      while ((list($sub_r, $sub_v) = $this->xDatasetClause($sub_v)) && $sub_r) {
        $r['dataset'][] = $sub_r;
      }
      /* where */
      if ((list($sub_r, $sub_v) = $this->xWhereClause($sub_v)) && $sub_r) {
        $r['pattern'] = $sub_r;
        return array($r, $sub_v);
      }
      else {
        $this->addError('Missing or invalid WHERE clause.');
      }
    }
    return array(0, $v);
  }

  /* 9, 10, 11, 12 */

  function xDatasetClause($v) {
    if ($r = $this->x('FROM(\s+NAMED)?\s+', $v)) {
      $named = $r[1] ? 1 : 0;
      if ((list($r, $sub_v) = $this->xIRIref($r[2])) && $r) {
        return array(array('graph' => $r, 'named' => $named), $sub_v);
      }
    }
    return array(0, $v);
  }

  /* 13 */
  
  function xWhereClause($v) {
    if ($r = $this->x('(WHERE)?', $v)) {
      $v = $r[2];
    }
    if ((list($r, $v) = $this->xGroupGraphPattern($v)) && $r) {
      return array($r, $v);
    }
    return array(0, $v);
  }
  
  /* 14, 15 */
  
  function xSolutionModifier($v) {
    $r = array();
    if ((list($sub_r, $sub_v) = $this->xOrderClause($v)) && $sub_r) {
      $r['order_infos'] = $sub_r;
    }
    while ((list($sub_r, $sub_v) = $this->xLimitOrOffsetClause($sub_v)) && $sub_r) {
      $r = array_merge($r, $sub_r);
    }
    return ($v == $sub_v) ? array(0, $v) : array($r, $sub_v);
  }

  /* 18, 19 */
  
  function xLimitOrOffsetClause($v) {
    if ($sub_r = $this->x('(LIMIT|OFFSET)', $v)) {
      $key = strtolower($sub_r[1]);
      $sub_v = $sub_r[2];
      if ((list($sub_r, $sub_v) = $this->xINTEGER($sub_v)) && ($sub_r !== false)) {
        return array(array($key =>$sub_r), $sub_v);
      }
      if ((list($sub_r, $sub_v) = $this->xPlaceholder($sub_v)) && ($sub_r !== false)) {
        return array(array($key =>$sub_r), $sub_v);
      }
    }
    return array(0, $v);
  }

  /* 16 */
  
  function xOrderClause($v) {
    if ($sub_r = $this->x('ORDER BY\s+', $v)) {
      $sub_v = $sub_r[1];
      $r = array();
      while ((list($sub_r, $sub_v) = $this->xOrderCondition($sub_v)) && $sub_r) {
        $r[] = $sub_r;
      }
      if (count($r)) {
        return array($r, $sub_v);
      }
      else {
        $this->addError('No order conditions specified.');
      }
    }
    return array(0, $v);
  }
  
  /* 17, 27 */
  
  function xOrderCondition($v) {
    if ($sub_r = $this->x('(ASC|DESC)', $v)) {
      $dir = strtolower($sub_r[1]);
      $sub_v = $sub_r[2];
      if ((list($sub_r, $sub_v) = $this->xBrackettedExpression($sub_v)) && $sub_r) {
        $sub_r['direction'] = $dir;
        return array($sub_r, $sub_v);
      }
    }
    elseif ((list($sub_r, $sub_v) = $this->xVar($v)) && $sub_r) {
      $sub_r['direction'] = 'asc';
      return array($sub_r, $sub_v);
    }
    elseif ((list($sub_r, $sub_v) = $this->xBrackettedExpression($v)) && $sub_r) {
      return array($sub_r, $sub_v);
    }
    elseif ((list($sub_r, $sub_v) = $this->xBuiltInCall($v)) && $sub_r) {
      $sub_r['direction'] = 'asc';
      return array($sub_r, $sub_v);
    }
    elseif ((list($sub_r, $sub_v) = $this->xFunctionCall($v)) && $sub_r) {
      $sub_r['direction'] = 'asc';
      return array($sub_r, $sub_v);
    }
    return array(0, $v);
  }

  /* 20 */
  
  function xGroupGraphPattern($v) {
    $pattern_id = substr(md5(uniqid(rand())), 0, 4);
    if ($sub_r = $this->x('\{', $v)) {
      $r = array('type' => 'group', 'patterns' => array());
      $sub_v = $sub_r[1];
      if ((list($sub_r, $sub_v) = $this->xTriplesBlock($sub_v)) && $sub_r) {
        $this->indexBnodes($sub_r, $pattern_id);
        $r['patterns'][] = array('type' => 'triples', 'patterns' => $sub_r);
      }
      do {
        $proceed = 0;
        if ((list($sub_r, $sub_v) = $this->xGraphPatternNotTriples($sub_v)) && $sub_r) {
          $r['patterns'][] = $sub_r;
          $pattern_id = substr(md5(uniqid(rand())), 0, 4);
          $proceed = 1;
        }
        elseif ((list($sub_r, $sub_v) = $this->xFilter($sub_v)) && $sub_r) {
          $r['patterns'][] = array('type' => 'filter', 'constraint' => $sub_r);
          $proceed = 1;
        }
        if ($sub_r = $this->x('\.', $sub_v)) {
          $sub_v = $sub_r[1];
        }
        if ((list($sub_r, $sub_v) = $this->xTriplesBlock($sub_v)) && $sub_r) {
          $this->indexBnodes($sub_r, $pattern_id);
          $r['patterns'][] = array('type' => 'triples', 'patterns' => $sub_r);
          $proceed = 1;
        }
        if ((list($sub_r, $sub_v) = $this->xPlaceholder($sub_v)) && $sub_r) {
          $r['patterns'][] = $sub_r;
          $proceed = 1;
        }
      } while ($proceed);
      if ($sub_r = $this->x('\}', $sub_v)) {
        $sub_v = $sub_r[1];
        return array($r, $sub_v);
      }
      $rest = preg_replace('/[\x0a|\x0d]/i', ' ', substr($sub_v, 0, 30));
      $this->addError('Incomplete or invalid Group Graph pattern. Could not handle "' . $rest . '"');
    }
    return array(0, $v);
  }
  
  function indexBnodes($triples, $pattern_id) {
    $index_id = count($this->bnode_pattern_index['patterns']);
    $index_id = $pattern_id;
    $this->bnode_pattern_index['patterns'][] = $triples;
    foreach ($triples as $t) {
      foreach (array('s', 'p', 'o') as $term) {
        if ($t[$term . '_type'] == 'bnode') {
          $val = $t[$term];
          if (isset($this->bnode_pattern_index['bnodes'][$val]) && ($this->bnode_pattern_index['bnodes'][$val] != $index_id)) {
            $this->addError('Re-used bnode label "' .$val. '" across graph patterns');
          }
          else {
            $this->bnode_pattern_index['bnodes'][$val] = $index_id;
          }
        }
      }
    }
  }
  
  /* 22.., 25.. */
  
  function xGraphPatternNotTriples($v) {
    if ((list($sub_r, $sub_v) = $this->xOptionalGraphPattern($v)) && $sub_r) {
      return array($sub_r, $sub_v); 
    }
    if ((list($sub_r, $sub_v) = $this->xGraphGraphPattern($v)) && $sub_r) {
      return array($sub_r, $sub_v);
    }
    $r = array('type' => 'union', 'patterns' => array());
    $sub_v = $v;
    do {
      $proceed = 0;
      if ((list($sub_r, $sub_v) = $this->xGroupGraphPattern($sub_v)) && $sub_r) {
        $r['patterns'][] = $sub_r;
        if ($sub_r = $this->x('UNION', $sub_v)) {
          $sub_v = $sub_r[1];
          $proceed = 1;
        }
      }
    } while ($proceed);
    $pc = count($r['patterns']);
    if ($pc == 1) {
      return array($r['patterns'][0], $sub_v);
    }
    elseif ($pc > 1) {
      return array($r, $sub_v);
    }
    return array(0, $v);
  }

  /* 23 */
  
  function xOptionalGraphPattern($v) {
    if ($sub_r = $this->x('OPTIONAL', $v)) {
      $sub_v = $sub_r[1];
      if ((list($sub_r, $sub_v) = $this->xGroupGraphPattern($sub_v)) && $sub_r) {
        return array(array('type' => 'optional', 'patterns' => $sub_r['patterns']), $sub_v);
      }
      $this->addError('Missing or invalid Group Graph Pattern after OPTIONAL');
    }
    return array(0, $v);
  } 
  
  /* 24.. */
  
  function xGraphGraphPattern($v) {
    if ($sub_r = $this->x('GRAPH', $v)) {
      $sub_v = $sub_r[1];
      $r = array('type' => 'graph', 'var' => '', 'uri' => '', 'patterns' => array());
      if ((list($sub_r, $sub_v) = $this->xVar($sub_v)) && $sub_r) {
        $r['var'] = $sub_r;
      }
      elseif ((list($sub_r, $sub_v) = $this->xIRIref($sub_v)) && $sub_r) {
        $r['uri'] = $sub_r;
      }
      if ($r['var'] || $r['uri']) {
        if ((list($sub_r, $sub_v) = $this->xGroupGraphPattern($sub_v)) && $sub_r) {
          $r['patterns'][] = $sub_r;
          return array($r, $sub_v);
        }
        $this->addError('Missing or invalid Graph Pattern');
      }
    }
    return array(0, $v);
  } 
  
  /* 26.., 27.. */
  
  function xFilter($v) {
    if ($r = $this->x('FILTER', $v)) {
      $sub_v = $r[1];
      if ((list($r, $sub_v) = $this->xBrackettedExpression($sub_v)) && $r) {
        return array($r, $sub_v);
      }
      if ((list($r, $sub_v) = $this->xBuiltInCall($sub_v)) && $r) {
        return array($r, $sub_v);
      }
      if ((list($r, $sub_v) = $this->xFunctionCall($sub_v)) && $r) {
        return array($r, $sub_v);
      }
      $this->addError('Incomplete FILTER');
    }
    return array(0, $v);
  }
  
  /* 28.. */
  
  function xFunctionCall($v) {
    if ((list($r, $sub_v) = $this->xIRIref($v)) && $r) {
      if ((list($sub_r, $sub_v) = $this->xArgList($sub_v)) && $sub_r) {
        return array(array('type' => 'function_call', 'uri' => $r, 'args' => $sub_r), $sub_v);
      }
    }
    return array(0, $v);
  }
  
  /* 29 */
  
  function xArgList($v) {
    $r = array();
    $sub_v = $v;
    $closed = 0;
    if ($sub_r = $this->x('\(', $sub_v)) {
      $sub_v = $sub_r[1];
      do {
        $proceed = 0;
        if ((list($sub_r, $sub_v) = $this->xExpression($sub_v)) && $sub_r) {
          $r[] = $sub_r;
          if ($sub_r = $this->x('\,', $sub_v)) {
            $sub_v = $sub_r[1];
            $proceed = 1;
          }
        }
        if ($sub_r = $this->x('\)', $sub_v)) {
         $sub_v = $sub_r[1];
         $closed = 1;
         $proceed = 0;
        }
      } while ($proceed);
    }
    return $closed ? array($r, $sub_v) : array(0, $v);
  }
  
  /* 30, 31 */
  
  function xConstructTemplate($v) {
    if ($sub_r = $this->x('\{', $v)) {
      $r = array();
      if ((list($sub_r, $sub_v) = $this->xTriplesBlock($sub_r[1])) && is_array($sub_r)) {
        $r = $sub_r;
      }
      if ($sub_r = $this->x('\}', $sub_v)) {
        return array($r, $sub_r[1]);
      }
    }
    return array(0, $v);
  }
    
  /* 46, 47 */
  
  function xExpression($v) {
    if ((list($sub_r, $sub_v) = $this->xConditionalAndExpression($v)) && $sub_r) {
      $r = array('type' => 'expression', 'sub_type' => 'or', 'patterns' => array($sub_r));
      do {
        $proceed = 0;
        if ($sub_r = $this->x('\|\|', $sub_v)) {
          $sub_v = $sub_r[1];
          if ((list($sub_r, $sub_v) = $this->xConditionalAndExpression($sub_v)) && $sub_r) {
            $r['patterns'][] = $sub_r;
            $proceed = 1;
          }
        }
      } while ($proceed);
      return count($r['patterns']) == 1 ? array($r['patterns'][0], $sub_v) : array($r, $sub_v);
    }
    return array(0, $v);
  }
  
  /* 48.., 49.. */
  
  function xConditionalAndExpression($v) {
    if ((list($sub_r, $sub_v) = $this->xRelationalExpression($v)) && $sub_r) {
      $r = array('type' => 'expression', 'sub_type' => 'and', 'patterns' => array($sub_r));
      do {
        $proceed = 0;
        if ($sub_r = $this->x('\&\&', $sub_v)) {
          $sub_v = $sub_r[1];
          if ((list($sub_r, $sub_v) = $this->xRelationalExpression($sub_v)) && $sub_r) {
            $r['patterns'][] = $sub_r;
            $proceed = 1;
          }
        }
      } while ($proceed);
      return count($r['patterns']) == 1 ? array($r['patterns'][0], $sub_v) : array($r, $sub_v);
    }
    return array(0, $v);
  }
  
  /* 50, 51 */

  function xRelationalExpression($v) {
    if ((list($sub_r, $sub_v) = $this->xAdditiveExpression($v)) && $sub_r) {
      $r = array('type' => 'expression', 'sub_type' => 'relational', 'patterns' => array($sub_r));
      do {
        $proceed = 0;
        /* don't mistake '<' + uriref with '<'-operator ("longest token" rule) */
        if ((list($sub_r, $sub_v) = $this->xIRI_REF($sub_v)) && $sub_r) {
          $this->addError('Expected operator, found IRIref: "'.$sub_r.'".');
        }
        if ($sub_r = $this->x('(\!\=|\=\=|\=|\<\=|\>\=|\<|\>)', $sub_v)) {
          $op = $sub_r[1];
          $sub_v = $sub_r[2];
          $r['operator'] = $op;
          if ((list($sub_r, $sub_v) = $this->xAdditiveExpression($sub_v)) && $sub_r) {
            //$sub_r['operator'] = $op;
            $r['patterns'][] = $sub_r;
            $proceed = 1;
          }
        }
      } while ($proceed);
      return count($r['patterns']) == 1 ? array($r['patterns'][0], $sub_v) : array($r, $sub_v);
    }
    return array(0, $v);
  }
  
  /* 52 */
  
  function xAdditiveExpression($v) {
    if ((list($sub_r, $sub_v) = $this->xMultiplicativeExpression($v)) && $sub_r) {
      $r = array('type' => 'expression', 'sub_type' => 'additive', 'patterns' => array($sub_r));
      do {
        $proceed = 0;
        if ($sub_r = $this->x('(\+|\-)', $sub_v)) {
          $op = $sub_r[1];
          $sub_v = $sub_r[2];
          if ((list($sub_r, $sub_v) = $this->xMultiplicativeExpression($sub_v)) && $sub_r) {
            $sub_r['operator'] = $op;
            $r['patterns'][] = $sub_r;
            $proceed = 1;
          }
          elseif ((list($sub_r, $sub_v) = $this->xNumericLiteral($sub_v)) && $sub_r) {
            $r['patterns'][] = array('type' => 'numeric', 'operator' => $op, 'value' => $sub_r);
            $proceed = 1;
          }
        }
      } while ($proceed);
      //return array($r, $sub_v);
      return count($r['patterns']) == 1 ? array($r['patterns'][0], $sub_v) : array($r, $sub_v);
    }
    return array(0, $v);
  }
  
  /* 53 */
  
  function xMultiplicativeExpression($v) {
    if ((list($sub_r, $sub_v) = $this->xUnaryExpression($v)) && $sub_r) {
      $r = array('type' => 'expression', 'sub_type' => 'multiplicative', 'patterns' => array($sub_r));
      do {
        $proceed = 0;
        if ($sub_r = $this->x('(\*|\/)', $sub_v)) {
          $op = $sub_r[1];
          $sub_v = $sub_r[2];
          if ((list($sub_r, $sub_v) = $this->xUnaryExpression($sub_v)) && $sub_r) {
            $sub_r['operator'] = $op;
            $r['patterns'][] = $sub_r;
            $proceed = 1;
          }
        }
      } while ($proceed);
      return count($r['patterns']) == 1 ? array($r['patterns'][0], $sub_v) : array($r, $sub_v);
    }
    return array(0, $v);
  }
  
  /* 54 */
  
  function xUnaryExpression($v) {
    $sub_v = $v;
    $op = '';
    if ($sub_r = $this->x('(\!|\+|\-)', $sub_v)) {
      $op = $sub_r[1];
      $sub_v = $sub_r[2];
    }
    if ((list($sub_r, $sub_v) = $this->xPrimaryExpression($sub_v)) && $sub_r) {
      if (!is_array($sub_r)) {
        $sub_r = array('type' => 'unary', 'expression' => $sub_r);
      }
      elseif ($sub_op = $this->v1('operator', '', $sub_r)) {
        $ops = array('!!' => '', '++' => '+', '--' => '+', '+-' => '-', '-+' => '-');
        $op = isset($ops[$op . $sub_op]) ? $ops[$op . $sub_op] : $op . $sub_op;
      }
      $sub_r['operator'] = $op;
      return array($sub_r, $sub_v);
    }
    return array(0, $v);
  }
  
  /* 55 */
  
  function xPrimaryExpression($v) {
    foreach (array('BrackettedExpression', 'BuiltInCall', 'IRIrefOrFunction', 'RDFLiteral', 'NumericLiteral', 'BooleanLiteral', 'Var', 'Placeholder') as $type) {
      $m = 'x' . $type;
      if ((list($sub_r, $sub_v) = $this->$m($v)) && $sub_r) {
        return array($sub_r, $sub_v);
      }
    }
    return array(0, $v);
  }
  
  /* 56 */
  
  function xBrackettedExpression($v) {
    if ($r = $this->x('\(', $v)) {
      if ((list($r, $sub_v) = $this->xExpression($r[1])) && $r) {
        if ($sub_r = $this->x('\)', $sub_v)) {
          return array($r, $sub_r[1]);
        }
      }
    }
    return array(0, $v);
  }
  
  /* 57.., 58.. */
  
  function xBuiltInCall($v) {
    if ($sub_r = $this->x('(str|lang|langmatches|datatype|bound|sameterm|isiri|isuri|isblank|isliteral|regex)\s*\(', $v)) {
      $r = array('type' => 'built_in_call', 'call' => strtolower($sub_r[1]));
      if ((list($sub_r, $sub_v) = $this->xArgList('(' . $sub_r[2])) && is_array($sub_r)) {
        $r['args'] = $sub_r;
        return array($r, $sub_v);
      }
    }
    return array(0, $v);
  }
  
  /* 59.. */
  
  function xIRIrefOrFunction($v) {
    if ((list($r, $v) = $this->xIRIref($v)) && $r) {
      if ((list($sub_r, $sub_v) = $this->xArgList($v)) && is_array($sub_r)) {
        return array(array('type' => 'function', 'uri' => $r, 'args' => $sub_r), $sub_v);
      }
      return array(array('type' => 'uri', 'uri' => $r), $sub_v);
    }
  }

  /* 70.. @@sync with TurtleParser */
  
  function xIRI_REF($v) {
    if (($r = $this->x('\<(\$\{[^\>]*\})\>', $v)) && ($sub_r = $this->xPlaceholder($r[1]))) {
      return array($r[1], $r[2]);
    }
    elseif ($r = $this->x('\<([^\<\>\s\"\|\^`]*)\>', $v)) {
      return array($r[1] ? $r[1] : true, $r[2]);
    }
    /* allow reserved chars in obvious IRIs */
    elseif ($r = $this->x('\<(https?\:[^\s][^\<\>]*)\>', $v)) {
      return array($r[1] ? $r[1] : true, $r[2]);
    }
    return array(0, $v);
  }
    
}  
