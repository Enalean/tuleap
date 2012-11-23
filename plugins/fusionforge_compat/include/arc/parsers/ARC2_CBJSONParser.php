<?php
/**
 * ARC2 CrunchBase API JSON Parser
 *
 * @author Benjamin Nowack <bnowack@semsol.com>
 * @license http://arc.semsol.org/license
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version 2010-03-25
*/

ARC2::inc('JSONParser');

class ARC2_CBJSONParser extends ARC2_JSONParser {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_CBJSONParser($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {/* reader */
    parent::__init();
    $this->base = 'http://cb.semsol.org/';
    $this->rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    $this->default_ns = $this->base . 'ns#';
    $this->nsp = array($this->rdf => 'rdf');
  }
  
  /*  */

  function done() {
    $this->extractRDF();
  }
  
  function extractRDF() {
    $struct = $this->struct;
    if ($type = $this->getStructType($struct)) {
      $s = $this->getResourceID($struct, $type);
      /* rdf:type */
      $this->addT($s, $this->rdf . 'type', $this->default_ns . $this->camelCase($type), 'uri', 'uri');
      /* explicit triples */
      $this->extractResourceRDF($struct, $s);
    }
  }
  
  function getStructType($struct, $rel = '') {
    /* url-based */
    if ($url = $this->v('crunchbase_url', '', $struct)) {
      return preg_replace('/^.*crunchbase\.com\/([^\/]+)\/.*$/', '\\1', $url);
    }
    /* rel-based */
    if ($rel == 'person') return 'person';
    if ($rel == 'company') return 'company';
    if ($rel == 'acquiring_company') return 'company';
    if ($rel == 'firm') return 'company';
    if ($rel == 'provider') return 'service-provider';
    /* struct-based */
    if (isset($struct['_type'])) return $struct['_type'];
    if (isset($struct['round_code'])) return 'funding_round';
    if (isset($struct['products'])) return 'company';
    if (isset($struct['first_name'])) return 'person';
    if (isset($struct['investments'])) return 'financial-organization';
    if (isset($struct['launched_year'])) return 'product';
    if (isset($struct['providerships']) && is_array($struct['providerships'])) return 'service-provider';
    return '';
  }
  
  function getResourceID($struct, $type) {
    if ($type && isset($struct['permalink'])) {
      return $this->base . $type . '/' . $struct['permalink'] . '#self';
    }
    return $this->createBnodeID();
  }
  
  function getPropertyURI($name, $ns = '') {
    if (!$ns) $ns = $this->default_ns;
    if (preg_match('/^(product|funding_round|investment|acquisition|.+ship|office|milestone|.+embed|.+link|degree|fund)s/', $name, $m)) $name = $m[1];
    if ($name == 'tag_list') $name = 'tag';
    if ($name == 'competitions') $name = 'competitor';
    return $ns . $name;
  }

  function createSubURI($s, $k, $pos) {
    $s = str_replace('#self', '/', $s);
    if (preg_match('/(office|ship|investment|milestone|fund|embed|link)s$/', $k)) $k = substr($k, 0, -1);
    return $s . $k . '-' . ($pos + 1) . '#self';
  }
  
  /*  */
  
  function extractResourceRDF($struct, $s, $pos = 0) {
    $s_type = preg_match('/^\_\:/', $s) ? 'bnode' : 'uri';
    $date_prefixes = array();
    foreach ($struct as $k => $v) {
      if ($k == 'acquisition') $k = 'exit';
      if (preg_match('/^(.*)\_(year|month|day)$/', $k, $m)) {
        if (!in_array($m[1], $date_prefixes)) $date_prefixes[] = $m[1];
      }
      $sub_m = 'extract' . $this->camelCase($k) . 'RDF';
      if (method_exists($this, $sub_m)) {
        $this->$sub_m($s, $s_type, $v);
        continue;
      }
      $p = $this->getPropertyURI($k);
      if (!$v) continue;
      /* simple, single v */
      if (!is_array($v)) {
        $o_type = preg_match('/^[a-z]+\:[^\s]+$/is', $v) ? 'uri' : 'literal';
        $v = trim($v);
        if (preg_match('/^https?\:\/\/[^\/]+$/', $v)) $v .= '/';
        $this->addT($s, $p, $v, $s_type, $o_type);
        /* rdfs:label */
        if ($k == 'name') $this->addT($s, 'http://www.w3.org/2000/01/rdf-schema#label', $v, $s_type, $o_type);
        /* dc:identifier */
        //if ($k == 'permalink') $this->addT($s, 'http://purl.org/dc/elements/1.1/identifier', $v, $s_type, $o_type);
      }
      /* structured, single v */
      elseif (!$this->isFlatArray($v)) {
        if ($o_type = $this->getStructType($v, $k)) {/* known type */
          $o = $this->getResourceID($v, $o_type);
          $this->addT($s, $p, $o, $s_type, 'uri');
          $this->addT($o, $this->rdf . 'type', $this->default_ns . $this->camelCase($o_type), 'uri', 'uri');
        }
        else {/* unknown type */
          $o = $this->createSubURI($s, $k, $pos);
          $this->addT($s, $p, $o, $s_type, 'uri');
          $this->extractResourceRDF($v, $o);
        }
      }
      /* value list */
      else {
        foreach ($v as $sub_pos => $sub_v) {
          $this->extractResourceRDF(array($k => $sub_v), $s, $sub_pos);
        }
      }
    }
    /* infer XSD triples */
    foreach ($date_prefixes as $prefix) {
      $this->inferDate($prefix, $s, $struct);
    }
  }

  function isFlatArray($v) {
    foreach ($v as $k => $sub_v) {
      return is_numeric($k) ? 1 : 0;
    }
  }
  
  /*  */
  
  function extractTagListRDF($s, $s_type,  $v) {
    if (!$v) return 0;
    $tags = split(', ', $v);
    foreach ($tags as $tag) {
      if (!trim($tag)) continue;
      $this->addT($s, $this->getPropertyURI('tag'), $tag, $s_type, 'literal');
    }
  }

  function extractImageRDF($s, $s_type, $v, $rel = 'image') {
    if (!$v) return 1;
    $sizes = $v['available_sizes'];
    foreach ($sizes as $size) {
      $w = $size[0][0];
      $h = $size[0][1];
      $img = 'http://www.crunchbase.com/' . $size[1];
      $this->addT($s, $this->getPropertyURI($rel), $img, $s_type, 'uri');
      $this->addT($img, $this->getPropertyURI('width'), $w, 'uri', 'literal');
      $this->addT($img, $this->getPropertyURI('height'), $h, 'uri', 'literal');
    }
  }

  function extractScreenshotsRDF($s, $s_type, $v) {
    if (!$v) return 1;
    foreach ($v as $sub_v) {
      $this->extractImageRDF($s, $s_type, $sub_v, 'screenshot');
    }
  }
  
  function extractProductsRDF($s, $s_type, $v) {
    foreach ($v as $sub_v) {
      $o = $this->getResourceID($sub_v, 'product');
      $this->addT($s, $this->getPropertyURI('product'), $o, $s_type, 'uri');
    }
  }

  function extractCompetitionsRDF($s, $s_type, $v) {
    foreach ($v as $sub_v) {
      $o = $this->getResourceID($sub_v['competitor'], 'company');
      $this->addT($s, $this->getPropertyURI('competitor'), $o, $s_type, 'uri');
    }
  }

  function extractFundingRoundsRDF($s, $s_type, $v) {
    foreach ($v as $pos => $sub_v) {
      $o = $this->createSubURI($s, 'funding_round', $pos);
      $this->addT($s, $this->getPropertyURI('funding_round'), $o, $s_type, 'uri');
      $this->extractResourceRDF($sub_v, $o, $pos);
    }
  }

  function extractInvestmentsRDF($s, $s_type, $v) {
    foreach ($v as $pos => $sub_v) {
      /* incoming */
      foreach (array('person' => 'person', 'company' => 'company', 'financial_org' => 'financial-organization') as $k => $type) {
        if (isset($sub_v[$k])) $this->addT($s, $this->getPropertyURI('investment'), $this->getResourceID($sub_v[$k], $type), $s_type, 'uri');
      }
      /* outgoing */
      if (isset($sub_v['funding_round'])) {
        $o = $this->createSubURI($s, 'investment', $pos);
        $this->addT($s, $this->getPropertyURI('investment'), $o, $s_type, 'uri');
        $this->extractResourceRDF($sub_v['funding_round'], $o, $pos);
      }
    }
  }
  
  function extractExternalLinksRDF($s, $s_type, $v) {
    foreach ($v as $sub_v) {
      $href = $sub_v['external_url'];
      if (preg_match('/^https?\:\/\/[^\/]+$/', $href)) $href .= '/';
      $this->addT($s, $this->getPropertyURI('external_link'), $href, $s_type, 'uri');
      $this->addT($href, $this->getPropertyURI('title'), $sub_v['title'], $s_type, 'literal');
    }
  }

  function extractWebPresencesRDF($s, $s_type, $v) {
    foreach ($v as $sub_v) {
      $href = $sub_v['external_url'];
      if (preg_match('/^https?\:\/\/[^\/]+$/', $href)) $href .= '/';
      $this->addT($s, $this->getPropertyURI('web_presence'), $href, $s_type, 'uri');
      $this->addT($href, $this->getPropertyURI('title'), $sub_v['title'], $s_type, 'literal');
    }
  }

  function extractCreatedAtRDF($s, $s_type,  $v) {
    $v = $this->getAPIDateXSD($v);
    $this->addT($s, $this->getPropertyURI('created_at'), $v, $s_type, 'literal');
  }

  function extractUpdatedAtRDF($s, $s_type,  $v) {
    $v = $this->getAPIDateXSD($v);
    $this->addT($s, $this->getPropertyURI('updated_at'), $v, $s_type, 'literal');
  }

  function getAPIDateXSD($val) {
    //Fri Jan 16 21:11:48 UTC 2009
    if (preg_match('/^[a-z]+ ([a-z]+) ([0-9]+) ([0-9]{2}\:[0-9]{2}\:[0-9]{2}) UTC ([0-9]{4})/i', $val, $m)) {
      $months = array('Jan' => '01', 'Feb' => '02', 'Mar' =>'03', 'Apr' => '04', 'May' => '05', 'Jun' => '06', 'Jul' => '07', 'Aug' => '08', 'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12');
      return $m[4] . '-' . $months[$m[1]] . '-' . $m[2] . 'T' . $m[3] . 'Z';
    }
    return '2000-01-01';
  }

  /*  */

  function inferDate($prefix, $s, $struct) {
    $s_type = preg_match('/^\_\:/', $s) ? 'bnode' : 'uri';
    $r = '';
    foreach (array('year', 'month', 'day') as $suffix) {
      $val = $this->v1($prefix . '_' . $suffix, '00', $struct);
      $r .= ($r ? '-' : '') . str_pad($val, 2, '0', STR_PAD_LEFT);
    }
    if ($r != '00-00-00') {
      $this->addT($s, $this->getPropertyURI($prefix . '_date'), $r, $s_type, 'literal');
    }
  }
  
}
