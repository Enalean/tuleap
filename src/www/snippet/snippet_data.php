<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2004. All rights reserved
//
// $Id: snippet_data.php 936 2004-03-30 08:05:27Z guerin $
//
//
//
//  Written for CodeX by Nicolas Guérin
//


// Return true if the current user has rights to modify an existing snippet
// Only the snippet author(s) or site admin may edit snippet details
function snippet_data_can_modify_snippet($snippet_id) {
    if (user_is_super_user()) {
        return true;
    } else {
        $sql="SELECT submitted_by FROM snippet_version WHERE  snippet_id='$snippet_id'";
        $result=db_query($sql);
        while($resrow = db_fetch_array($result)) {
            if ($resrow['submitted_by']==user_getid()) {
                return true;
                break;
            }
        }
    }
    return false;
}

// Return true if the current user has rights to modify an existing snippet package
// Only the snippet package author(s) or site admin may edit snippet package details
function snippet_data_can_modify_snippet_package($snippet_id) {
    if (user_is_super_user()) {
        return true;
    } else {
        $sql="SELECT submitted_by FROM snippet_package_version WHERE snippet_package_id='$snippet_package_id'";
        $result=db_query($sql);
        while($resrow = db_fetch_array($result)) {
            if ($resrow['submitted_by']==user_getid()) {
                return true;
                break;
            }
        }
    }
    return false;
}


// Return the language name when given the language Id
function snippet_data_get_language_from_id($lang_id) {
    $sql="SELECT language_name FROM snippet_language WHERE language_id=$lang_id";
    $result = db_query ($sql);
    return db_result($result,0,0);
}

// Return the category name when given the category Id
function snippet_data_get_category_from_id($cat_id) {
    $sql="SELECT category_name FROM snippet_category WHERE category_id=$cat_id";
    $result = db_query ($sql);
    return db_result($result,0,0);
}

// Return the type name when given the type Id
function snippet_data_get_type_from_id($type_id) {
    $sql="SELECT type_name FROM snippet_type WHERE type_id=$type_id";
    $result = db_query ($sql);
    return db_result($result,0,0);
}

// Return the license name when given the license Id
function snippet_data_get_license_from_id($license_id) {
    $sql="SELECT license_name FROM snippet_license WHERE license_id=$license_id";
    $result = db_query ($sql);
    return db_result($result,0,0);
}

// Return all languages as db result
function snippet_data_get_all_languages() {
    $sql="SELECT * FROM snippet_language ORDER BY language_name";
    $result = db_query ($sql);
    return $result;
}

// Return all categories as db result
function snippet_data_get_all_categories() {
    $sql="SELECT * FROM snippet_category ORDER BY category_name";
    $result = db_query ($sql);
    return $result;
}

// Return all types as db result
function snippet_data_get_all_types() {
    $sql="SELECT * FROM snippet_type ORDER BY type_name";
    $result = db_query ($sql);
    return $result;
}

// Return all licenses as db result
function snippet_data_get_all_licenses() {
    $sql="SELECT * FROM snippet_license ORDER BY license_name";
    $result = db_query ($sql);
    return $result;
}

?>
