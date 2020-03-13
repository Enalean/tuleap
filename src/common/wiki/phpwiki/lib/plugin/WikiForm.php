<?php
// -*-php-*-
rcs_id('$Id: WikiForm.php,v 1.16 2004/07/01 13:14:01 rurban Exp $');
/**
 Copyright 1999, 2000, 2001, 2002, 2004 $ThePhpWikiProgrammingTeam

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
 * This is a replacement for MagicPhpWikiURL forms.
 * Just a few old actions are supported, which where previously
 * encoded with the phpwiki: syntax.
 *
 * See WikiFormMore for the more generic version.
 */
class WikiPlugin_WikiForm extends WikiPlugin
{
    public function getName()
    {
        return _("WikiForm");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.16 $"
        );
    }

    public function getDefaultArguments()
    {
        return array('action' => 'upload', // 'upload', 'loadfile', or
                                           // 'dumpserial'
                     'default' => false,
                     'buttontext' => false,
                     'overwrite' => false,
                     'size' => 50);
    }


    public function run($dbi, $argstr, &$request, $basepage)
    {
        extract($this->getArgs($argstr, $request));
        $form = HTML::form(
            array('action' => $request->getPostURL(),
                                 'method' => 'post',
                                 'class'  => 'wikiadmin',
                                 'accept-charset' => $GLOBALS['charset']),
            HiddenInputs(array('action' => $action,
            'pagename' => $basepage))
        );
        $input = array('type' => 'text',
                       'value' => $default,
                       'size' => $size);

        switch ($action) {
            case 'loadfile':
                $input['name'] = 'source';
                if (!$default) {
                    $input['value'] = DEFAULT_DUMP_DIR;
                }
                if (!$buttontext) {
                    $buttontext = _("Load File");
                }
                $class = false;
                break;
            case 'login':
                $input['name'] = 'source';
                if (!$buttontext) {
                    $buttontext = _("Login");
                }
                $class = 'wikiadmin';
                break;
            case 'upload':
                $form->setAttr('enctype', 'multipart/form-data');
                $form->pushContent(HTML::input(array('name' => 'MAX_FILE_SIZE',
                                                 'value' =>  MAX_UPLOAD_SIZE,
                                                 'type'  => 'hidden')));
                $input['name'] = 'file';
                $input['type'] = 'file';
                if (!$buttontext) {
                    $buttontext = _("Upload");
                }
                $class = false; // local OS function, so use native OS button
                break;
            default:
                return HTML::p(fmt("WikiForm: %s: unknown action", $action));
        }

        $input = HTML::input($input);
        $input->addTooltip($buttontext);
        $button = Button('submit:', $buttontext, $class);
        if ($request->getArg('start_debug')) {
            $form->pushContent(HTML::input(array('name' => 'start_debug',
                                                 'value' =>  $request->getArg('start_debug'),
                                                 'type'  => 'hidden')));
        }
        $form->pushContent(HTML::span(
            array('class' => $class),
            $input,
            $button
        ));

        return $form;
    }
}

// $Log: WikiForm.php,v $
// Revision 1.16  2004/07/01 13:14:01  rurban
// desc only
//
// Revision 1.15  2004/06/22 07:12:49  rurban
// removed USE_TAGLINES constant
//
// Revision 1.14  2004/06/21 17:06:38  rurban
// renamed constant
//
// Revision 1.13  2004/06/21 16:22:32  rurban
// add DEFAULT_DUMP_DIR and HTML_DUMP_DIR constants, for easier cmdline dumps,
// fixed dumping buttons locally (images/buttons/),
// support pages arg for dumphtml,
// optional directory arg for dumpserial + dumphtml,
// fix a AllPages warning,
// show dump warnings/errors on DEBUG,
// don't warn just ignore on wikilens pagelist columns, if not loaded.
// RateIt pagelist column is called "rating", not "ratingwidget" (Dan?)
//
// Revision 1.12  2004/04/18 01:11:52  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
//
// Revision 1.11  2004/02/24 15:20:07  rurban
// fixed minor warnings: unchecked args, POST => Get urls for sortby e.g.
//
// Revision 1.10  2004/02/22 23:20:33  rurban
// fixed DumpHtmlToDir,
// enhanced sortby handling in PageList
//   new button_heading th style (enabled),
// added sortby and limit support to the db backends and plugins
//   for paging support (<<prev, next>> links on long lists)
//
// Revision 1.9  2003/02/26 01:56:52  dairiki
// Tuning/fixing of POST action URLs and hidden inputs.
//
// Revision 1.8  2003/01/18 22:14:30  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
