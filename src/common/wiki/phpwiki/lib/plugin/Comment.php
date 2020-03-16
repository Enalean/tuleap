<?php
// -*-php-*-
rcs_id('$Id: Comment.php,v 1.2 2004/02/17 12:11:36 rurban Exp $');
/**
 * A WikiPlugin for putting comments in WikiPages
 *
 * Usage:
 * <?plugin Comment
 *
 * !!! My Secret Text
 *
 * This is some WikiText that won't show up on the page.
 *
 * ?>
 */

class WikiPlugin_Comment extends WikiPlugin
{
    // Five required functions in a WikiPlugin.

    public function getName()
    {
        return _("Comment");
    }

    public function getDescription()
    {
        return _("Embed hidden comments in WikiPages.");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.2 $"
        );
    }

    // No arguments here.
    public function getDefaultArguments()
    {
        return array();
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
    }

    // function handle_plugin_args_cruft(&$argstr, &$args) {
    // }
}

// $Log: Comment.php,v $
// Revision 1.2  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.1  2003/01/28 17:57:15  carstenklapp
// Martin Geisler's clever Comment plugin.
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
