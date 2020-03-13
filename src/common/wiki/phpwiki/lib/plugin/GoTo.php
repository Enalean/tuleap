<?php
// -*-php-*-
rcs_id('$Id: GoTo.php,v 1.4 2004/07/08 20:30:07 rurban Exp $');

/**
 *  Display a form with text entry box and 'Go' button.
 *  The user enters a page name... if it exists, browse
 *  that page; if not, edit (create) that page.
 *  Note: pagenames are absolute, not relative to the actual subpage.
 *
 *  Usage: <?plugin GoTo ?>
 *  @author: Michael van Dam
 */

class WikiPlugin_GoTo extends WikiPlugin
{
    public function getName()
    {
        return _("GoTo");
    }

    public function getDescription()
    {
        return _("Go to or create page.");
    }

    public function getDefaultArguments()
    {
        return array('size' => 32);
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $request->setArg('action', false);
        $args = $this->getArgs($argstr, $request);
        extract($args);

        if ($goto = $request->getArg('goto')) {
            // The user has pressed 'Go'; process request
            $request->setArg('goto', false);
            $target = $goto['target'];
            if ($dbi->isWikiPage($target)) {
                $url = WikiURL($target, 0, 1);
            } else {
                $url = WikiURL($target, array('action' => 'edit'), 1);
            }

            $request->redirect($url);
            // User should see nothing after redirect
            return '';
        }

        $action = $request->getURLtoSelf();
        $form = HTML::form(array('action' => $action,
                                 'method' => 'post'
                          ));

        $form->pushContent(HiddenInputs($request->getArgs()));

        $textfield = HTML::input(array('type' => 'text',
                                       'size' => $size,
                                       'name' => 'goto[target]'));

        $button = Button('submit:goto[go]', _("Go"), false);

        $form->pushContent($textfield, $button);

        return $form;
    }
}

// $Log: GoTo.php,v $
// Revision 1.4  2004/07/08 20:30:07  rurban
// plugin->run consistency: request as reference, added basepage.
// encountered strange bug in AllPages (and the test) which destroys ->_dbi
//
// Revision 1.3  2004/04/18 01:11:52  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
//
// Revision 1.2  2004/04/12 16:21:01  rurban
// fix lib/plugin/RssFeed.php:81: Notice[8]: Undefined variable: th
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
