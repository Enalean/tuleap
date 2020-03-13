<?php
// -*-php-*-
rcs_id('$Id: AllRevisionsIter.php,v 1.2 2004/04/26 20:44:35 rurban Exp $');

/**
 * An iterator which returns all revisions of page.
 *
 * This iterator uses  only the WikiDB_backend::get_versiondata interface
 * of a WikiDB_backend, and so it should work with all backends.
 */
class WikiDB_backend_dumb_AllRevisionsIter extends WikiDB_backend_iterator
{
    /**
     *
     *
     * @access protected
     * @param $backend object A WikiDB_backend.
     * @param $pagename string Page whose revisions to get.
     */
    public function __construct(&$backend, $pagename)
    {
        $this->_backend = &$backend;
        $this->_pagename = $pagename;
        $this->_lastversion = -1;
    }

    /**
     * Get next revision in sequence.
     *
     * @see WikiDB_backend_iterator_next;
     */
    public function next()
    {
        $backend = &$this->_backend;
        $pagename = &$this->_pagename;
        $version = &$this->_lastversion;

        //$backend->lock();
        if ($this->_lastversion == -1) {
            $version = $backend->get_latest_version($pagename);
        } elseif ($this->_lastversion > 0) {
            $version = $backend->get_previous_version($pagename, $version);
        }

        if ($version) {
            $vdata = $backend->get_versiondata($pagename, $version);
        }
        //$backend->unlock();

        if ($version == 0) {
            return false;
        }

        $rev = array('versiondata' => $vdata,
                     'pagename' => $pagename,
                     'version' => $version);

        if (!empty($vdata['%pagedata'])) {
            $rev['pagedata'] = &$vdata['%pagedata'];
        }

        return $rev;
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
