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
     * search group by filter
     * adapter cette methode
     *
     */
    function searchGroupByFilter($ca, $offset, $limit) {

        $sql = 'SELECT SQL_CALC_FOUND_ROWS groups.group_id, group_name, unix_group_name, groups.status, type, is_public, license, count(user.user_id) as c, name '.
               'FROM user JOIN user_group ON user.user_id = user_group.user_id '. 
               'JOIN groups ON user_group.group_id = groups.group_id '.
               'JOIN group_type ON groups.type = group_type.type_id ';
           

        if (!empty($ca)) {

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

        }
   
        $sql .= ' GROUP BY groups.group_id';
        $sql .= ' ORDER BY groups.group_name';
        $sql .= ' LIMIT '.$offset.', '.$limit;

        return $this->retrieve($sql);
    }

    /**
     * search the email of groups admins
     *
     */
    function searchAdminEmailByFilter($ca) {

        $sql = 'SELECT email,user_group.user_id, user_group.group_id '.
               'FROM user '.
               'JOIN user_group ON user.user_id = user_group.user_id '.
               'JOIN groups ON user_group.group_id = groups.group_id '.
               'WHERE admin_flags = \'A\'';

        if (!empty($ca)) {

            foreach($ca as $c) {
            
                if ($c->getWhere()) {
                    $where .= ' AND '.$c->getWhere();
                }
                
                if ($c->getGroupBy() !== null) {
                    $groupby .= $c->getGroupBy();
                }
            }  
                   
            if ($groupby !== null) {
                $sql .= ' GROUP BY '.$groupby;
            }
            $sql .= $where;
        }
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
