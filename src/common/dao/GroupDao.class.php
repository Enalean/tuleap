<?php



require_once('include/DataAccessObject.class.php');
require_once('GroupFilter.php');

/**
 *  Data Access Object for User 
 */
class GroupDao extends DataAccessObject {
    /**
     * Constructs the GroupDao
     * @param $da instance of the DataAccess class
     */
    function GroupDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
     * Gets all tables of the db
     * @return DataAccessResult
     adapeter cette methode
    */
    function & searchAll($offset=null, $limit=null) {
        $this->sql = "SELECT  SQL_CALC_FOUND_ROWS * FROM user";

        if($offset !== null && $limit !== null) {
            $this->sql .= ' LIMIT '.$this->da->escapeInt($offset).','.$this->da->escapeInt($limit);
        }        
        return $this->retrieve($this->sql);
    }
    
    /**
     * search group by criteria
     * adapter cette methode
     *
     */
    function & searchUserByCriteria($ca, $offset, $limit) {

        $sql = 'SELECT SQL_CALC_FOUND_ROWS * ';
        $sql .= 'FROM user ';

        $iwhere = 0;

        foreach($ca as $c) {
            
            if ($c->getJoin()) {
                $join .= $c->getJoin();
            }
   
            if ($iwhere >= 1) {
                $where .= ' AND '.$c->getWhere();
                $iwhere++;
            }
            else {
                $where .= $c->getWhere();
                $iwhere++;
            }

            if ($c->getGroupBy() !== null) {
                $groupby .= $c->getGroupBy();
            }
        }  

        if ($join !== null) {
            $sql .= ' JOIN '.$join;
        }

        $sql .= ' WHERE '.$where;
        
        if ($groupby !== null) {
            $sql .= ' GROUP BY '.$groupby;
        }
   
        $sql .= ' ORDER BY user.user_name, user.realname, user.status';
        $sql .= ' LIMIT '.$offset.', '.$limit;

        return $this->retrieve($sql);
    }

    function getFoundRows() {
        $sql = 'SELECT FOUND_ROWS() as nb';
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->getRow();
            return $row['nb'];
        } else {
            return false;
        }
    }
    
    /**
     * count the number of row of a resource
     * @return int
     */
    function & count($function) {   
        $count = db_numrows($this->function);
        return $count;
    }
}

class GroupName implements Statement {

    private $name;

    function __construct($name) {
        $this->name = $name;
    }

    function getJoin() {}

    function getWhere() {
        return '(user_name LIKE \'%'.$this->name.'%\' OR realname LIKE \'%'.$this->name.'%\')';
    }

    function getGroupBy() {}
}


//adapter les classe et les requete sql
class GroupGroup implements Statement {

    private $group;

    function __construct($group) {
        $this->group = $group;
    }

    function getJoin() {
        return  'user_group ON (user.user_id = user_group.user_id) JOIN groups ON (user_group.group_id = groups.group_id)';
    }

    function getWhere() {
        return '(groups.group_name LIKE \'%'.$this->group.'%\' OR groups.unix_group_name LIKE \'%'.$this->group.'%\')';
    }

    function getGroupBy() {
        return 'user.user_id';
    }
}

class GroupStatus implements Statement {

    private $status;

    function __construct($status) {
        $this->status = $status;
    }

    function getJoin() {}

    function getWhere() {
        return 'user.status = \''.$this->status.'\'';
    }

    function getGroupBy() {}
}

class GroupShortcut implements Statement {

    private $shortcut;

    function __construct($shortcut) {
        $this->shortcut = $shortcut;
    }

    function getJoin() {}

    function getWhere() {
        return '(user_name LIKE \''.$this->shortcut.'%\')';
    }

    function getGroupBy() {}
}

//rajouter des classe en fonction des criteres

?>
