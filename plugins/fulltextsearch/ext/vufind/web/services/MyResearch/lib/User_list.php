<?php
/**
 * Table Definition for user_list
 */
require_once 'DB/DataObject.php';

class User_list extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'user_list';                       // table name
    public $id;                              // int(11)  not_null primary_key auto_increment
    public $user_id;                         // int(11)  not_null multiple_key
    public $title;                           // string(200)  not_null
    public $description;                     // string(500)  
    public $created;                         // datetime(19)  not_null binary
    public $public;                          // int(11)  not_null

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('User_list',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
    
    function getResources($tags = null)
    {
        $resourceList = array();

        $sql = "SELECT DISTINCT resource.* FROM resource, user_resource " .
               "WHERE resource.id = user_resource.resource_id " .
               "AND user_resource.user_id = '$this->user_id' " .
               "AND user_resource.list_id = '$this->id'";

        if ($tags) {
            for ($i=0; $i<count($tags); $i++) {
                $sql .= " AND resource.id IN (SELECT DISTINCT resource_tags.resource_id " .
                    "FROM resource_tags, tags " .
                    "WHERE resource_tags.tag_id=tags.id AND tags.tag = '" . 
                    addslashes($tags[$i]) . "' AND resource_tags.user_id = '$this->user_id' " .
                    "AND resource_tags.list_id = '$this->id')";
            }
        }

        $resource = new Resource();
        $resource->query($sql);
        if ($resource->N) {
            while ($resource->fetch()) {
                $resourceList[] = clone($resource);
            }
        }

        return $resourceList;
    }
    
    function getTags()
    {
        $tagList = array();

        $sql = "SELECT resource_tags.* FROM resource, resource_tags, user_resource " .
               "WHERE resource.id = user_resource.resource_id " .
               "AND resource.id = resource_tags.resource_id " .
               "AND user_resource.user_id = '$this->user_id' " .
               "AND user_resource.list_id = '$this->id'";
        $resource = new Resource();
        $resource->query($sql);
        if ($resource->N) {
            while ($resource->fetch()) {
                $tagList[] = clone($resource);
            }
        }

        return $tagList;
    }

    /**
     * @todo: delete any unused tags
     */
    function removeResource($resource)
    {
        // Remove the Saved Resource
        $join = new User_resource();
        $join->user_id = $this->user_id;
        $join->resource_id = $resource->id;
        $join->list_id = $this->id;
        $join->delete();
        
        // Remove the Tags from the resource
        $join = new Resource_tags();
        $join->user_id = $this->user_id;
        $join->resource_id = $resource->id;
        $join->list_id = $this->id;
        $join->delete();
    }

}

?>
