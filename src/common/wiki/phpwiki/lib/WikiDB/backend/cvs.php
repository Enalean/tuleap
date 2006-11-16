<?php
rcs_id('$Id: cvs.php,v 1.26 2005/11/14 22:24:33 rurban Exp $');
/**
 * Backend for handling CVS repository. 
 *
 * ASSUMES: that the shell commands 'cvs', 'grep', 'rm', are all located 
 * ASSUMES: in the path of the server calling this script.
 *
 * Author: Gerrit Riessen, gerrit.riessen@open-source-consultants.de
 */

require_once('lib/WikiDB/backend.php');
require_once('lib/ErrorManager.php');

/** 
 * Constants used by the CVS backend 
 **/
// these are the parameters defined in db_params
define( 'CVS_DOC_DIR',              'doc_dir' );
define( 'CVS_REPOSITORY',           'repository' );
define( 'CVS_CHECK_FOR_REPOSITORY', 'check_for_repository' );
define( 'CVS_DEBUG_FILE',           'debug_file' );
define( 'CVS_PAGE_SOURCE',          'pgsrc' );
define( 'CVS_MODULE_NAME',          'module_name' );

// these are the things that are defined in the page hash
// CMD == Cvs Meta Data
define( 'CMD_LAST_MODIFIED', 'lastmodified' );
define( 'CMD_CONTENT',       '%content');
define( 'CMD_CREATED',       'created');
define( 'CMD_VERSION',       'version');
define( 'CMD_AUTHOR',        'author');
define( 'CMD_LINK_ATT',      '_links_' );

// file names used to store specific information
define( 'CVS_MP_FILE',              '.most_popular' );
define( 'CVS_MR_FILE',              '.most_recent' );

class WikiDB_backend_cvs
extends WikiDB_backend
{
    var $_docDir;
    var $_repository;
    var $_module_name;
    var $_debug_file;

    /**
     * In the following parameters should be defined in dbparam:
     *   . wiki ==> directory where the pages should be stored
     *              this is not the CVS repository location
     *   . repository ==> local directory where the repository should be 
     *                    created. This can also be a :pserver: but then
     *                    set check_for_repository to false and checkout
     *                    the documents beforehand. (This is basically CVSROOT)
     *   . check_for_repository ==> boolean flag to indicate whether the 
     *                              repository should be created, this only
     *                              applies to local directories, for pserver
     *                              set this to false and check out the 
     *                              document base beforehand
     *   . debug_file ==> file name where debug information should be sent.
     *                    If file doesn't exist then it's created, if this
     *                    is empty, then debugging is turned off.
     *   . pgsrc ==> directory name where the default wiki pages are stored.
     *               This is only required if the backend is to create a
     *               new CVS repository.
     *
     * The class also adds a parameter 'module_name' to indicate the name
     * of the cvs module that is being used to version the documents. The
     * module_name is assumed to be the base name of directory given in
     * wiki, e.g. if wiki == '/some/path/to/documents' then module_name 
     * becomes 'documents' and this module will be created in the CVS 
     * repository or assumed to exist. If on the other hand the parameter
     * already exists, then it is not overwritten.
     */
    function WikiDB_backend_cvs( $dbparam ) 
    {
        // setup all the instance values.
        $this->_docDir = $dbparam{CVS_DOC_DIR};
        $this->_repository = $dbparam{CVS_REPOSITORY};
        if ( ! $dbparam{CVS_MODULE_NAME} ) {
            $this->_module_name = basename( $this->_docDir );
            $dbparam{CVS_MODULE_NAME} = $this->_module_name;
        } else {
            $this->_module_name = $dbparam{CVS_MODULE_NAME};
        }
        $this->_debug_file = $dbparam{CVS_DEBUG_FILE};

        if ( $dbparam{CVS_CHECK_FOR_REPOSITORY}
             && !( is_dir( $this->_repository )
                   && is_dir( $this->_repository . "/CVSROOT" )
                   && is_dir( $this->_repository . "/" . $this->_module_name ))) {

            $this->_cvsDebug( sprintf("Creating new repository [%s]", $this->_repository) );

            // doesn't exist, need to create it and the replace the wiki 
            // document directory.
            $this->_mkdir( $this->_repository, 0775 );
    
            // assume that the repository is a local directory, prefix :local:
            if ( !ereg( "^:local:", $this->_repository ) ) {
                $this->_repository = ":local:" . $this->_repository;
            }
            
            $cmdLine = sprintf( "cvs -d \"%s\" init", $this->_repository);
            $this->_execCommand( $cmdLine, $cmdOutput, true );

            $this->_mkdir( $this->_docDir, 0775 );
            $cmdLine = sprintf("cd %s; cvs -d \"%s\" import -m no_message "
                               ."%s V R", $this->_docDir, $this->_repository,
                               $this->_module_name );
            $this->_execCommand( $cmdLine, $cmdOutput, true );
            
            // remove the wiki directory and check it out from the 
            // CVS repository
            $cmdLine = sprintf( "rm -fr %s; cd %s; cvs -d \"%s\" co %s",
                                $this->_docDir, dirname($this->_docDir), 
                                $this->_repository, $this->_module_name);
            $this->_execCommand( $cmdLine, $cmdOutput, true );
            
            // add the default pages using the update_pagedata
            $metaData = array();
            $metaData[$AUTHOR] = "PhpWiki -- CVS Backend";

            if ( is_dir( $dbparam[CVS_PAGE_SOURCE] ) ) {
                $d = opendir( $dbparam[CVS_PAGE_SOURCE] );
                while ( $entry = readdir( $d ) ) {
                    $filename = $dbparam[CVS_PAGE_SOURCE] . "/" . $entry;
                    $this->_cvsDebug( sprintf("Found [%s] in [%s]", $entry, $dbparam[CVS_PAGE_SOURCE]) );
                    
                    if ( is_file( $filename ) ) {
                        $metaData[CMD_CONTENT] = join('',file($filename));
                        $this->update_pagedata( $entry, $metaData );
                    }
                }
                closedir( $d );
            }
            
            // ensure that the results of the is_dir are cleared
            clearstatcache();
        }
    }

    /**
     * Return: metadata about page
     */
    function get_pagedata($pagename) 
    {
        // the metadata information about a page is stored in the 
        // CVS directory of the document root in serialized form. The
        // file always has the name, i.e. '_$pagename'.
        $metaFile = $this->_docDir . "/CVS/_" . $pagename;

        if ( file_exists( $metaFile ) ) {
            
            $megaHash = 
                 unserialize(join( '',$this->_readFileWithPath($metaFile)));

            $filename = $this->_docDir . "/" . $pagename;
            if ( file_exists( $filename ) ) {
                $megaHash[CMD_CONTENT] = $this->_readFileWithPath( $filename );
            } else {
                $megaHash[CMD_CONTENT] = "";
            }

            $this->_updateMostRecent( $pagename );
            $this->_updateMostPopular( $pagename );

            return $megaHash;
        } else {
            return false;
        }
    }

    /**
     * This will create a new page if page being requested does not
     * exist.
     */
    function update_pagedata($pagename, $newdata = array() ) 
    {
        // check argument
        if ( ! is_array( $newdata ) ) {
            trigger_error("update_pagedata: Argument 'newdata' was not array", 
                          E_USER_WARNING);
        }

        // retrieve the meta data
        $metaData = $this->get_pagedata( $pagename );

        if ( ! $metaData ) {
            $this->_cvsDebug("update_pagedata: no meta data found");
            // this means that the page does not exist, we need to create
            // it.
            $metaData = array();

            $metaData[CMD_CREATED] = time();
            $metaData[CMD_VERSION] = "1";

            if ( ! isset($newdata[CMD_CONTENT])) {
                $metaData[CMD_CONTENT] = "";
            } else {
                $metaData[CMD_CONTENT] = $newdata[CMD_CONTENT];
            }

            // create an empty page ...
            $this->_writePage( $pagename, $metaData[CMD_CONTENT] );
            $this->_addPage( $pagename );

            // make sure that the page is written and committed a second time
            unset( $newdata[CMD_CONTENT] );
            unset( $metaData[CMD_CONTENT] );
        }

        // change any meta data information
        foreach ( $newdata as $key => $value ) {
            if ( $value == false || empty( $value ) ) {
                unset( $metaData[$key] );
            } else {
                $metaData[$key] = $value;
            }
        }

        // update the page data, if required. Use newdata because it could
        // be empty and thus unset($metaData[CMD_CONTENT]).
        if ( isset( $newdata[CMD_CONTENT] ) ) {
            $this->_writePage( $pagename, $newdata[CMD_CONTENT] );
        }

        // remove any content from the meta data before storing it
        unset( $metaData[CMD_CONTENT] );
        $metaData[CMD_LAST_MODIFIED] = time();

        $metaData[CMD_VERSION] = $this->_commitPage( $pagename, $metaData );
        $this->_writeMetaInfo( $pagename, $metaData );
    }

    function get_latest_version($pagename) 
    {
        $metaData = $this->get_pagedata( $pagename );
        if ( $metaData ) {
            // the version number is everything after the '1.'
            return $metaData[CMD_VERSION];
        } else {
            $this->_cvsDebug(sprintf("get_latest_versioned FAILED for [%s]", $pagename));
            return 0;
        }
    }

    function get_previous_version($pagename, $version) 
    {
        // cvs increments the version numbers, so this is real easy ;-)
        return ($version > 0 ? $version - 1 : 0);
    }

    /**
     * the version parameter is assumed to be everything after the '1.'
     * in the CVS versioning system.
     */
    function get_versiondata($pagename, $version, $want_content = false) 
    {
        $this->_cvsDebug( "get_versiondata: [$pagename] [$version] [$want_content]" );
      
        $filedata = "";
        if ( $want_content ) {
            // retrieve the version from the repository
            $cmdLine = sprintf("cvs -d \"%s\" co -p -r 1.%d %s/%s 2>&1", 
                               $this->_repository, $version, 
                               $this->_module_name, $pagename );
            $this->_execCommand( $cmdLine, $filedata, true );
        
            // TODO: DEBUG: 5 is a magic number here, depending on the
            // TODO: DEBUG: version of cvs used here, 5 might have to
            // TODO: DEBUG: change. Basically find a more reliable way of
            // TODO: DEBUG: doing this.
            // the first 5 lines contain various bits of 
            // administrative information that can be ignored.
            for ( $i = 0; $i < 5; $i++ ) {
                array_shift( $filedata );
            }
        }

        /**
         * Now obtain the rest of the pagehash information, this is contained
         * in the log message for the revision in serialized form.
         */
        $cmdLine = sprintf("cd %s; cvs log -r1.%d %s", $this->_docDir,
                           $version, $pagename );
        $this->_execCommand( $cmdLine, $logdata, true );

        // shift log data until we get to the 'revision X.X' line
        // FIXME: ensure that we don't enter an endless loop here
        while ( !ereg( "^revision 1.([0-9]+)$", $logdata[0], $revInfo ) ) {
            array_shift( $logdata );
        }

        // serialized hash information now stored in position 2
        $rVal = unserialize( _unescape( $logdata[2] ) );

        // version information is incorrect
        $rVal[CMD_VERSION] = $revInfo[1];
        $rVal[CMD_CONTENT] = $filedata;

        foreach ( $rVal as $key => $value ) {
            $this->_cvsDebug( "$key == [$value]" );
        }
      
        return $rVal;
    }

    /**
     * See ADODB for a better delete_page(), which can be undone and is seen in RecentChanges.
     * See backend.php
     */
    //function delete_page($pagename) { $this->purge_page($pagename); }

    /**
     * This returns false if page was not deleted or could not be deleted
     * else return true.
     */
    function purge_page($pagename) 
    {
        $this->_cvsDebug( "delete_page [$pagename]") ;
        $filename = $this->_docDir . "/" . $pagename;
        $metaFile = $this->_docDir . "/CVS/_" . $pagename;
        
        // obtain a write block before deleting the file
        if ( $this->_deleteFile( $filename ) == false ) {
            return false;
        }
        
        $this->_deleteFile( $metaFile );
        
        $this->_removePage( $pagename );

        return true;
    }

    /**
     * For now delete and create a new one.
     *
     * This returns false if page was not renamed,
     * else return true.
     */
    function rename_page($pagename, $to) 
    {
        $this->_cvsDebug( "rename_page [$pagename,$to]") ;
	$data = get_pagedata($pagename);
	if (isset($data['pagename']))
	  $data['pagename'] = $to;
	//$version = $this->get_latest_version($pagename);
	//$vdata = get_versiondata($pagename, $version, 1);
        //$data[CMD_CONTENT] = $vdata[CMD_CONTENT];
	$this->delete_page($pagename);
	$this->update_pagedata($to, $data);
	return true;
    }

    function delete_versiondata($pagename, $version) 
    {
        // TODO: Not Implemented.
        // TODO: This is, for CVS, difficult because it implies removing a
        // TODO: revision somewhere in the middle of a revision tree, and
        // TODO: this is basically not possible!
        trigger_error("delete_versiondata: Not Implemented", E_USER_WARNING);
    }

    function set_versiondata($pagename, $version, $data) 
    {
        // TODO: Not Implemented.
        // TODO: requires changing the log(commit) message for a particular
        // TODO: version and this can't be done??? (You can edit the repository
        // TODO: file directly but i don't know of a way of doing it via
        // TODO: the cvs tools).
        trigger_error("set_versiondata: Not Implemented", E_USER_WARNING);
    }

    function update_versiondata($pagename, $version, $newdata) 
    {
        // TODO: same problem as set_versiondata
        trigger_error("set_versiondata: Not Implemented", E_USER_WARNING);
    }

    function set_links($pagename, $links) 
    {
        // TODO: needs to be tested ....
        $megaHash = get_pagedata( $pagename );
        $megaHash[CMD_LINK_ATT] = $links;
        $this->_writeMetaInfo( $pagename, $megaHash );
    }

    function get_links($pagename, $reversed=true, $include_empty=false,
                       $sortby=false, $limit=false, $exclude=false)
    {
        // TODO: ignores the $reversed argument and returns
        // TODO: the value of _links_ attribute of the meta information
        // TODO: to implement a reversed version, i guess, we going to
        // TODO: need to do a grep on all files for the pagename in 
        // TODO: in question and return all those page names that contained
        // TODO: the required pagename!
        $megaHash = get_pagedata( $pagename );
        return $megaHash[CMD_LINK_ATT];
    }

    /* function get_all_revisions($pagename) {
        // TODO: should replace this with something more efficient
        include_once('lib/WikiDB/backend/dumb/AllRevisionsIter.php');
        return new WikiDB_backend_dumb_AllRevisionsIter($this, $pagename);
    } */

    function get_all_pages($include_empty=false, $sortby=false, $limit=false) 
    {
        // FIXME: this ignores the parameters.
        return new Cvs_Backend_Array_Iterator(
                              $this->_getAllFileNamesInDir( $this->_docDir ));
    }

    function text_search($search, $fullsearch = false, $orderby=false, $limit=false, $exclude=false) 
    {
        if ( $fullsearch ) {
            $iter = new Cvs_Backend_Full_Search_Iterator(
                               $this->_getAllFileNamesInDir( $this->_docDir ), 
                               $search, 
                               $this->_docDir );
            $iter->stoplisted =& $search->stoplisted;
            return $iter;
        } else {
            return new Cvs_Backend_Title_Search_Iterator(
                               $this->_getAllFileNamesInDir( $this->_docDir ),
                               $search);
        }
    }

    function most_popular($limit, $sortby='') {
        // TODO: needs to be tested ...
        $mp = $this->_getMostPopular();
        if ($limit < 0){
            asort ($mp, SORT_NUMERIC);
            $limit = -$limit;
        } else {
            arsort( $mp, SORT_NUMERIC );
        }
        $returnVal = array();
        
        while ( (list($key, $val) = each($a)) && $limit > 0 ) {
            $returnVal[] = $key;
            $limit--;
        }
        return $returnVal;
    }

    /**
     * This only accepts the 'since' and 'limit' attributes, everything
     * else is ignored.
     */
    function most_recent($params) 
    {
        // TODO: needs to be tested ...
        // most recent are those pages with the highest time value ...
        $mr = $this->_getMostRecent();
        $rev = false;
        $returnVal = array();
        if ( isset( $params['limit'] ) ) {
            $limit = $params['limit'];
            $rev = $limit < 0;
        }
        if ($rev){
            arsort( $mr, SORT_NUMERIC );
        } else {
            asort( $mr, SORT_NUMERIC );
        }
        if ( isset( $limit ) ) {
            while ( (list($key, $val) = each($a)) && $limit > 0 ) {
                $returnVal[] = $key;
                $limit--;
            }
        } else if ( isset( $params['since'] ) ) {
            while ( (list($key, $val) = each($a)) ) {
                
                if ( $val > $params['since'] ) {
                    $returnVal[] = $key;
                }
            }
        }

        return new Cvs_Backend_Array_Iterator( $returnVal );
    }

    function lock($write_lock = true) 
    {
        // TODO: to be implemented
        trigger_error("lock: Not Implemented", E_USER_WARNING);
    }

    function unlock($force = false) 
    {
        // TODO: to be implemented
        trigger_error("unlock: Not Implemented", E_USER_WARNING);
    }

    function close () 
    {
    }

    function sync() 
    {
    }

    function optimize() 
    {
    }

    /**
     * What we do here is take a listing of the documents directory and
     * check that each page has metadata file. If not, then a metadata
     * file is created for the page.
     *
     * This can happen if rebuild() was called and someone has added
     * files to the CVS repository not via PhpWiki. These files are 
     * added to the document directory but without any metadata files.
     */
    function check() 
    {
        // TODO:
        // TODO: test this .... i.e. add test to unit test file.
        // TODO:
        $page_names = $this->_getAllFileNamesInDir($this->_docDir);
        $meta_names = $this->_getAllFileNamesInDir($this->_docDir . "/CVS");

        array_walk( $meta_names, '_strip_leading_underscore' );
        reset( $meta_names );
        $no_meta_files = array_diff( $page_names, $meta_names );

        array_walk( $no_meta_files, '_create_meta_file', $this );

        return true;
    }

    /**
     * Do an update of the CVS repository 
     */
    function rebuild() 
    {
        // TODO:
        // TODO: test this .... i.e. add test to unit test file.
        // TODO:
        $cmdLine = sprintf( "cd %s; cvs update -d 2>&1", $this->_docDir );
        $this->_execCommand( $cmdLine, $cmdOutput, true );
        return true;
    }
    
    // 
    // ..-.-..-.-..-.-.. .--..-......-.--. --.-....----.....
    // The rest are all internal methods, not to be used 
    // directly.
    // ..-.-..-.-..-.-.. .--..-......-.--. --.-....----.....
    //
    function _create_meta_file( $page_name, $key, &$backend )
    {
        // this is used as part of an array walk and therefore takes
        // the backend argument
        $backend->_cvsDebug(sprintf("Creating meta file for [%s]", $page_name));
        $backend->update_pagedata( $page_name, array() );
    }

    function _strip_leading_underscore( &$item ) 
    {
        $item = ereg_replace( "^_", "", $item );
    }

    /**
     * update the most popular information by incrementing the count
     * for the following page. If the page was not defined, it is entered
     * with a value of 1.
     */
    function _updateMostPopular( $pagename )
    {
        $mp = $this->_getMostPopular();
        if ( isset( $mp[$pagename] ) ) {
            $mp[$pagename]++;
        } else {
            $mp[$pagename] = 1;
        }
        $this->_writeFileWithPath( $this->_docDir . "/CVS/" . CVS_MP_FILE, 
                                   serialize( $mp ) );
    }


    /**
     * Returns an array containing the most popular information. This
     * creates the most popular file if it does not exist.
     */
    function _getMostPopular()
    {
        $mostPopular = $this->_docDir . "/CVS/" . CVS_MP_FILE;
        if ( !file_exists( $mostPopular ) ) {
            $this->_writeFileWithPath( $mostPopular, serialize( array() ) );
        }
        return unserialize(join( '',$this->_readFileWithPath($mostPopular)));
    }

    function _getMostRecent()
    {
        $mostRecent = $this->_docDir . "/CVS/" . CVS_MR_FILE;
        if ( !file_exists( $mostRecent ) ) {
            $this->_writeFileWithPath( $mostRecent, serialize( array() ) );
        }
        return unserialize(join( '',$this->_readFileWithPath($mostRecent)));
    }

    function _updateMostRecent( $pagename )
    {
        $mr = $this->_getMostRecent();
        $mr[$pagename] = time();
        $this->_writeFileWithPath( $this->_docDir . "/CVS/" . CVS_MR_FILE, 
                                   serialize( $mr ) );
    }

    function _writeMetaInfo( $pagename, $hashInfo )
    {
        $this->_writeFileWithPath( $this->_docDir . "/CVS/_" . $pagename, 
                                   serialize( $hashInfo ) );
    }
    function _writePage( $pagename, $content )
    {
        $this->_writeFileWithPath( $this->_docDir . "/". $pagename, $content );
    }
    function _removePage( $pagename )
    {
        $cmdLine = sprintf("cd %s; cvs remove %s 2>&1; cvs commit -m '%s' "
                           ."%s 2>&1", $this->_docDir, $pagename, 
                           "remove page", $pagename );
        
        $this->_execCommand( $cmdLine, $cmdRemoveOutput, true );
    }

    /**
     * this returns the new version number of the file.
     */
    function _commitPage( $pagename, &$meta_data )
    {
        $cmdLine = sprintf( "cd %s; cvs commit -m \"%s\" %s 2>&1", 
                            $this->_docDir, 
                            escapeshellcmd( serialize( $meta_data ) ),
                            $pagename );
        $this->_execCommand( $cmdLine, $cmdOutput, true );

        $cmdOutput = implode( "\n", $cmdOutput );
        $revInfo = array();
        ereg( "\nnew revision: 1[.]([0-9]+); previous revision: ", $cmdOutput,
              $revInfo );

        $this->_cvsDebug( "CP: revInfo 0: $revInfo[0]" );
        $this->_cvsDebug( "CP: $cmdOutput" );
        if ( isset( $revInfo[1] ) ) {
            $this->_cvsDebug( "CP: got revision information" );
            return $revInfo[1];
        } else {
            ereg( "\ninitial revision: 1[.]([0-9]+)", $cmdOutput, $revInfo );
            if ( isset( $revInfo[1] ) ) {
                $this->_cvsDebug( "CP: is initial release" );
                return 1;
            }
            $this->_cvsDebug( "CP: returning old version" );
            return $meta_data[CMD_VERSION];
        }
    }
    function _addPage( $pagename )
    {
        // TODO: need to add a check for the mimetype so that binary
        // TODO: files are added as binary files
        $cmdLine = sprintf("cd %s; cvs add %s 2>&1", $this->_docDir, 
                           $pagename );
        $this->_execCommand( $cmdLine, $cmdAddOutput, true );
    }

    /**
     * Returns an array containing all the names of files contained
     * in a particular directory. The list is sorted according the 
     * string representation of the filenames.
     */
    function _getAllFileNamesInDir( $dirName ) 
    {
        $namelist = array();
        $d = opendir( $dirName );
        while ( $entry = readdir( $d ) ) {
            $namelist[] = $entry;
        }
        closedir( $d );
        sort( $namelist, SORT_STRING );
        return $namelist;
    }

    /**
     * Recursively create all directories.
     */
    function _mkdir( $path, $mode ) 
    {
        $directoryName = dirname( $path );
        if ( $directoryName != "/" && $directoryName != "\\"  
             && !is_dir( $directoryName ) && $directoryName != "" ) {
            $rVal = $this->_mkdir( $directoryName, $mode );
        }
        else {
            $rVal = true;
        }
      
        return ($rVal && @mkdir( $path, $mode ) );
    }

    /**
     * Recursively create all directories and then the file.
     */
    function _createFile( $path, $mode ) 
    {
        $this->_mkdir( dirname( $path ), $mode );
        touch( $path );
        chmod( $path, $mode );
    }

    /**
     * The lord giveth, and the lord taketh.
     */
    function _deleteFile( $filename )
    {
        if( $fd = fopen($filename, 'a') ) { 
            
            $locked = flock($fd,2);  // Exclusive blocking lock 

            if (!$locked) { 
                $this->_cvsError("Unable to delete file, lock was not obtained.",
                                 __LINE__, $filename, EM_NOTICE_ERRORS );
            } 

            if ( ($rVal = unlink( $filename )) != 0 ) {
                $this->_cvsDebug( "[$filename] --> Unlink returned [$rVal]" );
            }

            return $rVal;
        } else {
            $this->_cvsError( "deleteFile: Unable to open file",
                      __LINE__, $filename, EM_NOTICE_ERRORS );
            return false;
        }
    }

    /**
     * Called when something happened that causes the CVS backend to 
     * fail.
     */
    function _cvsError( $msg     = "no message", 
                        $errline = 0, 
                        $errfile = "lib/WikiDB/backend/cvs.php",
                        $errno   = EM_FATAL_ERRORS)
    {
        $err = new PhpError( $errno, "[CVS(be)]: " . $msg, $errfile, $errline);
        // send error to the debug routine
        $this->_cvsDebug( $err->asXML() );
        // send the error to the error manager
        $GLOBALS['ErrorManager']->handleError( $err );
    }

    /**
     * Debug function specifically for the CVS database functions.
     * Can be deactived by setting the WikiDB['debug_file'] to ""
     */
    function _cvsDebug( $msg )  
    {
        if ( $this->_debug_file == "" ) {
            return;
        }
        
        if ( !file_exists( $this->_debug_file  ) ) {
            $this->_createFile( $this->_debug_file, 0755 );
        }

        if ( $fdlock = @fopen( $this->_debug_file, 'a' ) ) {
            $locked = flock( $fdlock, 2 );
            if ( !$locked ) {
                fclose( $fdlock );
                return;
            }
            
            $fdappend = @fopen( $this->_debug_file, 'a' );
            fwrite( $fdappend, ($msg . "\n") );
            fclose( $fdappend );
            fclose( $fdlock );
        }
        else {
            // TODO: this should be replaced ...
            printf("unable to locate/open [%s], turning debug off\n", $filename);
            $this->_debug_file = "";
        }
    }

    /**
     * Execute a command and potentially exit if the flag exitOnNonZero is 
     * set to true and the return value was nonZero
     */
    function _execCommand( $cmdLine, &$cmdOutput, $exitOnNonZero )
    {
        $this->_cvsDebug( sprintf("Preparing to execute [%s]", $cmdLine) );
        exec( $cmdLine, $cmdOutput, $cmdReturnVal );
        if ( $exitOnNonZero && ($cmdReturnVal != 0) ) {
            $this->_cvsDebug( sprintf("Command failed [%s], Output: ", $cmdLine) ."[". 
                              join("\n",$cmdOutput) . "]" );
            $this->_cvsError( sprintf("Command failed [%s], Return value: %s", $cmdLine, $cmdReturnVal),
                            __LINE__ );
        }
        $this->_cvsDebug( "Done execution [" . join("\n", $cmdOutput) . "]" );

        return $cmdReturnVal;
    }

    /**
     * Read locks a file, reads it, and returns it contents
     */
    function _readFileWithPath( $filename ) 
    {
        if ( $fd = @fopen( $filename, "r" ) )  {
            $locked = flock( $fd, 1 ); // read lock
            if ( !$locked ) {
                fclose( $fd );
                $this->_cvsError( "Unable to obtain read lock.", __LINE__ );
            }

            $content = file( $filename );
            fclose( $fd );
            return $content;
        } else {
            $this->_cvsError( sprintf("Unable to open file '%s' for reading", $filename),
                              __LINE__ );
            return false;
        }
    }

    /**
     * Either replace the contents of an existing file or create a 
     * new file in the particular store using the page name as the
     * file name.
     * 
     * Nothing is returned, might be useful to return something ;-)
     */
    function _writeFileWithPath( $filename, $contents )
    { 
        // TODO: $contents should probably be a reference parameter ...
        if( $fd = fopen($filename, 'a') ) { 
            $locked = flock($fd,2);  // Exclusive blocking lock 
            if (!$locked) { 
                $this->_cvsError( "Timeout while obtaining lock.", __LINE__ );
            } 

            // Second filehandle -- we use this to write the contents
            $fdsafe = fopen($filename, 'w'); 
            fwrite($fdsafe, $contents); 
            fclose($fdsafe); 
            fclose($fd);
        } else {
            $this->_cvsError( sprintf("Could not open file '%s' for writing", $filename), 
                              __LINE__ );
        }
    }

   /**
    * Copy the contents of the source directory to the destination directory.
    */
    function _copyFilesFromDirectory( $src, $dest )
    {
        $this->_cvsDebug( sprintf("Copying from [%s] to [%s]", $src, $dest) );

        if ( is_dir( $src ) && is_dir( $dest ) ) {
            $this->_cvsDebug( "Copying " );
            $d = opendir( $src );
            while ( $entry = readdir( $d ) ) {
                if ( is_file( $src . "/" . $entry )
                     && copy( $src . "/" . $entry, $dest . "/" . $entry ) ) {
                    $this->_cvsDebug( sprintf("Copied to [%s]", "$dest/$entry") );
                } else {
                    $this->_cvsDebug( sprintf("Failed to copy [%s]", "$src/$entry") );
                }
            }
            closedir( $d );
            return true;
        } else {
            $this->_cvsDebug( "Not copying" );
            return false;
        }
    }

    /**
     * Unescape a string value. Normally this comes from doing an 
     * escapeshellcmd. This converts the following:
     *    \{ --> {
     *    \} --> }
     *    \; --> ;
     *    \" --> "
     */
    function _unescape( $val )
    {
        $val = str_replace( "\\{", "{", $val );
        $val = str_replace( "\\}", "}", $val );
        $val = str_replace( "\\;", ";", $val );
        $val = str_replace( "\\\"", "\"", $val );
        
        return $val;
    }

    /**
     * Function for removing the newlines from the ends of the
     * file data returned from file(..). This is used in retrievePage
     */
    function _strip_newlines( &$item, $key )
    {
        $item = ereg_replace( "\n$", "", $item );
    }

} /* End of WikiDB_backend_cvs class */

/**
 * Generic iterator for stepping through an array of values.
 */
class Cvs_Backend_Array_Iterator
extends WikiDB_backend_iterator
{
    var $_array;

    function Cvs_Backend_Iterator( $arrayValue = Array() )
    {
        $this->_array = $arrayValue;
    }

    function next() 
    {
        while ( ($rVal = array_pop( $this->_array )) != NULL ) {
            return $rVal;
        }
        return false;
    }

    function count() {
    	return count($this->_array);
    }

    function free()
    {
        unset( $this->_array );
    }
}

class Cvs_Backend_Full_Search_Iterator
extends Cvs_Backend_Array_Iterator
{
    var $_searchString = '';
    var $_docDir = "";

    function Cvs_Backend_Title_Search_Iterator( $arrayValue = Array(),
                                                $searchString  = "",
                                                $documentDir = ".")
    {
        $this->Cvs_Backend_Array_Iterator( $arrayValue );
        $_searchString = $searchString;
        $_docDir = $documentDir;
    }

    function next()
    {
        do {
            $pageName = Cvs_Backend_Array_Iterator::next();
        } while ( !$this->_searchFile( $_searchString, 
                                       $_docDir . "/" . $pageName ));

        return $pageName;
    }

    /**
     * Does nothing more than a grep and search the entire contents
     * of the given file. Returns TRUE of the searchstring was found, 
     * false if the search string wasn't find or the file was a directory
     * or could not be read.
     */
    function _searchFile( $searchString, $fileName )
    {
        // TODO: using grep here, it might make more sense to use
        // TODO: some sort of inbuilt/language specific method for
        // TODO: searching files.
        $cmdLine = sprintf( "grep -E -i '%s' %s > /dev/null 2>&1", 
                            $searchString, $fileName );
        
        return ( WikiDB_backend_cvs::_execCommand( $cmdLine, $cmdOutput, 
                                                   false ) == 0 );
    }
}

/**
 * Iterator used for doing a title search.
 */
class Cvs_Backend_Title_Search_Iterator
extends Cvs_Backend_Array_Iterator
{
    var $_searchString = '';

    function Cvs_Backend_Title_Search_Iterator( $arrayValue = Array(),
                                                $searchString  = "")
    {
        $this->Cvs_Backend_Array_Iterator( $arrayValue );
        $_searchString = $searchString;
    }

    function next()
    {
        do {
            $pageName = Cvs_Backend_Array_Iterator::next();
        } while ( !eregi( $this->_searchString, $pageName ) );

        return $pageName;
    }
}

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>
