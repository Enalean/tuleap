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
    function & searchGroupByFilter($ca, $offset, $limit) {

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


?>
