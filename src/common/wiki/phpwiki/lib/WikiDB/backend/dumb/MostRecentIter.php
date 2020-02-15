<?php
// -*-php-*-
rcs_id('$Id: MostRecentIter.php,v 1.6 2005/04/09 09:16:54 rurban Exp $');

require_once('lib/WikiDB/backend.php');


/**
 * An inefficient but general most_recent iterator.
 *
 * This iterator will work with any backends.
 */
class WikiDB_backend_dumb_MostRecentIter extends WikiDB_backend_iterator
{
    public function __construct(&$backend, &$pages, $params)
    {
        $limit = false;
        extract($params);
        if ($exclude_major_revisions) {
            $include_minor_revisions = true;
        }

        $reverse = $limit < 0;
        if ($reverse) {
            $limit = -$limit;
        }
        $this->_revisions = array();
        while ($page = $pages->next()) {
            $revs = $backend->get_all_revisions($page['pagename']);
            while ($revision = &$revs->next()) {
                $vdata = &$revision['versiondata'];
                assert(is_array($vdata));
                if (!empty($vdata['is_minor_edit'])) {
                    if (!$include_minor_revisions) {
                        continue;
                    }
                } else {
                    if ($exclude_major_revisions) {
                        continue;
                    }
                }
                if (!empty($since) && $vdata['mtime'] < $since) {
                    break;
                }

                $this->_revisions[] = $revision;

                if (!$include_all_revisions) {
                    break;
                }
            }
            $revs->free();
        }
        if ($reverse) {
            usort($this->_revisions, 'WikiDB_backend_dumb_MostRecentIter_sortf_rev');
        } else {
            usort($this->_revisions, 'WikiDB_backend_dumb_MostRecentIter_sortf');
        }
        if (!empty($limit) && $limit < count($this->_revisions)) {
            array_splice($this->_revisions, $limit);
        }
    }

    public function next()
    {
        return array_shift($this->_revisions);
    }

    public function free()
    {
        unset($this->_revisions);
    }
}

function WikiDB_backend_dumb_MostRecentIter_sortf($a, $b)
{
    $acreated = $a['versiondata']['mtime'];
    $bcreated = $b['versiondata']['mtime'];
    return $bcreated - $acreated;
}

function WikiDB_backend_dumb_MostRecentIter_sortf_rev($a, $b)
{
    $acreated = $a['versiondata']['mtime'];
    $bcreated = $b['versiondata']['mtime'];
    return $acreated - $bcreated;
}

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
