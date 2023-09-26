<?php
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/bookmarks.php';

$request = HTTPRequest::instance();


$HTML->header(\Tuleap\Layout\HeaderConfiguration::fromTitle($Language->getText('bookmark_delete', 'title')));

print "<H3>" . $Language->getText('bookmark_delete', 'title') . "</H3>\n";
$vId = new Valid_UInt('bookmark_id');
$vId->required();
if ($request->valid($vId)) {
    $bookmark_id = (int) $request->get('bookmark_id');
    $csrf_token  = new CSRFSynchronizerToken('bookmark_delete');

    if ($request->isPost()) {
        $csrf_token->check('/my/bookmark_delete.php?bookmark_id=' . $bookmark_id);
        bookmark_delete($bookmark_id);
        print '<p>' . $Language->getText('bookmark_delete', 'deleted') . '</p>';
    } else {
        print '<form method="post">';
        print '<p>' . $Language->getText('my_index', 'del_bookmark') . '</p>';
        print '<input type="hidden" name="bookmark_id" value="' . $bookmark_id . '"/>';
        print $csrf_token->fetchHTMLInput();
        print '<input type="submit" value="' . $Language->getText('global', 'btn_submit') . '">';
        print '</form>';
    }
    print "<p><a href=\"/my/\">[" . $Language->getText('global', 'back_home') . "]</a></p>";
} else {
    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'missing_parameters'));
    $GLOBALS['Response']->redirect('/my');
}

$HTML->footer([]);
