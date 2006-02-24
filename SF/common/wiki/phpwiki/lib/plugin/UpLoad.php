<?php // -*-php-*-
rcs_id('$Id$');
/*
 Copyright 2003, 2004 $ThePhpWikiProgrammingTeam

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
 * UpLoad:  Allow Administrator to upload files to a special directory,
 *          which should preferably be added to the InterWikiMap
 * Usage:   <?plugin UpLoad ?>
 * Author:  NathanGass <gass@iogram.ch>
 * Changes: ReiniUrban <rurban@x-ray.at>,
 *          qubit <rtryon@dartmouth.edu>
 * Note:    See also Jochen Kalmbach's plugin/UserFileManagement.php
 */

    /* Change these config variables to your needs. Paths must end with "/".
     */

class WikiPlugin_UpLoad
extends WikiPlugin
{
    //var $file_dir = PHPWIKI_DIR . "/img/";
    //var $url_prefix = DATA_PATH . "/img/";
    //what if the above are not set in index.php? seems to fail...

    var $disallowed_extensions;
    // todo: use PagePerms instead
    var $only_authenticated = true; // allow only authenticated users upload.

    function getName () {
        return "UpLoad";
    }

    function getDescription () {
        return _("Upload files to the local InterWiki Upload:<filename>");
    }

    function getDefaultArguments() {
        return array('logfile'  => 'file_list.txt',
        	     // add a link of the fresh file automatically to the 
        	     // end of the page (or current page)
        	     'autolink' => true, 
        	     'page'     => '[pagename]',
        	     );
    }

    function run($dbi, $argstr, &$request, $basepage) {
        $this->disallowed_extensions = explode("\n",
"ad[ep]
asd
ba[st]
chm
cmd
com
cgi
cpl
crt
dll
eml
exe
hlp
hta
in[fs]
isp
jse?
lnk
md[betw]
ms[cipt]
nws
ocx
ops
pcd
p[ir]f
php
pl
py
reg
sc[frt]
sh[bsm]?
swf
url
vb[esx]?
vxd
ws[cfh]");
        //removed "\{[[:xdigit:]]{8}(?:-[[:xdigit:]]{4}){3}-[[:xdigit:]]{12}\}"

        $args = $this->getArgs($argstr, $request);
        extract($args);

        $file_dir = defined('PHPWIKI_DIR') ? 
            PHPWIKI_DIR . "/uploads/" : "uploads/";
        $url_prefix = SERVER_NAME . DATA_PATH; 

        $form = HTML::form(array('action' => $request->getPostURL(),
                                 'enctype' => 'multipart/form-data',
                                 'method' => 'post'));
        $contents = HTML::div(array('class' => 'wikiaction'));
        $contents->pushContent(HTML::input(array('type' => 'hidden',
                                                 'name' => 'MAX_FILE_SIZE',
                                                 'value' => MAX_UPLOAD_SIZE)));
        /// MV add pv
        /// @todo: have a generic method to transmit pv
        if(!empty($_GET['pv'])) {
            $contents->pushContent(HTML::input(array('type' => 'hidden',
                                                     'name' => 'pv',
                                                     'value' => $_GET['pv'])));
        }
        $contents->pushContent(HTML::input(array('name' => 'userfile',
                                                 'type' => 'file',
                                                 'size' => '50')));
        $contents->pushContent(HTML::raw(" "));
        $contents->pushContent(HTML::input(array('value' => _("Upload"),
                                                 'type' => 'submit')));
        $form->pushContent($contents);

        $message = HTML();
        $userfile = $request->getUploadedFile('userfile');
        if ($userfile) {
            $userfile_name = $userfile->getName();
            $userfile_name = basename($userfile_name);
            $userfile_tmpname = $userfile->getTmpName();

            if ($this->only_authenticated) {
                // Make sure that the user is logged in.
                //
                $user = $request->getUser();
                if (!$user->isAuthenticated()) {
                    $message->pushContent(_("ACCESS DENIED: You must log in to upload files."),
                                          HTML::br(),HTML::br());
                    $result = HTML();
                    $result->pushContent($form);
                    $result->pushContent($message);
                    return $result;
                }
            }

            $userfile_size = $userfile->getSize();
            $userfile_type = $userfile->getType();

            require_once($_SERVER['DOCUMENT_ROOT'].'/../common/wiki/lib/WikiAttachment.class');
            $wa = new WikiAttachment(GROUP_ID);
            $rev = $wa->createRevision($userfile_name, $userfile_size, $userfile_type, $userfile_tmpname);
            if($rev >= 0) {
                $prev = $rev+1;
            	$interwiki = new PageType_interwikimap();
            	$link = $interwiki->link("Upload:$prev/$userfile_name");
                $message->pushContent(_("File successfully uploaded."));
                $message->pushContent(HTML::ul(HTML::li($link)));

                // MV: Do not log uploads.
                // the upload was a success and we need to mark this event in the "upload log"
                //$upload_log = $file_dir . basename($logfile);
                //if ($logfile) { 
                //    $this->log($userfile, $upload_log, &$message);
                //}
                if ($autolink) {
                    require_once("lib/loadsave.php");
                    $pagehandle = $dbi->getPage($page);
                    if ($pagehandle->exists()) {// don't replace default contents
                        $current = $pagehandle->getCurrentRevision();
                        $version = $current->getVersion();
                        $text = $current->getPackedContent();
                        $newtext = $text . "\n* [Upload:$prev/$userfile_name]";
                        $meta = $current->_data;
                        $meta['summary'] = sprintf(_("uploaded %s revision %d"),$userfile_name,$prev);
                        $meta['author'] = user_getname();
                        $pagehandle->save($newtext, $version + 1, $meta);
                    }
                }
            }
            else {
                //$message->pushContent(HTML::br(),_("Uploading failed: "),$userfile_name, HTML::br());
            }
        }
        else {
            $message->pushContent(HTML::br(),HTML::br());
        }

        //$result = HTML::div( array( 'class' => 'wikiaction' ) );
        $result = HTML();
        $result->pushContent($form);
        $result->pushContent($message);
        return $result;
    }

    function log ($userfile, $upload_log, &$message) {
    	global $Theme;
    	$user = $GLOBALS['request']->_user;
        if (!is_writable($upload_log)) {
            $message->pushContent(_("Error: the upload log is not writable"));
            $message->pushContent(HTML::br());
        }
        elseif (!$log_handle = fopen ($upload_log, "a")) {
            $message->pushContent(_("Error: can't open the upload logfile"));
            $message->pushContent(HTML::br());
        }
        else {        // file size in KB; precision of 0.1
            $file_size = round(($userfile->getSize())/1024, 1);
            if ($file_size <= 0) {
                $file_size = "&lt; 0.1";
            }
            $userfile_name = $userfile->getName();
            fwrite($log_handle,
                   "\n"
                   . "<tr><td><a href=\"$userfile_name\">$userfile_name</a></td>"
                   . "<td align=\"right\">$file_size kB</td>"
                   . "<td>&nbsp;&nbsp;" . $Theme->formatDate(time()) . "</td>"
                   . "<td>&nbsp;&nbsp;<em>" . $user->getId() . "</em></td></tr>");
            fclose($log_handle);
        }
        return;
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

// $Log$
// Revision 1.1  2005/04/12 13:33:34  guerin
// First commit for wiki integration.
// Added Manuel's code as of revision 13 on Partners.
// Very little modification at the moment:
// - removed use of DOCUMENT_ROOT and SF_LOCAL_INC_PREFIX
// - simplified require syntax
// - removed ST-specific code (for test phase)
//
// Revision 1.10  2004/04/12 10:19:18  rurban
// fixed copyright year
//
// Revision 1.9  2004/04/12 10:18:22  rurban
// removed the hairy regex line
//
// Revision 1.8  2004/04/12 09:12:22  rurban
// fix syntax errors
//
// Revision 1.7  2004/04/09 17:49:03  rurban
// Added PhpWiki RssFeed to Sidebar
// sidebar formatting
// some browser dependant fixes (old-browser support)
//
// Revision 1.6  2004/02/27 01:36:51  rurban
// autolink enabled
//
// Revision 1.5  2004/02/27 01:24:43  rurban
// use IntwerWiki links for uploaded file.
// autolink to page prepared, but not yet ready
//
// Revision 1.4  2004/02/21 19:12:59  rurban
// patch by Sascha Carlin
//
// Revision 1.3  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.2  2004/01/26 09:18:00  rurban
// * changed stored pref representation as before.
//   the array of objects is 1) bigger and 2)
//   less portable. If we would import packed pref
//   objects and the object definition was changed, PHP would fail.
//   This doesn't happen with an simple array of non-default values.
// * use $prefs->retrieve and $prefs->store methods, where retrieve
//   understands the interim format of array of objects also.
// * simplified $prefs->get() and fixed $prefs->set()
// * added $user->_userid and class '_WikiUser' portability functions
// * fixed $user object ->_level upgrading, mostly using sessions.
//   this fixes yesterdays problems with loosing authorization level.
// * fixed WikiUserNew::checkPass to return the _level
// * fixed WikiUserNew::isSignedIn
// * added explodePageList to class PageList, support sortby arg
// * fixed UserPreferences for WikiUserNew
// * fixed WikiPlugin for empty defaults array
// * UnfoldSubpages: added pagename arg, renamed pages arg,
//   removed sort arg, support sortby arg
//
// Revision 1.1  2003/11/04 18:41:41  carstenklapp
// New plugin which was submitted to the mailing list some time
// ago. (This is the best UpLoad function I have seen for PhpWiki so
// far. Cleaned up text formatting and typos from the version on the
// mailing list. Still needs a few adjustments.)
//
?>
