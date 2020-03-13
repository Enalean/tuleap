<?php
/**
 * What to do on ?format=rdf  What to do on ?format=owl
 *
 * Map relations on a wikipage to a RDF ressource to build a "Semantic Web"
 * - a web ontology frontend compatible to OWL (web ontology language).
 * http://www.w3.org/2001/sw/Activity
 * Simple RDF ontologies contain facts and rules, expressed by RDF triples:
 *   Subject (page) -> Predicate (verb, relation) -> Object (links)
 * OWL extents that to represent a typical OO framework.
 *  OO predicates:
 *    is_a, has_a, ...
 *  OWL predicates:
 *    subClassOf, restrictedBy, onProperty, intersectionOf, allValuesFrom, ...
 *    someValuesFrom, unionOf, equivalentClass, disjointWith, ...
 *    plus the custom vocabulary (ontology): canRun, canBite, smellsGood, ...
 *  OWL Subjects: Class, Restriction, ...
 *  OWL Properties: type, label, comment, ...
 * DAML should also be supported.
 *
 * Purpose:
 * - Another way to represent various KB models in various DL languages. (OWL/DAML/other DL)
 * - Frontend to various KB model reasoners and representations.
 * - Generation/update of static wiki pages based on external OWL/DL/KB (=> ModelTest/Categories)
 *   KB Blackboard and Visualization.
 * - OWL generation based on static wiki pages (ModelTest?format=owl)
 *
 * Facts: (may be represented by special links on a page)
 *  - Each page must be representable with an unique URL.
 *  - Each fact must be representable with an unique RDF triple.
 *  - A class is represented by a category page.
 *  - To represent more expressive description logic, "enriched"
 *    links will not be enough (? variable symbolic objects).
 *
 * Rules: (may be represented by special content on a page)
 *  - Syntax: reasoner backend specific, or common or ?
 *
 * RDF Triple: (representing facts)
 *   Subject (page) -> Predicate (verb, relation) -> Object (links)
 * Subject: a page
 * Verb:
 *   Special link qualifiers represent RDF triples, based on RDF standard notation.
 *   See RDF standard DTD's on daml.org and w3.org, plus your custom predicates.
 *   (need your own DTD)
 *   Example: page [Ape] isa:Animal, ...
 * Object: special links on a page.
 * Class: WikiCategory
 * Model: Basepage for a KB. (parametrizeable pages or copies of modified snapshots?)
 *
 * DL: Description Logic
 * KB: Knowledge Base
 *
 * Discussion:
 * Of course *real* expert systems ("reasoners") will help/must be used in
 * optimization and maintainance of the SemanticWeb KB (Knowledge
 * Base). Hooks will be tested to KM (an interactive KB playground),
 * LISA (standard unifier), FaCT, RACER, ...

 * Maybe also ZEBU (parser generator) is needed to convert the wiki KB
 * syntax to the KB reasoner backend (LISA, KM, CLIPS, JESS, FaCT,
 * ...) and forth.

 * pOWL is a simple php backend with some very simple AI logic in PHP,
 * though I strongly doubt the usefulness of reasoners not written in
 * Common Lisp.
 *
 * SEAL (omntoweb.org) is similar to that, just on top of the Zope CMF.
 * FaCT uses e.g. this KB DTD:
<!ELEMENT KNOWLEDGEBASE (DEFCONCEPT|DEFROLE|IMPLIESC|EQUALC|IMPLIESR|EQUALR|TRANSITIVE|FUNCTIONAL)*>
<!ELEMENT CONCEPT (PRIMITIVE|TOP|BOTTOM|AND|OR|NOT|SOME|ALL|ATMOST|ATLEAST)>
<!ELEMENT ROLE (PRIMROLE|INVROLE)>
... (facts and rules described in XML)
 *
 * Links:
 *   http://phpwiki.org/SemanticWeb,
 *   http://en.wikipedia.org/wiki/Knowledge_representation
 *   http://www.ontoweb.org/
 *   http://www.semwebcentral.org/ (OWL on top of GForge)
 *
 *
 * Author: Reini Urban <rurban@x-ray.at>
 */
/*============================================================================*/
/*
 Copyright 2004 Reini Urban

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * RdfWriter - A class to represent a wikipage as RDF. Supports ?format=rdf
 *
 * RdfWriter
 *  - RssWriter
 *    - RecentChanges (RecentChanges?format=rss)
 *      channel: ... item: ...
 */
include_once('lib/RssWriter.php');
class RdfWriter extends RssWriter // in fact it should be rewritten to be other way round.
{
    public function __construct()
    {
        $this->XmlElement(
            'rdf:RDF',
            array('xmlns' => "http://purl.org/rss/1.0/",
            'xmlns:rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#')
        );

        $this->_modules = array(
            //Standards
        'content'    => "http://purl.org/rss/1.0/modules/content/",
        'dc'    => "http://purl.org/dc/elements/1.1/",
        );

        $this->_uris_seen = array();
        $this->_items = array();
    }
}

/**
 * OwlWriter - A class to represent a set of wiki pages (a DL model) as OWL.
 * Supports ?format=owl
 *
 * OwlWriter
 *  - RdfWriter
 *  - Reasoner
*/
class OwlWriter extends RdfWriter
{
}

/**
 * ModelWriter - Export a KB as set of wiki pages.
 * Probably based on some convenient DL expression syntax. (deffact, defrule, ...)
 *
 * ModelWriter
 *  - OwlWriter
 *  - ReasonerBackend
*/
class ModelWriter extends OwlWriter
{
}


/**
 * ReasonerBackend - hooks to reasoner backends.
 * via http as with DIG,
 * or internally
 */
class ReasonerBackend
{
    public function __construct()
    {
    }
    /**
     * transform to reasoner syntax
     */
    public function transformTo()
    {
    }
    /**
     * transform from reasoner syntax
     */
    public function transformFrom()
    {
    }
    /**
     * call the reasoner
     */
    public function invoke()
    {
    }
}

class ReasonerBackend_LISA extends ReasonerBackend
{
}

class ReasonerBackend_KM extends ReasonerBackend
{
}


// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
