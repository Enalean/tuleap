<?php
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright (c) Enalean, 2015 - Present. All rights reserved
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/bookmarks.php';
require_once __DIR__ . '/my_utils.php';

$request = HTTPRequest::instance();
$vUrl    = new Valid_String('bookmark_url');
$vUrl->required();
$vTitle = new Valid_String('bookmark_title');
$vTitle->required();

$purifier = Codendi_HTMLPurifier::instance();

$csrf_token = new CSRFSynchronizerToken('/my/bookmark_add.php');

if ($request->isPost() && $request->valid($vUrl) && $request->valid($vTitle)) {
    $csrf_token->check();

    $bookmark_url   = $request->get('bookmark_url');
    $bookmark_title = $request->get('bookmark_title');

    my_check_bookmark_URL($bookmark_url, '/my/bookmark_add.php');

    $HTML->header(\Tuleap\Layout\HeaderConfiguration::fromTitle($Language->getText('bookmark_add', 'title')));
    print "<H3>" . $Language->getText('bookmark_add', 'title') . "</H3>";
    print $Language->getText('bookmark_add', 'message', [$purifier->purify($bookmark_url), $purifier->purify($bookmark_title)]) . "<p>\n";

    $bookmark_id = bookmark_add($bookmark_url, $bookmark_title);
    print '<A HREF="' . $purifier->purify($bookmark_url) . '">' . $Language->getText('bookmark_add', 'visit') . "</A> - ";
    print '<A HREF="/my/bookmark_edit.php?bookmark_id=' . $bookmark_id . '">' . $Language->getText('bookmark_add', 'edit') . "</A>";
    print '<p><A HREF="/my/">[' . $Language->getText('global', 'back_home') . "]</A>";
} else {
    $HTML->header(\Tuleap\Layout\HeaderConfiguration::fromTitle($Language->getText('bookmark_add', 'title')));
    print "<H3>" . $Language->getText('bookmark_add', 'title') . "</H3>";

    $bookmark_url = 'http://';
    if ($request->valid($vUrl)) {
        $bookmark_url = $request->get('bookmark_url');
    }
    $bookmark_title = $Language->getText('bookmark_add', 'favorite');
    if ($request->valid($vTitle)) {
        $bookmark_title = $request->get('bookmark_title');
    }
    ?>
    <FORM METHOD=POST>
        <?php echo $Language->getText('bookmark_add', 'bkm_url'); ?>:<br>
        <input type="text" size="60" name="bookmark_url" value="<?php echo $purifier->purify($bookmark_url); ?>">
        <p>
            <?php echo $Language->getText('bookmark_add', 'bkm_title'); ?>:<br>
            <input type="text" size="60" name="bookmark_title" value="<?php echo $purifier->purify($bookmark_title); ?>">
        <p>
            <?php echo $csrf_token->fetchHTMLInput();?>
            <input type="submit" value="<?php echo $Language->getText('global', 'btn_submit'); ?>">
    </form>
    <?php
}

$HTML->footer([]);
