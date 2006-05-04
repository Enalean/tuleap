<?php // -*-php-*-
rcs_id('$Id: SqlResult.php 2691 2006-03-02 15:31:51Z guerin $');
/*
 Copyright 2004 $ThePhpWikiProgrammingTeam
 
 This file is (not yet) part of PhpWiki.

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
 * This plugin displays result sets of arbitrary SQL select statements 
 * in table form.
 * The database definition, the dsn, must be defined in the local file 
 * lib/plugin/SqlResult.ini
 *   A simple textfile with alias = dsn lines.
 *
 * TODO: Optional template file to format the result and handle some logic.
 *       Pass args to the SQL line (for paging)
 *
 * Usage:
 *   <?plugin SqlResult alias=mysql
 *            SELECT 'mysql password for string "xx":',
 *                   PASSWORD('xx')
 *   ?>
 *   <?plugin SqlResult alias=videos template=videos
 *            SELECT rating,title,date 
 *                   FROM video 
 *                   ORDER BY rating DESC 
 *                   LIMIT 5
 *   ?>
 *
 * @author: ReiniUrban
 */

class WikiPlugin_SqlResult
extends WikiPlugin
{
    var $_args;	
    
    function getName () {
        return _("SqlResult");
    }

    function getDescription () {
        return _("Display arbitrary SQL result tables");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 2691 $");
    }

    function getDefaultArguments() {
        return array('page'        => '[pagename]',
                     'alias'       => false,
                     'template'    => false,
                    );
    }

    function getDsn($alias) {
        $ini = parse_ini_file(FindFile("lib/plugin/SqlResult.ini"));
        return $ini[$alias];
    }

    /** Get the SQL statement from the rest of the lines
     */
    function handle_plugin_args_cruft($argstr, $args) {
    	$this->_sql = str_replace("\n"," ",$argstr);
        return;
    }
   
    function run($dbi, $argstr, &$request, $basepage) {
        global $DBParams;
    	//$request->setArg('nocache','1');
        extract($this->getArgs($argstr, $request));
        if (!$alias)
            return $this->error(_("No DSN alias for SqlResult.ini specified"));
	$sql = $this->_sql;
        //TODO: handle variables

        $inidsn = $this->getDsn($alias);
        if (!$inidsn)
            return $this->error(sprintf(_("No DSN for alias %s in SqlResult.ini found"),
                                        $alias));
        $db = DB::connect($inidsn);
        $all = $db->getAll($sql);

        $html = HTML::table(array('class'=>'sqlresult'));
        $i = 0;
	foreach ($all as $row) {
            $tr = HTML::tr(array('class'=> $i++ % 2 ? 'evenrow' : 'oddrow'));
            foreach ($row as $col) {
                $tr->pushContent(HTML::td($col));
            }
            $html->pushContent($tr);
        }
        return $html;
    }

};

// $Log$
// Revision 1.2  2004/05/03 21:57:47  rurban
// locale updates: we previously lost some words because of wrong strings in
//   PhotoAlbum, german rewording.
// fixed $_SESSION registering (lost session vars, esp. prefs)
// fixed ending slash in listAvailableLanguages/Themes
//
// Revision 1.1  2004/05/03 20:44:58  rurban
// fixed gettext strings
// new SqlResult plugin
// _WikiTranslation: fixed init_locale
//

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>