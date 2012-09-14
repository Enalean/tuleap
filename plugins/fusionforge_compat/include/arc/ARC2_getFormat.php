<?php
/**
 * ARC2 format detection function
 *
 * @author Benjamin Nowack <bnowack@semsol.com>
 * @license http://arc.semsol.org/license
 * @package ARC2
 * @version 2010-01-18
*/

function ARC2_getFormat($v, $mtype = '', $ext = '') {
  $r = false;
  /* mtype check (atom, rdf/xml, turtle, n3, mp3, jpg) */
  $r = (!$r && preg_match('/\/atom\+xml/', $mtype)) ? 'atom' : $r;
  $r = (!$r && preg_match('/\/rdf\+xml/', $mtype)) ? 'rdfxml' : $r;
  $r = (!$r && preg_match('/\/(x\-)?turtle/', $mtype)) ? 'turtle' : $r;
  $r = (!$r && preg_match('/\/rdf\+n3/', $mtype)) ? 'n3' : $r;
  $r = (!$r && preg_match('/\/sparql-results\+xml/', $mtype)) ? 'sparqlxml' : $r;
  /* xml sniffing */
  if (
    !$r &&
    /* starts with angle brackets */
    preg_match('/^\s*\<[^\s]/s', $v) &&
    /* has an xmlns:* declaration or a matching pair of tags */
    (preg_match('/\sxmlns\:?/', $v) || preg_match('/\<([^\s]+).+\<\/\\1\>/s', $v)) &&
    /* not a typical ntriples/turtle/n3 file */
    !preg_match('/[\>\"\']\s*\.\s*$/s', $v)
  ) {
    while (preg_match('/^\s*\<\?xml[^\r\n]+\?\>\s*/s', $v)) {
      $v = preg_replace('/^\s*\<\?xml[^\r\n]+\?\>\s*/s', '', $v);
    }
    while (preg_match('/^\s*\<\!--.+?--\>\s*/s', $v)) {
      $v = preg_replace('/^\s*\<\!--.+?--\>\s*/s', '', $v);
    }
    /* doctype checks (html, rdf) */
    $r = (!$r && preg_match('/^\s*\<\!DOCTYPE\s+html[\s|\>]/is', $v)) ? 'html' : $r;
    $r = (!$r && preg_match('/^\s*\<\!DOCTYPE\s+[a-z0-9\_\-]\:RDF\s/is', $v)) ? 'rdfxml' : $r;
    /* markup checks */
    $v = preg_replace('/^\s*\<\!DOCTYPE\s.*\]\>/is', '', $v);
    $r = (!$r && preg_match('/^\s*\<rss\s+[^\>]*version/s', $v)) ? 'rss' : $r;
    $r = (!$r && preg_match('/^\s*\<feed\s+[^\>]+http\:\/\/www\.w3\.org\/2005\/Atom/s', $v)) ? 'atom' : $r;
    $r = (!$r && preg_match('/^\s*\<opml\s/s', $v)) ? 'opml' : $r;
    $r = (!$r && preg_match('/^\s*\<html[\s|\>]/is', $v)) ? 'html' : $r;
    $r = (!$r && preg_match('/^\s*\<sparql\s+[^\>]+http\:\/\/www\.w3\.org\/2005\/sparql\-results\#/s', $v)) ? 'sparqlxml' : $r;
    $r = (!$r && preg_match('/^\s*\<[^\>]+http\:\/\/www\.w3\.org\/2005\/sparql\-results#/s', $v)) ? 'srx' : $r;
    $r = (!$r && preg_match('/^\s*\<[^\s]*RDF[\s\>]/s', $v)) ? 'rdfxml' : $r;
    $r = (!$r && preg_match('/^\s*\<[^\>]+http\:\/\/www\.w3\.org\/1999\/02\/22\-rdf/s', $v)) ? 'rdfxml' : $r;
    
    $r = !$r ? 'xml' : $r;
  }
  /* json|jsonp */
  if (!$r && preg_match('/^[a-z0-9\.\(]*\s*[\{\[].*/s', trim($v))) {
    /* google social graph api */
    $r = (!$r && preg_match('/\"canonical_mapping\"/', $v)) ? 'sgajson' : $r;
    /* crunchbase api */
    $r = (!$r && preg_match('/\"permalink\"/', $v)) ? 'cbjson' : $r;

    $r = !$r ? 'json' : $r;
  }
  /* turtle/n3 */
  $r = (!$r && preg_match('/\@(prefix|base)/i', $v)) ? 'turtle' : $r;
  $r = (!$r && preg_match('/^(ttl)$/', $ext)) ? 'turtle' : $r;
  $r = (!$r && preg_match('/^(n3)$/', $ext)) ? 'n3' : $r;
  /* ntriples */
  $r = (!$r && preg_match('/^\s*(_:|<).+?\s+<[^>]+?>\s+\S.+?\s*\.\s*$/sm', $v)) ? 'ntriples' : $r;
  $r = (!$r && preg_match('/^(nt)$/', $ext)) ? 'ntriples' : $r;
  return $r;
}
