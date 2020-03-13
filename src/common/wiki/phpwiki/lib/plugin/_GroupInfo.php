<?php
/*
 Copyright 2004 $ThePhpWikiProgrammingTeam

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
 * @author: Charles Corrigan
 */
class WikiPlugin__GroupInfo extends WikiPlugin
{
    public function getName()
    {
        return _("DebugGroupInfo");
    }

    public function getDescription()
    {
        return sprintf(_("Show Group Information"));
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.1 $"
        );
    }

    public function getDefaultArguments()
    {
        return array();
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        extract($args);

        $output = HTML(HTML::h1("Group Info"));

        $group = WikiGroup::getGroup();
        $allGroups = $group->getAllGroupsIn();

        foreach ($allGroups as $g) {
            $members = $group->getMembersOf($g);
            $output->pushContent(HTML::h3($g . " - members: " .
            sizeof($members) . " - isMember: " . ($group->isMember($g) ? "yes" : "no")));
            foreach ($members as $m) {
                $output->pushContent($m);
                $output->pushContent(HTML::br());
            }
        }
        $output->pushContent(HTML::p("--- the end ---"));

        return $output;
    }
}
