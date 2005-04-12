<?php rcs_id('$Id$');
/*
 Copyright (C) 2002 Johannes Große (Johannes Gro&szlig;e)

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */ 
/**
 * Gets an image from the cache and prints it to the browser.
 * This file belongs to WikiPluginCached.
 * @author  Johannes Große
 * @version 0.8
 */

include "lib/config.php";
require_once(dirname(__FILE__)."/stdlib.php");
require_once('lib/Request.php');
if (ENABLE_USER_NEW) require_once("lib/WikiUserNew.php");
else                 require_once("lib/WikiUser.php");
require_once('lib/WikiDB.php');

require_once "lib/WikiPluginCached.php";

// -----------------------------------------------------------------------

function deducePagename ($request) {
    if ($request->getArg('pagename'))
        return $request->getArg('pagename');

    if (USE_PATH_INFO) {
        $pathinfo = $request->get('PATH_INFO');
        $tail = substr($pathinfo, strlen(PATH_INFO_PREFIX));
        if ($tail != '' and $pathinfo == PATH_INFO_PREFIX . $tail) {
            return $tail;
        }
    }
    elseif ($this->isPost()) {
        global $HTTP_GET_VARS;
        if (isset($HTTP_GET_VARS['pagename'])) { 
            return $HTTP_GET_VARS['pagename'];
        }
    }

    $query_string = $request->get('QUERY_STRING');
    if (preg_match('/^[^&=]+$/', $query_string))
        return urldecode($query_string);
    
    return HOME_PAGE;
}

function deduceUsername() {
    global $request, $HTTP_SERVER_VARS, $HTTP_ENV_VARS;
    if (!empty($request->args['auth']) and !empty($request->args['auth']['userid']))
        return $request->args['auth']['userid'];
    if (!empty($HTTP_SERVER_VARS['PHP_AUTH_USER']))
        return $HTTP_SERVER_VARS['PHP_AUTH_USER'];
    if (!empty($HTTP_ENV_VARS['REMOTE_USER']))
        return $HTTP_ENV_VARS['REMOTE_USER'];
    
    if ($user = $request->getSessionVar('wiki_user')) {
        $request->_user = $user;
        $request->_user->_authhow = 'session';
        return ENABLE_USER_NEW ? $user->UserName() : $request->_user;
    }
    if ($userid = $request->getCookieVar('WIKI_ID')) {
        if (!empty($userid) and substr($userid,0,2) != 's:') {
            $request->_user->authhow = 'cookie';
            return $userid;
        }
    }
    return false;
}

/**
 * Initializes PhpWiki and calls the plugin specified in the url to
 * produce an image. Furthermore, allow the usage of Apache's
 * ErrorDocument mechanism in order to make this file only called when 
 * image could not be found in the cache.
 * (see doc/README.phpwiki-cache for further information).
 */
function mainImageCache() {
    $request = new Request;   
    // normalize pagename
    $request->setArg('pagename', deducePagename($request));
    $pagename = $request->getArg('pagename');

    // assume that every user may use the cache    
    global $user; // FIXME: necessary ?
    if (ENABLE_USER_NEW) {
        $userid = deduceUsername();	
        if (isset($request->_user) and 
            !empty($request->_user->_authhow) and 
            $request->_user->_authhow == 'session')
        {
            if (isset($request->_user) and 
                ( ! isa($request->_user,WikiUserClassname())
                  or (strtolower(get_class($request->_user)) == '_passuser')))
            {
                $request->_user = WikiUser($userid,$request->_user->_prefs);
            }
            unset($request->_user->_HomePagehandle);
            $request->_user->hasHomePage();
            // update the lockfile filehandle
            if (  isa($request->_user,'_FilePassUser') and 
                  $request->_user->_file->lockfile and 
                  !$request->_user->_file->fplock  )
	    {
                $request->_user = new _FilePassUser($userid,$request->_user->_prefs,$request->_user->_file->filename);
            }
            $request->_prefs = & $request->_user->_prefs;
        } else {
            $user = WikiUser($userid);
            $request->_user =& $user;
            $request->_prefs =& $request->_user->_prefs;
        }
    } else {
        $user = new WikiUser($request, deduceUsername());
        $request->_user =& $user;
        $request->_prefs = $request->_user->getPreferences();
    }
    $dbi = WikiDB::open($GLOBALS['DBParams']);
    
    // Enable the output of most of the warning messages.
    // The warnings will screw up zip files and setpref though.
    // They will also screw up my images... But I think 
    // we should keep them.
    global $ErrorManager;
    $ErrorManager->setPostponedErrorMask(E_NOTICE|E_USER_NOTICE);

    $id = $request->getArg('id');
    $args = $request->getArg('args');
    $request->setArg('action', 'imagecache');

    if ($id) {
        // this indicates a direct call (script wasn't called as
        // 404 ErrorDocument)
    } else {
        // deduce image id or image args (plugincall) from
        // refering URL

        $uri = $request->get('REDIRECT_URL');
        $query = $request->get('REDIRECT_QUERY_STRING');
        $uri .= $query ? '?'.$query : '';        

        if (!$uri) {
            $uri = $request->get('REQUEST_URI');
        }
        if (!$uri) {
            WikiPluginCached::printError( 'png', 
                'Could not deduce image identifier or creation'
                . ' parameters. (Neither REQUEST nor REDIRECT'
                . ' obtained.)' ); 
            return;
        }    
        $cacheparams = $GLOBALS['CacheParams'];
        if (!preg_match(':^(.*/)?'.$cacheparams['filename_prefix'].'([^\?/]+)\.img(\?args=([^\?&]*))?$:', $uri, $matches)) {
            WikiPluginCached::printError('png', "I do not understand this URL: $uri");
            return;
        }        
        
        $request->setArg('id',$matches[2]);
        if ($matches[4]) {
           $request->setArg('args',rawurldecode($matches[4]));
        }
        $request->setStatus(200); // No, we do _not_ have an Error 404 :->
    }

    WikiPluginCached::fetchImageFromCache($dbi,$request,'png');
}


mainImageCache();


// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>
