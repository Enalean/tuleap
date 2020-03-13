<?php
// -*-php-*-
// iso-8859-1

/*
Copyright 1999,2000,2001,2002,2003,2004 $ThePhpWikiProgrammingTeam
= array(
"Steve Wainstead", "Clifford A. Adams", "Lawrence Akka",
"Scott R. Anderson", "Jon Åslund", "Neil Brown", "Jeff Dairiki",
"Stéphane Gourichon", "Jan Hidders", "Arno Hollosi", "John Jorgensen",
"Antti Kaihola", "Jeremie Kass", "Carsten Klapp", "Marco Milanesi",
"Grant Morgan", "Jan Nieuwenhuizen", "Aredridel Niothke",
"Pablo Roca Rozas", "Sandino Araico Sánchez", "Joel Uckelman",
"Reini Urban", "Joby Walker", "Tim Voght", "Jochen Kalmbach");

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

require_once(dirname(__FILE__) . '/lib/prepend.php');
rcs_id('$Id: index.php,v 1.147 2005/01/13 07:28:36 rurban Exp $');

require_once(dirname(__FILE__) . '/lib/IniConfig.php');
IniConfig(dirname(__FILE__) . "/config/config.ini");

////////////////////////////////////////////////////////////////
// PrettyWiki
// Check if we were included by some other wiki version
// (getimg.php, en, de, wiki, ...) or not.
// If the server requested this index.php fire up the code by loading lib/main.php.
// Parallel wiki scripts can now simply include /index.php for the
// main configuration, extend or redefine some settings and
// load lib/main.php by themselves. See the file 'wiki'.
// This overcomes the IndexAsConfigProblem.
// Generally a simple
//   define('VIRTUAL_PATH', $_SERVER['SCRIPT_NAME']);
// is enough in the wiki file, plus the action definition in a .htaccess file
////////////////////////////////////////////////////////////////

// If any page is empty, comment the if ... line out,
// to force include "lib/main.php".
// Without the dir check it might fail for index.php via DirectoryIndex
if (@is_dir(SCRIPT_FILENAME) or realpath(SCRIPT_FILENAME) == realpath(__FILE__)) {
    include(dirname(__FILE__) . "/lib/main.php");
}

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
