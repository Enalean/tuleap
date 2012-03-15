<?php
/**
 * Table Definition for comments
 */
require_once 'DB/DataObject.php';

class Comments extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'comments';                        // table name
    public $id;                              // int(11)  not_null primary_key auto_increment
    public $user_id;                         // int(11)  not_null multiple_key
    public $resource_id;                     // int(11)  not_null multiple_key
    public $comment;                         // blob(65535)  not_null blob
    public $created;                         // datetime(19)  not_null binary

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Comments',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
