<?php
// -*-php-*-
rcs_id('$Id: CategoryPage.php,v 1.2 2004/07/08 20:30:07 rurban Exp $');
/**
 Copyright 1999, 2000, 2001, 2002 $ThePhpWikiProgrammingTeam

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

// require_once('lib/InlineParser.php');
require_once('lib/BlockParser.php');

/**
 * CategoryPage plugin.
 *
 * This puts boilerplate text on a category page to make it easily usable
 * by novices.
 *
 * Usage:
 * <?plugin-form CategoryPage ?>
 *
 * It finds the file templates/categorypage.tmpl, then loads it with a few
 * variables substituted.
 *
 * This has only been used in wikilens.org.
 */
class WikiPlugin_CategoryPage extends WikiPlugin
{
    public function getName()
    {
        return _("CategoryPage");
    }

    public function getDescription()
    {
        return _("Create a Wiki page.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.2 $"
        );
    }

    public function getDefaultArguments()
    {
        return array(// Assume the categories are listed on the HomePage
                     'exclude'              => false,
                     'pagename'             => '[pagename]',
                     'plural'               => false,
                     'singular'             => false,
                     'self_on_create'       => true,
                     'showbuds'             => false);
    }

    public function run($dbi, $argstr, &$request)
    {
        $args = $this->getArgs($argstr, $request);

        if (empty($args['singular'])) {
            $args['singular'] = $args['pagename'];
        }
        if (empty($args['plural'])) {
            $args['plural'] = $args['singular'] . 's';
        }

        return new Template(
            'categorypage',
            $request,
            array('EXCLUDE' => $args['exclude'],
                                  'PAGENAME' => $args['pagename'],
                                  'PLURAL' => $args['plural'],
                                  'SHOWBUDS' => $args['showbuds'],
                                  'SELF_ON_CREATE' => $args['self_on_create'],
            'SINGULAR' => $args['singular'])
        );
    }
}

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
