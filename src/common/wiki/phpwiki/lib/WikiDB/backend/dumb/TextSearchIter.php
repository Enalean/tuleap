<?php // -*-php-*-
rcs_id('$Id$');

class WikiDB_backend_dumb_TextSearchIter
extends WikiDB_backend_iterator
{
    function WikiDB_backend_dumb_TextSearchIter(&$backend, &$pages, $search, $fullsearch = false) {
        $this->_backend = &$backend;
        $this->_pages = $pages;
        $this->_fullsearch = $fullsearch;
        $this->_search = $search;
    }

    function _get_content(&$page) {
        $backend = &$this->_backend;
        $pagename = $page['pagename'];
        
        if (!isset($page['versiondata'])) {
            $version = $backend->get_latest_version($pagename);
            $page['versiondata'] = $backend->get_versiondata($pagename, $version, true);
        }
        return $page['versiondata']['%content'];
    }
        
        
    function _match(&$page) {
        $text = $page['pagename'];
        if ($this->_fullsearch)
            $text .= "\n" . $this->_get_content($page);

        return $this->_search->match($text);
    }

    function next() {
        $pages = &$this->_pages;

        while ($page = $pages->next()) {
            if ($this->_match($page))
                return $page;
        }
        return false;
    }

    function free() {
        $this->_pages->free();
    }
};

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>
