<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2006 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

function new_utils_get_new_projects ($start_time,$offset,$limit) {
  $query = "SELECT group_id,unix_group_name,group_name,short_description,register_time FROM groups " .
           "WHERE access != '".db_es(Project::ACCESS_PRIVATE)."' AND status='A' AND type=1 AND type=1 " .
           "AND register_time < ".db_ei($start_time)." ".
           "ORDER BY register_time ";
  if (isset($limit) && $limit != 0) {
    $query .= "DESC LIMIT ".db_ei($limit);
  } elseif (isset($offset)) {
    $query .= "DESC LIMIT ".db_ei($offset).",21";
  }
  
  return($query);
}

function new_utils_get_new_releases_short($start_time) {
    $frsrf = new FRSReleaseFactory();

  $select = "SELECT groups.group_name AS group_name, "
	  . "groups.group_id AS group_id, "
	  . "groups.unix_group_name AS unix_group_name, "
	  . "frs_release.release_id AS release_id, "
	  . "frs_release.name AS release_version, "
	  . "frs_release.release_date AS release_date, "
          . "frs_package.package_id AS package_id ";

  $from = "FROM groups,frs_package,frs_release ";

  $where = "WHERE frs_release.release_date > ".db_ei($start_time)." "
         . "AND frs_release.package_id = frs_package.package_id "
	 . "AND frs_package.group_id = groups.group_id "
         . "AND frs_release.status_id=".$frsrf->STATUS_ACTIVE." "
         . "AND groups.access != '".db_es(Project::ACCESS_PRIVATE)."'";

    $group = "GROUP BY frs_release.release_id "
        . "ORDER BY frs_release.release_date DESC";
    return $select.$from.$where.$group;
}
