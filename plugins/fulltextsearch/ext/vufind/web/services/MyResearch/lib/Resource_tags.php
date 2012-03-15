<?php
/**
 * Table Definition for resource_tags
 */
require_once 'DB/DataObject.php';

class Resource_tags extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'resource_tags';                   // table name
    public $id;                              // int(11)  not_null primary_key auto_increment
    public $resource_id;                     // int(11)  not_null multiple_key
    public $tag_id;                          // int(11)  not_null multiple_key
    public $list_id;                         // int(11)  multiple_key
    public $user_id;                         // int(11)  multiple_key
    public $posted;                          // timestamp(19)  not_null unsigned zerofill binary timestamp

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Resource_tags',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
