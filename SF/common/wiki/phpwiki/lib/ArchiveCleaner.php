<?php rcs_id('$Id: ArchiveCleaner.php,v 1.4 2004/06/29 08:52:22 rurban Exp $');

class ArchiveCleaner
{
    function ArchiveCleaner ($expire_params) {
        $this->expire_params = $expire_params;
    }
    
    function isMergeable($revision) {
        if ( ! $revision->get('is_minor_edit') )
            return false;

        $page = $revision->getPage();
        $author_id = $revision->get('author_id');

        $previous = $page->getRevisionBefore($revision);

        return !empty($author_id)
            && $author_id == $previous->get('author_id');
    }

    function cleanDatabase($dbi) {
        $iter = $dbi->getAllPages();
        while ($page = $iter->next())
            $this->cleanPageRevisions($page);
    }
        
    function cleanPageRevisions($page) {

        $expire = &$this->expire_params;
        foreach (array('major', 'minor', 'author') as $class)
            $counter[$class] = new ArchiveCleaner_Counter($expire[$class]);

        $authors_seen = array();
        
        $current = $page->getCurrentRevision(false);

        for ( $revision = $page->getRevisionBefore($current,false);
              $revision->getVersion() > 0;
              $revision = $page->getRevisionBefore($revision,false) ) {

            if ($revision->get('is_minor_edit'))
                $keep = $counter['minor']->keep($revision);
            else
                $keep = $counter['major']->keep($revision);

            if ($this->isMergeable($revision)) {
                if (!$keep) {
                    $page->mergeRevision($revision);
                }
            }
            else {
                $author_id = $revision->get('author_id');
                if (empty($authors_seen[$author_id])) {
                    if ($counter['author']->keep($revision))
                        $keep = true;
                    $authors_seen[$author_id] = true;
                }
                if (!$keep) {
                    $page->deleteRevision($revision);
                }
            }
        }
    }
}

/**
 * @access private
 */
class ArchiveCleaner_Counter
{
    function ArchiveCleaner_Counter($params) {

        if (!empty($params))
            extract($params);
        $INFINITY = 0x7fffffff;

        $this->max_keep = isset($max_keep) ? $max_keep : $INFINITY;

        $this->min_age  = isset($min_age)  ? $min_age  : 0;
        $this->min_keep = isset($min_keep) ? $min_keep : 0;

        $this->max_age  = isset($max_age)  ? $max_age  : $INFINITY;
        $this->keep     = isset($keep)     ? $keep     : $INFINITY;

        if ($this->keep > $this->max_keep)
            $this->keep = $this->max_keep;
        if ($this->min_keep > $this->keep)
            $this->min_keep = $this->keep;

        if ($this->min_age > $this->max_age)
            $this->min_age = $this->max_age;
            
        $this->now = time();
        $this->count = 0;
        $this->previous_supplanted = false;
        
    }

    function computeAge($revision) {
        $supplanted = $revision->get('_supplanted');

        if (!$supplanted) {
            // Every revision but the most recent should have a supplanted time.
            // However, if it doesn't...
            trigger_error(sprintf("Warning: Page '%s', version '%d' has no '_supplanted' timestamp",
                                  $revision->getPageName(),
                                  $revision->getVersion()),
                          E_USER_NOTICE);
            // Assuming revisions are chronologically ordered, the previous
            // supplanted time is a good value to use...
            if ($this->previous_supplanted > 0)
                $supplanted = $this->previous_supplanted;
            else {
                // no supplanted timestamp.
                // don't delete this revision based on age.
                return 0;
            }
        }

        $this->previous_supplanted = $supplanted;
        return ($this->now - $supplanted) / (24 * 3600);
    }
        
    function keep($revision) {
        $count = ++$this->count;
        $age = $this->computeAge($revision);
        
        if ($count > $this->max_keep)
            return false;
        if ($age <= $this->min_age || $count <= $this->min_keep)
            return true;
        return $age <= $this->max_age && $count <= $this->keep;
    }
}


// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>