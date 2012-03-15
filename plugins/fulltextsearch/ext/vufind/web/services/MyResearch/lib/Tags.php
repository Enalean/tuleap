<?php
/**
 * Table Definition for tags
 */
require_once 'DB/DataObject.php';

class Tags extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'tags';                            // table name
    public $id;                              // int(11)  not_null primary_key auto_increment
    public $tag;                             // string(25)  not_null

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Tags',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
    
    function getResources()
    {
        $resList = array();

        $sql = "SELECT resource.* FROM resource_tags, resource " .
               "WHERE resource.id = resource_tags.resource_id " .
               "AND resource_tags.tag_id = '$this->id'";
        $res = new Resource();
        $res->query($sql);
        if ($res->N) {
            while ($res->fetch()) {
                $resList[] = clone($res);
            }
        }

        return $resList;
    }
}
