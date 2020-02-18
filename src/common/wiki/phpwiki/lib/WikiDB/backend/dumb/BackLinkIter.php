<?php
// -*-php-*-
rcs_id('$Id: BackLinkIter.php,v 1.2 2001/09/19 03:24:36 wainstead Exp $');

require_once('lib/WikiDB/backend.php');

/**
 * This backlink iterator will work with any WikiDB_backend
 * which has a working get_links(,'links_from') method.
 *
 * This is mostly here for testing, 'cause it's slow,slow,slow.
 */
class WikiDB_backend_dumb_BackLinkIter extends WikiDB_backend_iterator
{
    public function __construct(&$backend, &$all_pages, $pagename)
    {
        $this->_pages = $all_pages;
        $this->_backend = &$backend;
        $this->_target = $pagename;
    }

    public function next()
    {
        while ($page = $this->_pages->next()) {
            $pagename = $page['pagename'];
            $links = $this->_backend->get_links($pagename, false);
            while ($link = $links->next()) {
                if ($link['pagename'] == $this->_target) {
                    $links->free();
                    return $page;
                }
            }
        }
    }

    public function free()
    {
        $this->_pages->free();
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
