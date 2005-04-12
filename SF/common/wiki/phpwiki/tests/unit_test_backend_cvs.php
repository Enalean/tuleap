<?php
/**
 * Unit tests the 'lib/WikiDB/backend/cvs.php' file and with it
 * the class WikiDB_backend_cvs. This isn't based on the PhpUnit, and
 * is designed to be run directly using the php4 command.
 *
 * Author: Gerrit Riessen, gerrit.riessen@open-source-consultants.de
 */

// assume that the we've cd'ed to the tests directory
ini_set('include_path', '..' );

function rcs_id()
{
}

if ( $USER == "root" ) {
  // root user can't check in to a CVS repository
  print( "can not be run as root\n" );
  exit();
}

// set to false if something went wrong
$REMOVE_DEBUG = true;

require_once( 'lib/WikiDB/backend/cvs.php' );

$db_params                           = array();
/**
 * These are the parameters required by the backend. 
 */
$db_params[CVS_PAGE_SOURCE]          = "../pgsrc";
$db_params[CVS_CHECK_FOR_REPOSITORY] = true;
// the following three are removed if the test succeeds.
$db_params[CVS_DOC_DIR]              = "/tmp/wiki_docs";
$db_params[CVS_REPOSITORY]           = "/tmp/wiki_repository";
$db_params[CVS_DEBUG_FILE]           = "/tmp/php_cvs.log";

//
// Check the creation of a new CVS repository and the importing of
// the default pages.
//
$cvsdb = new WikiDB_backend_cvs( $db_params );
// check that all files contained in page source where checked in.
$allPageNames = array();
$d = opendir( $db_params[CVS_PAGE_SOURCE] );
while ( $entry = readdir( $d ) ) {
    exec( "grep 'Checking in $entry' " . $db_params[CVS_DEBUG_FILE],
          $cmdOutput, $cmdRetval );
    
    if ( !is_dir( $db_params[CVS_PAGE_SOURCE] . "/" . $entry )) {
        $allPageNames[] = $entry;
        
        if ( $cmdRetval ) {
            print "*** Error: [$entry] was not checked in -- view " 
                . $db_params[CVS_DEBUG_FILE] . " for details\n";
            $REMOVE_DEBUG = false;
        }
    }
}
closedir( $d );

//
// Check that the meta data files were created
//
function get_pagedata( $page_name, $key, &$cvsdb ) 
{
    global $REMOVE_DEBUG;
    $pageHash = $cvsdb->get_pagedata( $page_name );
    if ( $pageHash[CMD_VERSION] != "1" ) {
        print ( "*** Error: [$page_name] version wrong 1 != "
                . $pageHash[CMD_VERSION] ."\n" );
        $REMOVE_DEBUG = false;
    }

    $new_data = array();
    $new_data[CMD_CONTENT] = "";
    $cvsdb->update_pagedata( $page_name, $new_data );

    $pageHash = $cvsdb->get_pagedata( $page_name );
    if ( $pageHash[CMD_VERSION] != "2" ) {
        print ( "*** Error: [$page_name] version wrong 2 != "
                . $pageHash[CMD_VERSION] ."\n" );
        $REMOVE_DEBUG = false;
    }
}
array_walk( $allPageNames, 'get_pagedata', $cvsdb );

//
// test the add and delete pages
//
$new_page_data = array();
$pname = "Hello_World_Fubar";

$new_page_data[CMD_CONTENT] = "hello world\nPlease to meet you\n\n";
$cvsdb->update_pagedata( $pname, $new_page_data );
if ( $cvsdb->get_latest_version( $pname ) != "1" ) {
    print( "***Error Line " . __LINE__ . ": expecting version number 1\n");
    $REMOVE_DEBUG=false;
}

$new_page_data[CMD_CONTENT] = "goodbye cruel world\nbye bye....\n";
$cvsdb->update_pagedata( $pname, $new_page_data );
if ( $cvsdb->get_latest_version( $pname ) != "2" ) {
    print( "***Error Line " . __LINE__ . ": expecting version number 2\n");
    $REMOVE_DEBUG=false;
}

//
// clean up after ourselves
//
if ( $REMOVE_DEBUG ) {
    exec( "rm -fr " . $db_params[CVS_DOC_DIR], $cmdout, $retval );
    exec( "rm -fr " . $db_params[CVS_REPOSITORY], $cmdout, $retval );
    exec( "rm -f " . $db_params[CVS_DEBUG_FILE], $cmdout, $retval );
    print "Test was succesful\n";
} else {
    print "It appears something went wrong, nothing being removed\n";
}

?>