<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2006 (c) The SourceForge Crew
// http://sourceforge.net
//
// 
require_once ('common/frs/FRSReleaseFactory.class.php');

function new_utils_get_new_projects ($start_time,$offset,$limit) {
  $query = "SELECT group_id,unix_group_name,group_name,short_description,register_time FROM groups " .
           "WHERE is_public=1 AND status='A' AND type=1 AND type=1 " .
           "AND register_time < $start_time ".
           "ORDER BY register_time ";
  if (isset($limit) && $limit != 0) {
    $query .= "DESC LIMIT $limit";
  } elseif (isset($offset)) {
    $query .= "DESC LIMIT $offset,21";
  }
  
  return($query);
}

function new_utils_get_new_releases_short($start_time) {
  new_utils_get_new_releases($start_time,$select,$from,$where);
  $group = "GROUP BY frs_release.release_id "
	. "ORDER BY frs_release.release_date DESC";
  return $select.$from.$where.$group;
}

function new_utils_get_new_releases_long($start_time, $offset, $limit) {
  new_utils_get_new_releases($start_time,$select,$from,$where);
  $select .= ", groups.short_description AS short_description, "
	. "groups.license AS license, "
	. "user.user_name AS user_name, "
        . "frs_release.released_by AS released_by,"
        . "frs_package.name AS module_name, "
        . "frs_dlstats_grouptotal_agg.downloads AS downloads ";

  $from .= ",user,frs_dlstats_grouptotal_agg ";
  
  $where .= "AND frs_release.released_by = user.user_id "
	  . "AND frs_package.group_id = frs_dlstats_grouptotal_agg.group_id "
          . "AND groups.type=1 ";  //don't include templates or test projects

  $group = "GROUP BY frs_release.release_id "
	 . "ORDER BY frs_release.release_date DESC LIMIT $offset,$limit";

  return $select.$from.$where.$group;
}

function new_utils_get_new_releases($start_time,&$select,&$from,&$where ) {
    $frsrf = new FRSReleaseFactory();

  $select = "SELECT groups.group_name AS group_name, "
	  . "groups.group_id AS group_id, "
	  . "groups.unix_group_name AS unix_group_name, "
	  . "frs_release.release_id AS release_id, "
	  . "frs_release.name AS release_version, "
	  . "frs_release.release_date AS release_date, "
          . "frs_package.package_id AS package_id ";

  $from = "FROM groups,frs_package,frs_release ";

  $where = "WHERE frs_release.release_date > $start_time "
         . "AND frs_release.package_id = frs_package.package_id "
	 . "AND frs_package.group_id = groups.group_id "
         . "AND frs_release.status_id=".$frsrf->STATUS_ACTIVE;

}


?>