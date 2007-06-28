<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// 
//

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for Reference 
 */
class ReferenceDao extends DataAccessObject {
    /**
    * Constructs the ReferenceDao
    * @param $da instance of the DataAccess class
    */
    function ReferenceDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all references for the given project ID, sorted for presentation
    * @return DataAccessResult
    */
    function & searchByGroupID($group_id) {
        $sql = sprintf("SELECT * FROM reference,reference_group WHERE reference_group.group_id=%s AND reference_group.reference_id=reference.id ORDER BY reference.scope DESC, reference.service_short_name, reference.keyword",
                       $this->da->quoteSmart($group_id));
        return $this->retrieve($sql);
    }

    /**
    * Gets a reference from the reference id and the group ID, so that we also have "is_active" row
    * @return DataAccessResult
    */
    function & searchByIdAndGroupID($ref_id,$group_id) {
        $sql = sprintf("SELECT * FROM reference,reference_group WHERE reference_group.group_id=%s AND reference.id=%s AND reference_group.reference_id=reference.id",
                       $this->da->quoteSmart($group_id),
                       $this->da->quoteSmart($ref_id));
        return $this->retrieve($sql);
    }

    /**
    * Gets all active references for the given project ID
    * @return DataAccessResult
    */
    function & searchActiveByGroupID($group_id) {
        $sql = sprintf("SELECT * FROM reference,reference_group WHERE reference_group.group_id=%s AND reference_group.reference_id=reference.id AND reference_group.is_active=1",
                       $this->da->quoteSmart($group_id));
        return $this->retrieve($sql);
    }

    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM reference";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches Reference by reference Id 
    * @return DataAccessResult
    */
    function & searchById($id) {
        $sql = sprintf("SELECT * FROM reference WHERE id = %s",
                $this->da->quoteSmart($id));
        return $this->retrieve($sql);
    }

    /**
    * Searches Reference by scope 
    * @return DataAccessResult
    */
    function & searchByScope($scope) {
        $sql = sprintf("SELECT * FROM reference WHERE scope = %s",
                $this->da->quoteSmart($scope));
        return $this->retrieve($sql);
    }


    /**
    * Searches Reference by service short name 
    * @return DataAccessResult
    */
    function & searchByServiceShortName($service) {
        $sql = sprintf("SELECT * FROM reference WHERE service_short_name = %s",
                $this->da->quoteSmart($service));
        return $this->retrieve($sql);
    }


    /**
    * Searches Reference by scope and service short name 
    * Don't return reference 100 (empty reference)
    * @return DataAccessResult
    */
    function & searchByScopeAndServiceShortName($scope,$service) {
        $sql = sprintf("SELECT * FROM reference WHERE scope = %s AND service_short_name = %s AND id != 100",
                       $this->da->quoteSmart($scope),
                       $this->da->quoteSmart($service));
        return $this->retrieve($sql);
    }


    /**
    * Searches Reference by scope and service short name 
    * Don't return reference 100 (empty reference)
    * @return DataAccessResult
    */
    function & searchByScopeAndServiceShortNameAndGroupId($scope,$service,$group_id) {
        $sql = sprintf("SELECT * FROM reference,reference_group WHERE scope = %s AND reference.id=reference_group.reference_id AND service_short_name = %s AND group_id = %s AND reference.id != 100",
                       $this->da->quoteSmart($scope),
                       $this->da->quoteSmart($service),
		       $this->da->quoteSmart($group_id));
        return $this->retrieve($sql);
    }


    /**
    * Searches Reference by keyword and group_id 
    * @return DataAccessResult with one field ('reference_id')
    */
    function & searchByKeywordAndGroupId($keyword,$group_id) {
        # Order by scope to return 'P'roject references before 'S'ystem references
        # This may happen for old tracker created before Reference management.
        # Otherwise, there should not be both S and P reference with the same keyword...
        $sql = sprintf("SELECT * FROM reference,reference_group WHERE reference.keyword = %s and reference.id=reference_group.reference_id and reference_group.group_id=%s ORDER BY reference.scope",
                       $this->da->quoteSmart($keyword),
                       $this->da->quoteSmart($group_id));
        return $this->retrieve($sql);
    }


    /**
    * Searches Reference by keyword and group_id 
    * @return DataAccessResult with one field ('reference_id')
    */
    function & searchByKeywordAndGroupIdAndDescriptionAndLinkAndScope($keyword,$group_id,$description,$link,$scope) {
        # Order by scope to return 'P'roject references before 'S'ystem references
        # This may happen for old tracker created before Reference management.
        # Otherwise, there should not be both S and P reference with the same keyword...
        $sql = sprintf("SELECT * FROM reference r,reference_group rg WHERE ".
		       "r.keyword = %s AND ".
		       "r.id=rg.reference_id AND ".
		       "rg.group_id=%s AND ".
		       "r.description = %s AND ".
		       "r.link = %s AND ".
		       "r.scope = %s",
                       $this->da->quoteSmart($keyword),
                       $this->da->quoteSmart($group_id),
		       $this->da->quoteSmart($description),
		       $this->da->quoteSmart($link),
		       $this->da->quoteSmart($scope));
        return $this->retrieve($sql);
    }



    /**
    * create a row in the table reference 
    * @return true or id(auto_increment) if there is no error
    */
    function create($keyword,$desc,$link,$scope,$service_short_name) {
        $sql = sprintf("INSERT INTO reference (keyword,description,link,scope,service_short_name) VALUES (%s, %s, %s, %s, %s);",
                       $this->da->quoteSmart($keyword),
                       $this->da->quoteSmart($desc),
                       $this->da->quoteSmart($link),
                       $this->da->quoteSmart($scope),
                       $this->da->quoteSmart($service_short_name));
        $inserted = $this->update($sql);
        if ($inserted) {
            $dar =& $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($row = $dar->getRow()) {
                $inserted = $row['id'];
            } else {
                $inserted = $dar->isError();
            }
        } 
        return $inserted;
    }

    function create_ref_group($refid,$is_active,$group_id) {
        $sql = sprintf("INSERT INTO reference_group (reference_id,is_active,group_id) VALUES (%s, %s, %s);",
                       $this->da->quoteSmart($refid),
                       $this->da->quoteSmart($is_active),
                       $this->da->quoteSmart($group_id));
        $inserted = $this->update($sql);
        if ($inserted) {
            $dar =& $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($row = $dar->getRow()) {
                $inserted = $row['id'];
            } else {
                $inserted = $dar->isError();
            }
        } 
        return $inserted;
    }
    
    /**
    * update a row in the table reference 
    * @return true or id(auto_increment) if there is no error
    */
    function update_ref($id,$keyword,$desc,$link,$scope,$service_short_name) {
        $sql = sprintf("UPDATE reference SET keyword=%s, description=%s, link=%s, scope=%s, service_short_name=%s WHERE id=%s;",
                       $this->da->quoteSmart($keyword),
                       $this->da->quoteSmart($desc),
                       $this->da->quoteSmart($link),
                       $this->da->quoteSmart($scope),
                       $this->da->quoteSmart($service_short_name),
                       $this->da->quoteSmart($id));
        return $this->update($sql);
    }

    function update_ref_group($refid,$is_active,$group_id) {
        $sql = sprintf("UPDATE reference_group SET is_active=%s WHERE reference_id=%s AND group_id=%s;",
                       $this->da->quoteSmart($is_active),
                       $this->da->quoteSmart($refid),
                       $this->da->quoteSmart($group_id));
        return $this->update($sql);
    }    

    
    function removeById($id) {
        $sql = sprintf("DELETE FROM reference WHERE id = %s",
                $this->da->quoteSmart($id));
        return $this->update($sql);
    }

    function removeRefGroup($id,$group_id) {
        $sql = sprintf("DELETE FROM reference_group WHERE reference_id = %s AND group_id = %s",
                       $this->da->quoteSmart($id),
                       $this->da->quoteSmart($group_id));
        return $this->update($sql);
    }

    function removeAllById($id) {
        $sql = sprintf("DELETE reference, reference_group FROM reference, reference_group WHERE reference.id = %s AND reference_group.reference_id =%s",
                       $this->da->quoteSmart($id),
                       $this->da->quoteSmart($id));
        return $this->update($sql);
    }

}


?>