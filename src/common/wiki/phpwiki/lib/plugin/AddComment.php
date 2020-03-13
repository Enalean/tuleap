<?php
// -*-php-*-
rcs_id('$Id: AddComment.php,v 1.8 2004/06/13 09:45:23 rurban Exp $');
/*
 Copyright (C) 2004 $ThePhpWikiProgrammingTeam

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
 * This plugin allows user comments attached to a page, similar to WikiBlog.
 * based on WikiBlog, no summary.
 *
 * TODO:
 * For admin user, put checkboxes beside comments to allow for bulk removal.
 *
 * @author: ReiniUrban
 */

include_once("lib/plugin/WikiBlog.php");

class WikiPlugin_AddComment extends WikiPlugin_WikiBlog
{
    public function getName()
    {
        return _("AddComment");
    }

    public function getDescription()
    {
        return sprintf(_("Show and add comments for %s"), '[pagename]');
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.8 $"
        );
    }

    // Arguments:
    //
    //  page - page where the comment is attached at (default current page)
    //
    //  order - 'normal'  - place in chronological order
    //        - 'reverse' - place in reverse chronological order
    //
    //  mode - 'show'     - only show old comments
    //         'add'      - only show entry box for new comment
    //         'show,add' - show old comments then entry box
    //         'add,show' - show entry box followed by list of comments
    //  jshide - boolean  - quick javascript expansion of the comments
    //                      and addcomment box

    public function getDefaultArguments()
    {
        return array('pagename'   => '[pagename]',
                     'order'      => 'normal',
                     'mode'       => 'add,show',
                     'jshide'     => '0',
                     'noheader'   => false,
                     //'sortby'     => '-pagename' // oldest first. reverse by order=reverse
                    );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $args = $this->getArgs($argstr, $request);
        if (!$args['pagename']) {
            return $this->error(_("No pagename specified"));
        }

        // Get our form args.
        $comment = $request->getArg("comment");
        $request->setArg('comment', false);

        if ($request->isPost() and !empty($comment['addcomment'])) {
            $this->add($request, $comment, 'comment'); // noreturn
        }
        if ($args['jshide'] and isBrowserIE() and browserDetect("Mac")) {
            //trigger_error(_("jshide set to 0 on Mac IE"), E_USER_NOTICE);
            $args['jshide'] = 0;
        }

        // Now we display previous comments and/or provide entry box
        // for new comments
        $html = HTML();
        if ($args['jshide']) {
            $div = HTML::div(array('id' => 'comments','style' => 'display:none;'));
            //$list->setAttr('style','display:none;');
            $div->pushContent(Javascript("
function togglecomments(a) {
  comments=document.getElementById('comments');
  if (comments.style.display=='none') {
    comments.style.display='block';
    a.title='" . _("Click to hide the comments") . "';
  } else {
    comments.style.display='none';
    a.title='" . _("Click to display all comments") . "';
  }
}"));
            $html->pushContent(HTML::h4(HTML::a(
                array('name' => 'comment-header',
                                                      'class' => 'wikiaction',
                                                      'title' => _("Click to display"),
                                                      'onclick' => "togglecomments(this)"),
                _("Comments")
            )));
        } else {
            $div = HTML::div(array('id' => 'comments'));
        }
        foreach (explode(',', $args['mode']) as $show) {
            if (!empty($seen[$show])) {
                continue;
            }
            $seen[$show] = 1;
            switch ($show) {
                case 'show':
                    $show = $this->showAll($request, $args, 'comment');
                    //if ($args['jshide']) $show->setAttr('style','display:none;');
                    $div->pushContent($show);
                    break;
                case 'add':
                    $add = $this->showForm($request, $args, 'addcomment');
                    //if ($args['jshide']) $add->setAttr('style','display:none;');
                    $div->pushContent($add);
                    break;
                default:
                    return $this->error(sprintf("Bad mode ('%s')", $show));
            }
        }
        $html->pushContent($div);
        return $html;
    }
}

// $Log: AddComment.php,v $
// Revision 1.8  2004/06/13 09:45:23  rurban
// display bug workaround for MacIE browsers, jshide: 0
//
// Revision 1.7  2004/03/29 21:33:32  rurban
// possible fix for problem reported by Whit Blauvelt
//   Message-ID: <20040327211707.GA22374@free.transpect.com>
// create intermediate redirect subpages for blog/comment/forum
//
// Revision 1.6  2004/03/16 15:44:34  rurban
// jshide not default as in CreateToc
//
// Revision 1.5  2004/03/15 09:52:59  rurban
// jshide button: dynamic titles
//
// Revision 1.4  2004/03/14 20:30:21  rurban
// jshide button
//
// Revision 1.3  2004/03/14 16:26:21  rurban
// copyright line
//
// Revision 1.2  2004/03/12 20:59:18  rurban
// important cookie fix by Konstantin Zadorozhny
// new editpage feature: JS_SEARCHREPLACE
//
// Revision 1.1  2004/03/12 17:32:41  rurban
// new base class PageType_attach as base class for WikiBlog, Comment, and WikiForum.
// new plugin AddComment, which is a WikiBlog with different pagetype and template,
//   based on WikiBlog. WikiForum comes later.
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
