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
