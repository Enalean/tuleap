<?php
/**
 * Table Definition for session
 */
require_once 'DB/DataObject.php';

class Session extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'session';                        // table name
    public $id;                              // int(11)  not_null primary_key auto_increment
    public $session_id;                      // string(128)  unique_key
    public $data;                            // blob(65535)  blob
    public $last_used;                       // int(12)  not_null
    public $created;                         // datetime(19)  not_null binary

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Session',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
