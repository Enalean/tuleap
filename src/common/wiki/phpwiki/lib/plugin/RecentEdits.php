<?php
// -*-php-*-
rcs_id('$Id: RecentEdits.php,v 1.1 2004/04/21 04:29:10 rurban Exp $');

require_once("lib/plugin/RecentChanges.php");

class WikiPlugin_RecentEdits extends WikiPlugin_RecentChanges
{
    public function getName()
    {
        return _("RecentEdits");
    }

    public function getVersion()
    {
        return preg_replace(
            "/[Revision: $]/",
            '',
            "\$Revision: 1.1 $"
        );
    }

    public function getDefaultArguments()
    {
        $args = parent::getDefaultArguments();
        $args['show_minor'] = true;
        $args['show_all'] = true;
        return $args;
    }

    // box is used to display a fixed-width, narrow version with common header.
    // just a numbered list of limit pagenames, without date.
    public function box($args = false, $request = false, $basepage = false)
    {
        if (!$request) {
            $request = $GLOBALS['request'];
        }
        if (!isset($args['limit'])) {
            $args['limit'] = 15;
        }
        $args['format'] = 'box';
        $args['show_minor'] = true;
        $args['show_major'] = true;
        $args['show_deleted'] = false;
        $args['show_all'] = true;
        $args['days'] = 90;
        return $this->makeBox(
            WikiLink(_("RecentEdits"), '', _("Recent Edits")),
            $this->format($this->getChanges($request->_dbi, $args), $args)
        );
    }
}

// $Log: RecentEdits.php,v $
// Revision 1.1  2004/04/21 04:29:10  rurban
// Two convenient RecentChanges extensions
//   RelatedChanges (only links from current page)
//   RecentEdits (just change the default args)
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
