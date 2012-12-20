<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 SPARQLScript Parser (SPARQL+ + functions)
author:   Benjamin Nowack
version:  2008-09-22 (Addition: support for FunctionCall) 
*/

ARC2::inc('ARC2_SPARQLPlusParser');

class ARC2_SPARQLScriptParser extends ARC2_SPARQLPlusParser {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_SPARQLScriptParser($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
  }

  /*  */

  function parse($v, $src = '') {
    $this->setDefaultPrefixes();
    $this->base = $src ? $this->calcBase($src) : ARC2::getScriptURI();
    $this->blocks = array();
    $this->r = array('base' => '', 'vars' => array(), 'prefixes' => $this->prefixes);
    do {
      $proceed = 0;
      if ((list($r, $v) = $this->xScriptBlock($v)) && $r) {
        $this->blocks[] = $r;
        $proceed = 1;
      }
      $this->unparsed_code = trim($v);
    } while ($proceed);
    if (trim($this->unparsed_code) && !$this->getErrors()) {
      $rest = preg_replace('/[\x0a|\x0d]/i', ' ', substr($this->unparsed_code, 0, 30));
      $msg = trim($rest) ? 'Could not properly handle "' . $rest . '"' : 'Syntax Error';
      $this->addError($msg);
    }
  }
  
  function getScriptBlocks() {
    return $this->v('blocks', array());
  }

  /*  */

  function xScriptBlock($v) {
    /* comment removal */  
    while (preg_match('/^\s*(\#[^\xd\xa]*)(.*)$/si', $v, $m)) $v = $m[2];
    /* BaseDecl */
    if ((list($sub_r, $v) = $this->xBaseDecl($v)) && $sub_r) {
      $this->base = $sub_r;
    }
    /* PrefixDecl */
    while ((list($r, $v) = $this->xPrefixDecl($v)) && $r) {
      $this->prefixes[$r['prefix']] = $r['uri'];
    }
    /* EndpointDecl */
    if ((list($r, $v) = $this->xEndpointDecl($v)) && $r) {
      return array($r, $v);
    }
    /* Return */
    if ((list($r, $v) = $this->xReturn($v)) && $r) {
      return array($r, $v);
    }
    /* Assignment */
    if ((list($r, $v) = $this->xAssignment($v)) && $r) {
      return array($r, $v);
    }
    /* IFBlock */
    if ((list($r, $v) = $this->xIFBlock($v)) && $r) {
      return array($r, $v);
    }
    /* FORBlock */
    if ((list($r, $v) = $this->xFORBlock($v)) && $r) {
      return array($r, $v);
    }
    /* String */
    if ((list($r, $v) = $this->xString($v)) && $r) {
      return array($r, $v);
    }
    /* FunctionCall */
    if ((list($r, $v) = $this->xFunctionCall($v)) && $r) {
      return array($r, ltrim($v, ';'));
    }
    /* Query */
    $prev_r = $this->r;
    $this->r = array('base' => '', 'vars' => array(), 'prefixes' => $this->prefixes);
    if ((list($r, $rest) = $this->xQuery($v)) && $r) {
      $q = $rest ? trim(substr($v, 0, -strlen($rest))) : trim($v);
      $v = $rest;
      $r = array_merge($this->r, array(
        'type' => 'query',
        'query_type' => $r['type'],
        'query' => $q,
        //'prefixes' => $this->prefixes,
        'base' => $this->base,
        //'infos' => $r
      ));
      return array($r, $v);
    }
    else {
      $this->r = $prev_r;
    }
    return array(0, $v);
  }

  function xBlockSet($v) {
    if (!$r = $this->x("\{", $v)) return array(0, $v);
    $blocks = array();
    $sub_v = $r[1];
    while ((list($sub_r, $sub_v) = $this->xScriptBlock($sub_v)) && $sub_r) {
      $blocks[] = $sub_r;
    }
    if (!$sub_r = $this->x("\}", $sub_v)) return array(0, $v);
    $sub_v = $sub_r[1];
    return array(array('type' => 'block_set', 'blocks' => $blocks), $sub_v);
  }
  
  /* s2 */
  
  function xEndpointDecl($v) {
    if ($r = $this->x("ENDPOINT\s+", $v)) {
      if ((list($r, $sub_v) = $this->xIRI_REF($r[1])) && $r) {
        $r = $this->calcURI($r, $this->base);
        if ($sub_r = $this->x('\.', $sub_v)) {
          $sub_v = $sub_r[1];
        }
        return array(
          array('type' => 'endpoint_decl', 'endpoint' => $r),
          $sub_v
        );
      }
    }
    return array(0, $v);
  }
  
  /* s3 */
  
  function xAssignment($v) {
    /* Var */
    list($r, $sub_v) = $this->xVar($v);
    if (!$r) return array(0, $v);
    $var = $r;
    /* := | = */
    if (!$sub_r = $this->x("\:?\=", $sub_v)) return array(0, $v);
    $sub_v = $sub_r[1];
    /* try String */
    list($r, $sub_v) = $this->xString($sub_v);
    if ($r) return array(array('type' => 'assignment', 'var' => $var, 'sub_type' => 'string', 'string' => $r), ltrim($sub_v, '; '));
    /* try VarMerge */
    list($r, $sub_v) = $this->xVarMerge($sub_v);
    if ($r) return array(array('type' => 'assignment', 'var' => $var, 'sub_type' => 'var_merge', 'var2' => $r[0], 'var3' => $r[1]), ltrim($sub_v, '; '));
    /* try Var */
    list($r, $sub_v) = $this->xVar($sub_v);
    if ($r) return array(array('type' => 'assignment', 'var' => $var, 'sub_type' => 'var', 'var2' => $r), ltrim($sub_v, '; '));
    /* try function */
    list($r, $sub_v) = $this->xFunctionCall($sub_v);
    if ($r) return array(array('type' => 'assignment', 'var' => $var, 'sub_type' => 'function_call', 'function_call' => $r), ltrim($sub_v, '; '));
    /* try Placeholder */
    list($r, $sub_v) = $this->xPlaceholder($sub_v);
    if ($r) return array(array('type' => 'assignment', 'var' => $var, 'sub_type' => 'placeholder', 'placeholder' => $r), ltrim($sub_v, '; '));
    /* try query */
    $prev_r = $this->r;
    $this->r = array('base' => '', 'vars' => array(), 'prefixes' => $this->prefixes);
    list($r, $rest) = $this->xQuery($sub_v);
    if (!$r) {
      $this->r = $prev_r;
      return array(0, $v);
    }
    else {
      $q = $rest ? trim(substr($sub_v, 0, -strlen($rest))) : trim($sub_v);
      return array(
        array(
          'type' => 'assignment', 
          'var' => $var,
          'sub_type' => 'query',
          'query' => array_merge($this->r, array(
            'type' => 'query',
            'query_type' => $r['type'],
            'query' => $q,
            'base' => $this->base,
          )),
        ),
        ltrim($rest, '; ')
      );
    }
  }

  function xReturn($v) {
    if ($r = $this->x("return\s+", $v)) {
      /* fake assignment which accepts same right-hand values */
      $sub_v = '$__return_value__ := ' . $r[1];
      if ((list($r, $sub_v) = $this->xAssignment($sub_v)) && $r) {
        $r['type'] = 'return';
        return array($r, $sub_v);
      }
    }
    return array(0, $v);
  }
  
  /* s4 'IF' BrackettedExpression '{' Script '}' ( 'ELSE' '{' Script '}')?  */
  
  function xIFBlock($v) {
    if ($r = $this->x("IF\s*", $v)) {
      if ((list($sub_r, $sub_v) = $this->xBrackettedExpression($r[1])) && $sub_r) {
        $cond = $sub_r;
        if ((list($sub_r, $sub_v) = $this->xBlockSet($sub_v)) && $sub_r) {
          $blocks = $sub_r['blocks'];
          /* else */
          $else_blocks = array();
          $rest = $sub_v;
          if ($sub_r = $this->x("ELSE\s*", $sub_v)) {
            if ((list($sub_r, $sub_v) = $this->xBlockSet($sub_r[1])) && $sub_r) {
              $else_blocks = $sub_r['blocks'];
            }
            else {
              $sub_v = $rest;
            }
          }
          return array(
            array(
              'type' => 'ifblock',
              'condition' => $cond,
              'blocks' => $blocks,
              'else_blocks' => $else_blocks,
            ),
            $sub_v
          );
        }
      }
    }
    return array(0, $v);
  }
  
  /* s5 'FOR' '(' Var 'IN' Var ')' '{' Script '}' */
  
  function xFORBlock($v) {
    if ($r = $this->x("FOR\s*\(\s*[\$\?]([^\s]+)\s+IN\s+[\$\?]([^\s]+)\s*\)", $v)) {/* @@todo split into sub-patterns? */
      $iterator = $r[1];
      $set_var = $r[2];
      $sub_v = $r[3];
      if ((list($sub_r, $sub_v) = $this->xBlockSet($sub_v)) && $sub_r) {
        return array(
          array(
            'type' => 'forblock',
            'set' => $set_var,
            'iterator' => $iterator,
            'blocks' => $sub_r['blocks']
          ),
          $sub_v
        );
      }
    }
    return array(0, $v);
  }
  
  /* s6 Var '+' Var */
  
  function xVarMerge($v) {
    if ((list($sub_r, $sub_v) = $this->xVar($v)) && $sub_r) {
      $var1 = $sub_r;
      if ($sub_r = $this->x("\+", $sub_v)) {
        $sub_v = $sub_r[1];
        if ((list($sub_r, $sub_v) = $this->xVar($sub_v)) && $sub_r) {
          return array(
            array($var1, $sub_r),
            $sub_v
          );
        }
      }
    }
    return array(0, $v);
  }
  
}  
