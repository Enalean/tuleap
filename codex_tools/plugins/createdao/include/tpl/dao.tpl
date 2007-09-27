//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// $Id$
//

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for <?=$classname?> 
 */
class <?=$classname?>Dao extends DataAccessObject {
    /**
    * Constructs the <?=$classname?>Dao
    * @param $da instance of the DataAccess class
    */
    function <?=$classname?>Dao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM <?=$table?>";
        return $this->retrieve($sql);
    }
    
<?=$accessors?>
}

