<?php
// -*-php-*-
rcs_id('$Id: TextSearchIter.php,v 1.7 2005/11/14 22:24:33 rurban Exp $');

class WikiDB_backend_dumb_TextSearchIter extends WikiDB_backend_iterator
{
    public function __construct(
        &$backend,
        &$pages,
        $search,
        $fulltext = false,
        $options = array()
    ) {
        $this->_backend = &$backend;
        $this->_pages = $pages;
        $this->_fulltext = $fulltext;
        $this->_search  = $search;
        $this->_index   = 0;
        $this->_stoplist = $search->_stoplist;
        $this->stoplisted = array();

        if (isset($options['limit'])) {
            $this->_limit = $options['limit'];
        } else {
            $this->_limit = 0;
        }
        if (isset($options['exclude'])) {
            $this->_exclude = $options['exclude'];
        } else {
            $this->_exclude = false;
        }
    }

    public function _get_content(&$page)
    {
        $backend = $this->_backend;
        $pagename = $page['pagename'];

        if (!isset($page['versiondata'])) {
            $version = $backend->get_latest_version($pagename);
            $page['versiondata'] = $backend->get_versiondata($pagename, $version, true);
        }
        return $page['versiondata']['%content'];
    }

    public function _match(&$page)
    {
        $text = $page['pagename'];
        if ($result = $this->_search->match($text)) { // first match the pagename only
            return $result;
        }

        if ($this->_fulltext) {
            // eliminate stoplist words from fulltext search
            if (preg_match("/^" . $this->_stoplist . "$/i", $text)) {
                $this->stoplisted[] = $text;
                return $result;
            }
            $text .= "\n" . $this->_get_content($page);
            return $this->_search->match($text);
        } else {
            return $result;
        }
    }

    public function next()
    {
        $pages = &$this->_pages;
        while ($page = $pages->next()) {
            if ($this->_match($page)) {
                if ($this->_limit and ($this->_index++ >= $this->_limit)) {
                    return false;
                }
                return $page;
            }
        }
        return false;
    }

    public function free()
    {
        $this->_pages->free();
    }
}

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
