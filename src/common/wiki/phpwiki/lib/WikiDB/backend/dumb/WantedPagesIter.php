<?php
// -*-php-*-
rcs_id('$Id: WantedPagesIter.php,v 1.1 2004/11/20 17:35:58 rurban Exp $');

//require_once('lib/WikiDB/backend.php');

/**
 * This iterator will work with any WikiDB_backend
 * which has a working get_links(,'links_from') method.
 *
 * This is mostly here for testing, 'cause it's slow,slow,slow.
 */
class WikiDB_backend_dumb_WantedPagesIter extends WikiDB_backend_iterator
{
    public function __construct(&$backend, &$all_pages, $exclude = '', $sortby = false, $limit = false)
    {
        $this->_allpages   = $all_pages;
        $this->_allpages_array   = $all_pages->asArray();
        $this->_backend = &$backend;
        if (!is_array($exclude)) {
            $this->exclude = $exclude ? PageList::explodePageList($exclude) : array();
        } else {
            $this->exclude = $exclude;
        }
    }

    public function next()
    {
        while ($page = $this->_allpages->next()) {
            $pagename = $page['pagename'];
            $links = $this->_backend->get_links($pagename, false);
            while ($link = $links->next()) {
                if ($this->exclude and in_array($link['pagename'], $this->exclude)) {
                    continue;
                }
                // better membership for a pageiterator???
                if (! in_array($link['pagename'], $this->_allpages_array)) {
                    $links->free();
                    $link['wantedfrom'] = $pagename;
                    return $link;
                }
            }
            $links->free();
        }
        return false;
    }

    public function free()
    {
        unset($this->_allpages_array);
        $this->_allpages->free();
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
