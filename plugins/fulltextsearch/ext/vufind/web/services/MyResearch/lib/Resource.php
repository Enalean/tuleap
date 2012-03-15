<?php
/**
 * Table Definition for resource
 */
require_once 'DB/DataObject.php';

class Resource extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'resource';                        // table name
    public $id;                              // int(11)  not_null primary_key auto_increment
    public $record_id;                       // string(30)  not_null multiple_key
    public $title;                           // string(200)  not_null
    public $source = 'VuFind';               // string(50)  not_null

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Resource',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
    
    /**
     * Get tags associated with the current resource.
     *
     * @access  public
     * @param   int     $limit          Max. number of tags to return (0 = no limit)
     * @return  array
     */
    function getTags($limit = 0)
    {
        $tagList = array();

        $query = "SELECT MIN(tags.id), tags.tag, COUNT(*) as cnt " .
                 "FROM tags, resource_tags, resource " .
                 "WHERE tags.id = resource_tags.tag_id " .
                 "AND resource.id = resource_tags.resource_id " .
                 "AND resource.record_id = '" . $this->escape($this->record_id) . "' " .
                 "AND resource.source = '" . $this->escape($this->source) . "' " .
                 "GROUP BY tags.tag " .
                 "ORDER BY cnt DESC, tags.tag";
        $tag = new Tags();
        $tag->query($query);
        if ($tag->N) {
            while ($tag->fetch()) {
                $tagList[] = clone($tag);
                // Return prematurely if we hit the tag limit:
                if ($limit > 0 && count($tagList) >= $limit) {
                    return $tagList;
                }
            }
        }

        return $tagList;
    }
    
    function addTag($tag, $user)
    {
        require_once 'services/MyResearch/lib/Tags.php';
        require_once 'services/MyResearch/lib/Resource_tags.php';
    
        $tags = new Tags();
        $tags->tag = $tag;
        if (!$tags->find(true)) {
            $tags->insert();
        }
        
        $rTag = new Resource_tags();
        $rTag->resource_id = $this->id;
        $rTag->tag_id = $tags->id;
        if (!$rTag->find()) {
            $rTag->user_id = $user->id;
            $rTag->insert();
        }
        
        return true;
    }
    
    function addComment($body, $user)
    {
        require_once 'services/MyResearch/lib/Comments.php';
        
        $comment = new Comments();
        $comment->user_id = $user->id;
        $comment->resource_id = $this->id;
        $comment->comment = $body;
        $comment->created = date('Y-m-d h:i:s');
        $comment->insert();
        
        return true;
    }
    
    function getComments()
    {
        require_once 'services/MyResearch/lib/Comments.php';
        
        $sql = "SELECT comments.*, concat(user.firstname, ' ', user.lastname) as fullname " .
               "FROM comments RIGHT OUTER JOIN user on comments.user_id = user.id " .
               "WHERE comments.resource_id = '$this->id' ORDER BY comments.created";
        
        $commentList = array();

        $comment = new Comments();
        $comment->query($sql);
        if ($comment->N) {
            while ($comment->fetch()) {
                $commentList[] = clone($comment);
            }
        }

        return $commentList;
    }

}
