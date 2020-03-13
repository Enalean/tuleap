<?php
//-*-php-*-
rcs_id('$Id: Template.php,v 1.73 2005/04/08 05:41:00 rurban Exp $');

require_once("lib/ErrorManager.php");


/** An HTML template.
 */
class Template
{
    /**
     * name optionally of form "theme/template" to include parent templates in children
     */
    public function __construct($name, $request, $args = false)
    {
        global $WikiTheme;

        $this->_request = $request;
        $this->_basepage = $request->getArg('pagename');

        if (strstr($name, "/")) {
            $oldname = $WikiTheme->_name;
            $oldtheme = $WikiTheme->_theme;
            list($themename, $name) = explode("/", $name);
            $WikiTheme->_theme = "themes/$themename";
        }
        $this->_name = $name;
        $file = $WikiTheme->findTemplate($name);
        if (!$file) {
            trigger_error("no template for $name found.", E_USER_WARNING);
            return;
        }
        if (isset($oldname)) {
            $WikiTheme->_name = $oldname;
            $WikiTheme->_theme = $oldtheme;
        }
        $fp = fopen($file, "rb");
        if (!$fp) {
            trigger_error("$file not found", E_USER_WARNING);
            return;
        }
        $request->_TemplatesProcessed[$name] = 1;
        $this->_tmpl = fread($fp, filesize($file));
        fclose($fp);
        //$userid = $request->_user->_userid;
        if (is_array($args)) {
            $this->_locals = $args;
        } elseif ($args) {
            $this->_locals = array('CONTENT' => $args);
        } else {
            $this->_locals = array();
        }
    }

    public function _munge_input($template)
    {
        // Convert < ?plugin expr ? > to < ?php $this->_printPluginPI("expr"); ? >
        $template = preg_replace_callback(
            '/<\?plugin.*?\?>/s',
            function (array $matches) {
                return sprintf(
                    '<?php $this->_printPlugin(%s); ?>',
                    "'" . str_replace("'", "\'", $matches[0]) . "'"
                );
            },
            $template
        );

        // Convert < ?= expr ? > to < ?php $this->_print(expr); ? >
        return preg_replace('/<\?=(.*?)\?>/s', '<?php $this->_print(\1);?>', $template);
    }

    public function _printPlugin($pi)
    {
        include_once("lib/WikiPlugin.php");
        static $loader;

        if (empty($loader)) {
            $loader = new WikiPluginLoader;
        }

        $this->_print($loader->expandPI($pi, $this->_request, $this, $this->_basepage));
    }

    public function _print($val)
    {
        if (isa($val, 'Template')) {
            $this->_expandSubtemplate($val);
        } else {
            PrintXML($val);
        }
    }

    public function _expandSubtemplate(&$template)
    {
        // FIXME: big hack!
        //if (!$template->_request)
        //    $template->_request = &$this->_request;
        if (DEBUG) {
            echo "<!-- Begin $template->_name -->\n";
        }
        // Expand sub-template with defaults from this template.
        $template->printExpansion($this->_vars);
        if (DEBUG) {
            echo "<!-- End $template->_name -->\n";
        }
    }

    /**
     * Substitute HTML replacement text for tokens in template.
     *
     * Constructs a new WikiTemplate based upon the named template.
     *
     * @access public
     *
     * @param $token string Name of token to substitute for.
     *
     * @param $replacement string Replacement HTML text.
     */
    public function replace($varname, $value)
    {
        $this->_locals[$varname] = $value;
    }


    public function printExpansion($defaults = false)
    {
        if (!is_array($defaults)) { // HTML object or template object
            $defaults = array('CONTENT' => $defaults);
        }
        $this->_vars = array_merge($defaults, $this->_locals);
        extract($this->_vars);

        global $request;
        if (!isset($user)) {
            $user = $request->getUser();
        }
        if (!isset($page)) {
            $page = $request->getPage();
        }

        global $WikiTheme, $RCS_IDS, $charset;
        //$this->_dump_template();
        $SEP = $WikiTheme->getButtonSeparator();

        global $ErrorManager;
        $ErrorManager->pushErrorHandler(new WikiMethodCb($this, '_errorHandler'));

        eval('?>' . $this->_munge_input($this->_tmpl));

        $ErrorManager->popErrorHandler();
    }

    // FIXME (1.3.12)
    // Find a way to do template expansion less memory intensive and faster.
    // 1.3.4 needed no memory at all for dumphtml, now it needs +15MB.
    // Smarty? As before?
    public function getExpansion($defaults = false)
    {
        ob_start();
        $this->printExpansion($defaults);
        $xml = ob_get_contents();
        ob_end_clean();     // PHP problem: Doesn't release its memory?
        return $xml;
    }

    public function printXML()
    {
        $this->printExpansion();
    }

    public function asXML()
    {
        return $this->getExpansion();
    }


    // Debugging:
    public function _dump_template()
    {
        $lines = explode("\n", $this->_munge_input($this->_tmpl));
        $pre = HTML::pre();
        $n = 1;
        foreach ($lines as $line) {
            $pre->pushContent(fmt("%4d  %s\n", $n++, $line));
        }
        $pre->printXML();
    }

    public function _errorHandler($error)
    {
        //if (!preg_match('/: eval\(\)\'d code$/', $error->errfile))
    //    return false;

        if (preg_match('/: eval\(\)\'d code$/', $error->errfile)) {
            $error->errfile = "In template '$this->_name'";
            // Hack alert: Ignore 'undefined variable' messages for variables
            //  whose names are ALL_CAPS.
            if (preg_match('/Undefined variable:\s*[_A-Z]+\s*$/', $error->errstr)) {
                return true;
            }
        } elseif (strstr($error->errfile, "In template 'htmldump'")) {
            // ignore recursively nested htmldump loop: browse -> body -> htmldump -> browse -> body ...
            // FIXME for other possible loops also
//return $error;
        } elseif (strstr($error->errfile, "In template '")) { // merge
            $error->errfile = preg_replace("/'(\w+)'\)$/", "'\\1' < '$this->_name')", $error->errfile);
        } else {
            $error->errfile .= " (In template '$this->_name')";
        }

        if (!empty($this->_tmpl)) {
            $lines = explode("\n", $this->_tmpl);
            if (isset($lines[$error->errline - 1])) {
                $error->errstr .= ":\n\t" . $lines[$error->errline - 1];
            }
        }
        return $error;
    }
}

/**
 * Get a templates
 *
 * This is a convenience function and is equivalent to:
 * <pre>
 *   new Template(...)
 * </pre>
 */
function Template($name, $args = false)
{
    global $request;
    return new Template($name, $request, $args);
}

function alreadyTemplateProcessed($name)
{
    global $request;
    return !empty($request->_TemplatesProcessed[$name]) ? true : false;
}
/**
 * Make and expand the top-level template.
 *
 *
 * @param $content mixed html content to put into the page
 * @param $title string page title
 * @param $page_revision object A WikiDB_PageRevision object
 * @param $args hash Extract args for top-level template
 *
 * @return string HTML expansion of template.
 */
function GeneratePage($content, $title, $page_revision = false, $args = false)
{
    global $request;

    if (!is_array($args)) {
        $args = array();
    }

    $args['CONTENT'] = $content;
    $args['TITLE'] = $title;
    $args['revision'] = $page_revision;

    if (!isset($args['HEADER'])) {
        $args['HEADER'] = $title;
    }

    printXML(new Template('html', $request, $args));
}


/**
 * For dumping pages as html to a file.
 */
function GeneratePageasXML($content, $title, $page_revision = false, $args = false)
{
    global $request;

    if (!is_array($args)) {
        $args = array();
    }

    $content->_basepage = $title;
    $args['CONTENT'] = $content;
    $args['TITLE'] = SplitPagename($title);
    $args['revision'] = $page_revision;

    if (!isset($args['HEADER'])) {
        $args['HEADER'] = SplitPagename($title);
    }

    global $HIDE_TOOLBARS, $NO_BASEHREF, $HTML_DUMP;
    $HIDE_TOOLBARS = true;
    $HTML_DUMP = true;

    $html = asXML(new Template('htmldump', $request, $args));

    $HIDE_TOOLBARS = false;
    $HTML_DUMP = false;
    return $html;
}

// $Log: Template.php,v $
// Revision 1.73  2005/04/08 05:41:00  rurban
// fix Template("theme/name") inclusion
//
// Revision 1.72  2005/02/02 20:35:41  rurban
// add $SEP
//
// Revision 1.71  2005/02/02 19:29:30  rurban
// support theme overrides
//
// Revision 1.70  2005/01/25 07:01:26  rurban
// update comments about future plans
//
// Revision 1.69  2004/11/17 20:07:17  rurban
// just whitespace
//
// Revision 1.68  2004/11/09 17:11:04  rurban
// * revert to the wikidb ref passing. there's no memory abuse there.
// * use new wikidb->_cache->_id_cache[] instead of wikidb->_iwpcache, to effectively
//   store page ids with getPageLinks (GleanDescription) of all existing pages, which
//   are also needed at the rendering for linkExistingWikiWord().
//   pass options to pageiterator.
//   use this cache also for _get_pageid()
//   This saves about 8 SELECT count per page (num all pagelinks).
// * fix passing of all page fields to the pageiterator.
// * fix overlarge session data which got broken with the latest ACCESS_LOG_SQL changes
//
// Revision 1.67  2004/11/05 18:03:35  rurban
// shorten the template chain in errmsg
//
// Revision 1.66  2004/11/01 10:43:55  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.65  2004/10/07 16:08:58  rurban
// fixed broken FileUser session handling.
//   thanks to Arnaud Fontaine for detecting this.
// enable file user Administrator membership.
//
// Revision 1.64  2004/10/04 23:40:35  rurban
// fix nested loops on htmldump errors
//
// Revision 1.63  2004/09/06 08:22:33  rurban
// prevent errorhandler to fail on empty templates
//
// Revision 1.62  2004/06/28 15:39:27  rurban
// fixed endless recursion in WikiGroup: isAdmin()
//
// Revision 1.61  2004/06/25 14:29:18  rurban
// WikiGroup refactoring:
//   global group attached to user, code for not_current user.
//   improved helpers for special groups (avoid double invocations)
// new experimental config option ENABLE_XHTML_XML (fails with IE, and document.write())
// fixed a XHTML validation error on userprefs.tmpl
//
// Revision 1.60  2004/06/14 11:31:36  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.59  2004/05/18 16:23:39  rurban
// rename split_pagename to SplitPagename
//
// Revision 1.58  2004/05/15 19:48:33  rurban
// fix some too loose PagePerms for signed, but not authenticated users
//  (admin, owner, creator)
// no double login page header, better login msg.
// moved action_pdf to lib/pdf.php
//
// Revision 1.57  2004/05/01 18:20:05  rurban
// Add $charset to template locals (instead of constant CHARSET)
//
// Revision 1.56  2004/04/12 13:04:50  rurban
// added auth_create: self-registering Db users
// fixed IMAP auth
// removed rating recommendations
// ziplib reformatting
//
// Revision 1.55  2004/04/02 15:06:55  rurban
// fixed a nasty ADODB_mysql session update bug
// improved UserPreferences layout (tabled hints)
// fixed UserPreferences auth handling
// improved auth stability
// improved old cookie handling: fixed deletion of old cookies with paths
//
// Revision 1.54  2004/03/02 18:11:39  rurban
// CreateToc support: Pass the preparsed markup to each plugin as $dbi->_markup
// to be able to know about its context, and even let the plugin change it.
// (see CreateToc)
//
// Revision 1.53  2004/02/22 23:20:31  rurban
// fixed DumpHtmlToDir,
// enhanced sortby handling in PageList
//   new button_heading th style (enabled),
// added sortby and limit support to the db backends and plugins
//   for paging support (<<prev, next>> links on long lists)
//
// Revision 1.52  2003/12/20 23:59:19  carstenklapp
// Internal change: Added rcs Log tag & emacs php mode tag (sorry, forgot
// this in the last commit).
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
