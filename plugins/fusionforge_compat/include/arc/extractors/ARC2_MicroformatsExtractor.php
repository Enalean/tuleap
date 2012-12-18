<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 microformats Extractor
author:   Benjamin Nowack
version:  
*/

ARC2::inc('ARC2_PoshRdfExtractor');

class ARC2_MicroformatsExtractor extends ARC2_PoshRdfExtractor {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_MicroformatsExtractor($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->terms = $this->getTerms();
    $this->ns_prefix = 'mf';
    $this->a['ns']['mf'] = 'http://poshrdf.org/ns/mf#';
    $this->caller->detected_formats['posh-rdf'] = 1;
  }

  /*  */
  
  function preProcessNode($n) {
    if (!$n) return $n;
    /* remove existing poshRDF hooks */
    if (!is_array($n['a'])) $n['a'] = array();
    $n['a']['class'] = isset($n['a']['class']) ? preg_replace('/\s?rdf\-(s|p|o|o-xml)/', '', $n['a']['class']): '';
    if (!isset($n['a']['rel'])) $n['a']['rel'] = '';
    /* inject poshRDF hooks */
    foreach ($this->terms as $term => $infos) {
      if ((!in_array('rel', $infos) && $this->hasClass($n, $term)) || $this->hasRel($n, $term)) {
        if ($this->v('scope', '', $infos)) $infos[] = 'p';
        foreach (array('s', 'p', 'o', 'o-xml') as $type) {
          if (in_array($type, $infos)) {
            $n['a']['class'] .= ' rdf-' . $type;
            $n['a']['class'] = preg_replace('/(^|\s)' . $term . '(\s|$)/s', '\\1mf-' . $term . '\\2', $n['a']['class']);
            $n['a']['rel'] = preg_replace('/(^|\s)' . $term . '(\s|$)/s', '\\1mf-' . $term . '\\2', $n['a']['rel']);
          }
        }
      }
    }
    $n['a']['class m'] = split(' ', $n['a']['class']);
    $n['a']['rel m'] = split(' ', $n['a']['rel']);
    return $n;
  }
  
  function getPredicates($n, $ns) {
    $ns = array('mf' => $ns['mf']);
    return parent::getPredicates($n, $ns);
  }
  
  function tweakObject($o, $p, $ct) {
    $ns = $ct['ns']['mf'];
    /* rel-tag, skill => extract from URL */
    if (in_array($p, array($ns . 'tag', $ns . 'skill'))) {
      $o = preg_replace('/^.*\/([^\/]+)/', '\\1', trim($o, '/'));
      $o = urldecode(rawurldecode($o));
    }
    return $o;
  }
  
  /*  */
  
  function getTerms() {
    /* no need to define 'p' if scope is not empty */
    return array(
      'acquaintance' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'additional-name' => array('o', 'scope' => array('n')),
      'adr' => array('s', 'o', 'scope' => array('_doc', 'vcard')),
      'affiliation' => array('s', 'o', 'scope' => array('hresume')),
      'author' => array('s', 'o', 'scope' => array('hentry')),
      'bday' => array('o', 'scope' => array('vcard')),
      'bio' => array('o', 'scope' => array('vcard')),
      'best' => array('o', 'scope' => array('hreview')),
      'bookmark' => array('o', 'scope' => array('_doc', 'hentry', 'hreview')),
      'class' => array('o', 'scope' => array('vcard', 'vevent')),
      'category' => array('o', 's', 'scope' => array('vcard', 'vevent')),
      'child' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'co-resident' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'co-worker' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'colleague' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'contact' => array('o', 'scope' => array('_doc', 'hresume', 'hentry')),
      'country-name' => array('o', 'scope' => array('adr')),
      'crush' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'date' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'description' => array('o', 'scope' => array('vevent', 'hreview', 'xfolkentry')),
      'directory' => array('o', 'rel', 'scope' => array('_doc', 'hfeed', 'hentry', 'hreview')),
      'dtend' => array('o', 'scope' => array('vevent')),
      'dtreviewed' => array('o', 'scope' => array('hreview')),
      'dtstamp' => array('o', 'scope' => array('vevent')),
      'dtstart' => array('o', 'scope' => array('vevent')),
      'duration' => array('o', 'scope' => array('vevent')),
      'education' => array('s', 'o', 'scope' => array('hresume')),
      'email' => array('s', 'o', 'scope' => array('vcard')),
      'entry-title' => array('o', 'scope' => array('hentry')),
      'entry-content' => array('o-xml', 'scope' => array('hentry')),
      'entry-summary' => array('o', 'scope' => array('hentry')),
      'experience' => array('s', 'o', 'scope' => array('hresume')),
      'extended-address' => array('o', 'scope' => array('adr')),
      'family-name' => array('o', 'scope' => array('n')),
      'fn' => array('o', 'plain', 'scope' => array('vcard', 'item')),
      'friend' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'geo' => array('s', 'scope' => array('_doc', 'vcard', 'vevent')),
      'given-name' => array('o', 'scope' => array('n')),
      'hentry' => array('s', 'o', 'scope' => array('_doc', 'hfeed')),
      'hfeed' => array('s', 'scope' => array('_doc')),
      'honorific-prefix' => array('o', 'scope' => array('n')),
      'honorific-suffix' => array('o', 'scope' => array('n')),
      'hresume' => array('s', 'scope' => array('_doc')),
      'hreview' => array('s', 'scope' => array('_doc')),
      'item' => array('s', 'scope' => array('hreview')),
      'key' => array('o', 'scope' => array('vcard')),
      'kin' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'label' => array('o', 'scope' => array('vcard')),
      'last-modified' => array('o', 'scope' => array('vevent')),
      'latitude' => array('o', 'scope' => array('geo')),
      'license' => array('o', 'rel', 'scope' => array('_doc', 'hfeed', 'hentry', 'hreview')),
      'locality' => array('o', 'scope' => array('adr')),
      'location' => array('o', 'scope' => array('vevent')),
      'logo' => array('o', 'scope' => array('vcard')),
      'longitude' => array('o', 'scope' => array('geo')),
      'mailer' => array('o', 'scope' => array('vcard')),
      'me' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'met' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'muse' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'n' => array('s', 'o', 'scope' => array('vcard')),
      'neighbor' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'nickname' => array('o', 'plain', 'scope' => array('vcard')),
      'nofollow' => array('o', 'rel', 'scope' => array('_doc')),
      'note' => array('o', 'scope' => array('vcard')),
      'org' => array('o', 'xplain', 'scope' => array('vcard')),
      'parent' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'permalink' => array('o', 'scope' => array('hreview')),
      'photo' => array('o', 'scope' => array('vcard', 'item')),
      'post-office-box' => array('o', 'scope' => array('adr')),
      'postal-code' => array('o', 'scope' => array('adr')),
      'publication' => array('s', 'o', 'scope' => array('hresume')),
      'published' => array('o', 'scope' => array('hentry')),
      'rating' => array('o', 'scope' => array('hreview')),
      'region' => array('o', 'scope' => array('adr')),
      'rev' => array('o', 'scope' => array('vcard')),
      'reviewer' => array('s', 'o', 'scope' => array('hreview')),
      'role' => array('o', 'plain', 'scope' => array('vcard')),
      'sibling' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'skill' => array('o', 'scope' => array('hresume')),
      'sort-string' => array('o', 'scope' => array('vcard')),
      'sound' => array('o', 'scope' => array('vcard')),
      'spouse' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'status' => array('o', 'plain', 'scope' => array('vevent')),
      'street-address' => array('o', 'scope' => array('adr')),
      'summary' => array('o', 'scope' => array('vevent', 'hreview', 'hresume')),
      'sweetheart' => array('o', 'rel', 'scope' => array('_doc', 'hentry')),
      'tag' => array('o', 'rel', 'scope' => array('_doc', 'category', 'hfeed', 'hentry', 'skill', 'hreview', 'xfolkentry')),
      'taggedlink' => array('o', 'scope' => array('xfolkentry')),
      'title' => array('o', 'scope' => array('vcard')),
      'type' => array('o', 'scope' => array('adr', 'email', 'hreview', 'tel')),
      'tz' => array('o', 'scope' => array('vcard')),
      'uid' => array('o', 'scope' => array('vcard', 'vevent')),
      'updated' => array('o', 'scope' => array('hentry')),
      'url' => array('o', 'scope' => array('vcard', 'vevent', 'item')),
      'value' => array('o', 'scope' => array('email', 'adr', 'tel')),
      'vcard' => array('s', 'scope' => array('author', 'reviewer', 'affiliation', 'contact')),
      'version' => array('o', 'scope' => array('hreview')),
      'vevent' => array('s', 'scope' => array('_doc')),
      'worst' => array('o', 'scope' => array('hreview')),
      'xfolkentry' => array('s', 'scope' => array('_doc')),
    );
  }

  /*  */
  
}
