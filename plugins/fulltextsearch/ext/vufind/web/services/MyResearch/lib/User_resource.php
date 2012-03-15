<?php
/**
 * Table Definition for user_resource
 */
require_once 'DB/DataObject.php';

class User_resource extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'user_resource';                   // table name
    public $id;                              // int(11)  not_null primary_key auto_increment
    public $user_id;                         // int(11)  not_null multiple_key
    public $resource_id;                     // int(11)  not_null multiple_key
    public $list_id;                         // int(11)  multiple_key
    public $notes;                           // blob(65535)  blob
    public $saved;                           // timestamp(19)  not_null unsigned zerofill binary timestamp

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('User_resource',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
