<?php // -*-php-*-
rcs_id('$Id$');
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

class WikiPlugin_Comment
extends WikiPlugin
{
    // Five required functions in a WikiPlugin.

    function getName() {
        return _("Comment");
    }

    function getDescription() {
        return _("Embed hidden comments in WikiPages.");

    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision$");
    }

    // No arguments here.
    function getDefaultArguments() {
        return array();
    }

    function run($dbi, $argstr, &$request, $basepage) {
    }

    // function handle_plugin_args_cruft(&$argstr, &$args) {
    // }

};

// $Log$
// Revision 1.1  2005/04/12 13:33:33  guerin
// First commit for wiki integration.
// Added Manuel's code as of revision 13 on Partners.
// Very little modification at the moment:
// - removed use of DOCUMENT_ROOT and SF_LOCAL_INC_PREFIX
// - simplified require syntax
// - removed ST-specific code (for test phase)
//
// Revision 1.2  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.1  2003/01/28 17:57:15  carstenklapp
// Martin Geisler's clever Comment plugin.
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
