<?php
// -*-php-*-
rcs_id('$Id: AppendText.php,v 1.7 2005/04/02 03:05:43 uckelman Exp $');
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
 * Append text to an existing page.
 *
 * @Author: Pascal Giard <evilynux@gmail.com>
 *
 * See http://sourceforge.net/mailarchive/message.php?msg_id=10141823
 * why not to use "text" as parameter. Nasty mozilla bug with mult. radio rows.
 */
class WikiPlugin_AppendText extends WikiPlugin
{
    public function getName()
    {
        return _("AppendText");
    }

    public function getDescription()
    {
        return _("Append text to any page in this wiki.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.7 $"
        );
    }

    public function getDefaultArguments()
    {
        return array('page'     => '[pagename]',
                     's'        => '',  // Text to append.
                     'before'   => '',  // Add before (ignores after if defined)
                     'after'    => '',  // Add after line beginning with this
                     'redirect' => false // Redirect to modified page
                     );
    }

    public function _fallback($addtext, $oldtext, $notfound, &$message)
    {
        $message->pushContent(sprintf(_("%s not found"), $notfound) . ". " .
                              _("Appending at the end.") . "\n");
        return $oldtext . "\n" . $addtext;
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        $pagename = $args['page'];

        if (empty($args['s'])) {
            if ($request->isPost()) {
                if ($pagename != _("AppendText")) {
                    return HTML($request->redirect(WikiURL($pagename, false, 'absurl'), false));
                }
            }
            return '';
        }

        $page = $dbi->getPage($pagename);
        $message = HTML();

        if (!$page->exists()) { // We might want to create it?
            $message->pushContent(sprintf(
                _("Page could not be updated. %s doesn't exist!\n"),
                $pagename
            ));
            return $message;
        }

        $current = $page->getCurrentRevision();
        $oldtext = $current->getPackedContent();
        $text = $args['s'];

        // If a "before" or "after" is specified but not found, we simply append text to the end.
        if (!empty($args['before'])) {
            $before = preg_quote($args['before'], "/");
            // Insert before
            $newtext = preg_match("/\n${before}/", $oldtext)
                ? preg_replace(
                    "/(\n${before})/",
                    "\n" .  preg_quote($text, "/") . "\\1",
                    $oldtext
                )
                : $this->_fallback($text, $oldtext, $args['before'], $message);
        } elseif (!empty($args['after'])) {
            // Insert after
            $after = preg_quote($args['after'], "/");
            $newtext = preg_match("/\n${after}/", $oldtext)
                ? preg_replace(
                    "/(\n${after})/",
                    "\\1\n" .  preg_quote($text, "/"),
                    $oldtext
                )
                : $this->_fallback($text, $oldtext, $args['after'], $message);
        } else {
            // Append at the end
            $newtext = $oldtext .
                "\n" . $text;
        }

        require_once("lib/loadsave.php");
        $meta = $current->_data;
        $meta['summary'] = sprintf(_("AppendText to %s"), $pagename);
        if ($page->save($newtext, $current->getVersion() + 1, $meta)) {
            $message->pushContent(_("Page successfully updated."), HTML::br());
        }

        // AppendText has been called from the same page that got modified
        // so we directly show the page.
        if ($request->getArg($pagename) == $pagename) {
            // TODO: Just invalidate the cache, if AppendText didn't
            // change anything before.
            return $request->redirect(WikiURL($pagename, false, 'absurl'), false);

        // The user asked to be redirected to the modified page
        } elseif ($args['redirect']) {
            return $request->redirect(WikiURL($pagename, false, 'absurl'), false);
        } else {
            $link = HTML::em(WikiLink($pagename));
            $message->pushContent(HTML::Raw(sprintf(_("Go to %s."), $link->asXml())));
        }

        return $message;
    }
}

// $Log: AppendText.php,v $
// Revision 1.7  2005/04/02 03:05:43  uckelman
// Removed & from vars passed by reference (not needed, causes PHP to complain).
//
// Revision 1.6  2005/02/12 17:24:23  rurban
// locale update: missing . : fixed. unified strings
// proper linebreaks
//
// Revision 1.5  2004/11/26 18:39:02  rurban
// new regex search parser and SQL backends (90% complete, glob and pcre backends missing)
//
// Revision ext-1.4  2004/11/25 15:39:40  Pascal Giard <evilynux@gmail.com>
// * Directly including modified page when AppendText got called from
//   the page to be modified.
// * Translatable link to page.
//
// Revision ext-1.3  2004/11/25  9:44:45  Pascal Giard <evilynux@gmail.com>
// * text modified to s to workaround mozilla bug.
// * Added redirect parameter allowing you to be redirected to the modified page.
//
// Revision 1.4  2004/11/25 17:20:52  rurban
// and again a couple of more native db args: backlinks
//
// Revision 1.3  2004/11/25 13:56:23  rurban
// renamed text to s because of nasty mozilla radio button bug
//
// Revision 1.2  2004/11/25 08:29:43  rurban
// update from Pascal
//
// Revision ext-1.2  2004/11/24 11:22:30  Pascal Giard <evilynux@gmail.com>
// * Integrated rurban's modifications.
//
// Revision 1.1  2004/11/24 09:25:35  rurban
// simple plugin by Pascal Giard (QC/EMC)
//
// Revision 1.0  2004/11/23 09:43:35  epasgia
// * Initial version.
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
