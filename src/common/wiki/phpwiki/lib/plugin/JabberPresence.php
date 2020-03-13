<?php
// -*-php-*-
rcs_id('$Id: JabberPresence.php,v 1.3 2004/11/21 11:59:26 rurban Exp $');
/**
 * A simple Jabber presence WikiPlugin.
 * http://wiki.crao.net/index.php/JabberPr%E9sence/Source
 * http://edgar.netflint.net/howto.php
 *
 * Usage:
 *  <?plugin JabberPresence scripturl=http://edgar.netflint.net/status.php
 *                          jid=yourid@jabberserver type=html iconset=phpbb ?>
 *
 * @author: Arnaud Fontaine
 */

if (!defined('MY_JABBER_ID')) {
    define('MY_JABBER_ID', $GLOBALS['request']->_user->UserName() . "@jabber.com"); // or "@netflint.net"
}

class WikiPlugin_JabberPresence extends WikiPlugin
{
    // Five required functions in a WikiPlugin.
    public function getName()
    {
        return _("JabberPresence");
    }

    public function getDescription()
    {
        return _("Simple jabber presence plugin");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.3 $"
        );
    }

    // Establish default values for each of this plugin's arguments.
    public function getDefaultArguments()
    {
        return array('scripturl' => "http://edgar.netflint.net/status.php",
                     'jid'       => MY_JABBER_ID,
        'type'      => 'image',
                     'iconset'   => "gabber");
    }

    public function run($dbi, $argstr, $request)
    {
        extract($this->getArgs($argstr, $request));
        // Any text that is returned will not be further transformed,
        // so use html where necessary.
        if (empty($jid)) {
            $html = HTML();
        } else {
            $html = HTML::img(array('src' => urlencode($scripturl) .
            '&jid=' . urlencode($jid) .
            '&type=' . urlencode($type) .
            '&iconset=' . ($iconset)));
        }
        return $html;
    }
}

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
