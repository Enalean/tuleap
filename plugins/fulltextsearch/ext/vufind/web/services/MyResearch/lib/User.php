<?php
/**
 * Table Definition for user
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

require_once 'User_resource.php';
require_once 'User_list.php';
require_once 'Resource_tags.php';
require_once 'Tags.php';

class User extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'user';                // table name
    public $id;                              // int(11)  not_null primary_key auto_increment
    public $username;                        // string(30)  not_null unique_key
    public $password;                        // string(32)  not_null
    public $firstname;                       // string(50)  not_null
    public $lastname;                        // string(50)  not_null
    public $email;                           // string(250)  not_null
    public $cat_username;                    // string(50)  
    public $cat_password;                    // string(50)  
    public $college;                         // string(100)  not_null
    public $major;                           // string(100)  not_null
    public $created;                         // datetime(19)  not_null binary

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('User',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
    
    function __sleep()
    {
        return array('id', 'username', 'password', 'cat_username', 'cat_password', 'firstname', 'lastname', 'email', 'college', 'major');
    }
    
    function __wakeup()
    {
    }
    
    function hasResource($resource)
    {
        $join = new User_resource();
        $join->user_id = $this->id;
        $join->resource_id = $resource->id;
        if ($join->find()) {
            return true;
        } else {
            return false;
        }
    }

    function addResource($resource, $list, $tagArray, $notes)
    {
        $join = new User_resource();
        $join->user_id = $this->id;
        $join->resource_id = $resource->id;
        $join->list_id = $list->id;
        if ($join->find(true)) {
            if ($notes) {
                $join->notes = $notes;
                $join->update();
            }
            $result = true;
        } else {
            if ($notes) {
                $join->notes = $notes;
            }
            $result = $join->insert();
        }
        if ($result) {
            if (is_array($tagArray) && count($tagArray)) {
                $join = new Resource_tags();
                $join->resource_id = $resource->id;
                $join->user_id = $this->id;
                $join->list_id = $list->id;
                $join->delete();
                foreach ($tagArray as $value) {
                    $value = str_replace('"', '', $value);
                    $tag = new Tags();
                    $tag->tag = $value;
                    if (!$tag->find(true)) {
                        $tag->insert();
                    }
                    $join->tag_id = $tag->id;
                    $join->insert();
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @todo: delete any unused tags
     */
    function removeResource($resource)
    {
        // Remove the Saved Resource
        $join = new User_resource();
        $join->user_id = $this->id;
        $join->resource_id = $resource->id;
        $join->delete();
        
        // Remove the Tags from the resource
        $join = new Resource_tags();
        $join->user_id = $this->id;
        $join->resource_id = $resource->id;
        $join->delete();
    }

    function getResources($tags = null)
    {
        $resourceList = array();

        $sql = "SELECT DISTINCT resource.* FROM resource, user_resource " .
               "WHERE resource.id = user_resource.resource_id " .
               "AND user_resource.user_id = '$this->id'";

        if ($tags) {
            for ($i=0; $i<count($tags); $i++) {
                $sql .= " AND resource.id IN (SELECT DISTINCT resource_tags.resource_id " .
                    "FROM resource_tags, tags " .
                    "WHERE resource_tags.tag_id=tags.id AND tags.tag = '" . 
                    addslashes($tags[$i]) . "' AND resource_tags.user_id = '$this->id')";
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

    function getSavedData($resourceId, $listId = null)
    {
        $savedList = array();

        $sql = "SELECT user_resource.*, user_list.title as list_title, user_list.id as list_id " . 
               "FROM user_resource, resource, user_list " .
               "WHERE resource.id = user_resource.resource_id " .
               "AND user_resource.list_id = user_list.id " . 
               "AND user_resource.user_id = '$this->id' " .
               "AND resource.record_id = '$resourceId'";
        if (!is_null($listId)) {
            $sql .= " AND user_resource.list_id='$listId'";
        }
        $saved = new User_resource();
        $saved->query($sql);
        if ($saved->N) {
            while ($saved->fetch()) {
                $savedList[] = clone($saved);
            }
        }

        return $savedList;
    }


    function getTags($resourceId = null, $listId = null)
    {
        $tagList = array();

        $sql = "SELECT MIN(tags.id), tags.tag, COUNT(resource_tags.id) AS cnt " .
               "FROM tags, resource_tags, user_resource, resource " .
               "WHERE tags.id = resource_tags.tag_id " .
               "AND user_resource.user_id = '$this->id' " .
               "AND user_resource.resource_id = resource.id " .
               "AND resource_tags.user_id = '$this->id' " .
               "AND resource.id = resource_tags.resource_id " .
               "AND user_resource.list_id = resource_tags.list_id ";
        if (!is_null($resourceId)) {
            $sql .= "AND resource.record_id = '$resourceId' ";
        }
        if (!is_null($listId)) {
            $sql .= "AND resource_tags.list_id = '$listId' ";
        }
        $sql .= "GROUP BY tags.tag ORDER BY tag";
        $tag = new Tags();
        $tag->query($sql);
        if ($tag->N) {
            while ($tag->fetch()) {
                $tagList[] = clone($tag);
            }
        }

        return $tagList;
    }
    
    
    function getLists()
    {
        $lists = array();

        $sql = "SELECT user_list.*, COUNT(user_resource.id) AS cnt FROM user_list " . 
               "LEFT JOIN user_resource ON user_list.id = user_resource.list_id " .
               "WHERE user_list.user_id = '$this->id' " . 
               "GROUP BY user_list.id, user_list.user_id, user_list.title, " .
               "user_list.description, user_list.created, user_list.public " .
               "ORDER BY user_list.title";
        $list = new User_list();
        $list->query($sql);
        if ($list->N) {
            while ($list->fetch()) {
                $lists[] = clone($list);
            }
        }

        return $lists;
    }


}
