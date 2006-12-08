<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// $Id$
//

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for ArtifactField 
 */
class ArtifactFieldDao extends DataAccessObject {
    /**
    * Constructs the ArtifactFieldDao
    * @param $da instance of the DataAccess class
    */
    function ArtifactFieldDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM artifact_field";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches field_id for (multi_)assigned_to By artifactTypeId
    * @return DataAccessResult
    */
    function & searchAssignedToFieldIdByArtifactTypeId($artifactTypeId) {
        $sql = sprintf(" SELECT field_id ".
                       " FROM artifact_field ".
                       " WHERE group_artifact_id = %s ".
                       "   AND (field_name = 'assigned_to' OR field_name = 'multi_assigned_to') ",
				$artifactTypeId);
        return $this->retrieve($sql);
    }
}


?>