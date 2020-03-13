<?php
// -*-php-*-
rcs_id('$Id: RdfDefinition.php,v 1.6 2004/09/14 10:33:39 rurban Exp $');
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
 * RdfDefinition - Define the RDF vocabulary for a wiki subset.
 * <subject> - <predicate>  - <object>
 *                 <=>
 * page    - link-qualifier - pagelinkedto
 *
 * Similar to the InterWikiMap PageType, with the difference
 * that the initerwiki map links are wiki global, and a RDF vocabulary
 * is only local. Multiple vocabularies may be defined.
 *
 * TODO: Import external standard RDF, Export see below
 * Provide format=rdf methods here, or lib/SemanticWeb.php
 *
 * @author: Reini Urban
 */
class WikiPlugin_RdfDefinition extends WikiPlugin
{
    public function getName()
    {
        return _("RdfDefinition");
    }
    public function getDescription()
    {
        return _("Define the RDF vocabulary for a wiki subset.");
    }
    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.6 $"
        );
    }
    public function getDefaultArguments()
    {
        return array(
                     'pages' => false,        // define affected pageset here?
                     );
    }
    public function handle_plugin_args_cruft(&$argstr, &$args)
    {
        $this->source = $argstr;
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        // just a list of valid predicates
        // comments?
    }
}

// $Log: RdfDefinition.php,v $

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
