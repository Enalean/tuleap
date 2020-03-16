<?php
// -*-php-*-
rcs_id('$Id: UpLoad.php,v 1.19 2005/04/11 19:40:15 rurban Exp $');
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

/// MV add
/// Wiki attachments

/**
 * UpLoad:  Allow Administrator to upload files to a special directory,
 *          which should preferably be added to the InterWikiMap
 * Usage:   <?plugin UpLoad ?>
 * Author:  NathanGass <gass@iogram.ch>
 * Changes: ReiniUrban <rurban@x-ray.at>,
 *          qubit <rtryon@dartmouth.edu>
 * Note:    See also Jochen Kalmbach's plugin/UserFileManagement.php
 */

class WikiPlugin_UpLoad extends WikiPlugin
{
    public $disallowed_extensions;
    // TODO: use PagePerms instead
    public $only_authenticated = false; // allow only authenticated users may upload.

    public function getName()
    {
        return "UpLoad";
    }

    public function getDescription()
    {
        return _("Upload files to the local InterWiki Upload:<filename>");
    }

    public function getDefaultArguments()
    {
        return array('logfile'  => false,
                 // add a link of the fresh file automatically to the
                 // end of the page (or current page)
                 'autolink' => false,
                 'page'     => '[pagename]',
                 );
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $this->disallowed_extensions = explode(
            "\n",
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
ws[cfh]"
        );
        //removed "\{[[:xdigit:]]{8}(?:-[[:xdigit:]]{4}){3}-[[:xdigit:]]{12}\}"

        $args = $this->getArgs($argstr, $request);
        extract($args);

        $file_dir = getUploadFilePath();
        //$url_prefix = SERVER_NAME . DATA_PATH;

        $form = HTML::form(array('action' => $request->getPostURL(),
                                 'enctype' => 'multipart/form-data',
                                 'method' => 'post'));
        $contents = HTML::div(array('class' => 'wikiaction'));
        $contents->pushContent(HTML::input(array('type' => 'hidden',
                                                 'name' => 'MAX_FILE_SIZE',
                                                 'value' => MAX_UPLOAD_SIZE)));
        /// MV add pv
        /// @todo: have a generic method to transmit pv
        if (!empty($_REQUEST['pv'])) {
            $contents->pushContent(HTML::input(array('type' => 'hidden',
                                                     'name' => 'pv',
                                                     'value' => $_REQUEST['pv'])));
        }
        $contents->pushContent(HTML::input(array('name' => 'userfile',
                                                 'type' => 'file',
                                                 'size' => '50')));
        $contents->pushContent(HTML::raw(" "));
        $contents->pushContent(HTML::input(array('value' => _("Upload"),
                                                 'type' => 'submit')));
        $form->pushContent($contents);

        $message = HTML();
        if ($request->isPost() and $this->only_authenticated) {
            // Make sure that the user is logged in.
            $user = $request->getUser();
            if (!$user->isAuthenticated()) {
                $message->pushContent(
                    HTML::h2(_("ACCESS DENIED: You must log in to upload files.")),
                    HTML::br(),
                    HTML::br()
                );
                $result = HTML();
                $result->pushContent($form);
                $result->pushContent($message);
                return $result;
            }
        }

        $userfile = $request->getUploadedFile('userfile');
        if ($userfile) {
            $userfile_name = $userfile->getName();
            $userfile_name = trim(basename($userfile_name));
            $userfile_tmpname = $userfile->getTmpName();
            $err_header = HTML::h2(fmt("ERROR uploading '%s': ", $userfile_name));

            /// MV add
            /// Wiki attachments
            $wa  = new WikiAttachment(GROUP_ID);
            $rev = $wa->createRevision(
                $userfile_name,
                $userfile->getSize(),
                $userfile->getType(),
                $userfile->getTmpName()
            );
            if ($rev >= 0) {
                $prev = $rev + 1;
                $interwiki = new PageType_interwikimap();
                $link = $interwiki->link("Upload:$prev/$userfile_name");
                $message->pushContent(HTML::h2(_("File successfully uploaded.")));
                $message->pushContent(HTML::ul(HTML::li($link)));

                // the upload was a success and we need to mark this event in the "upload log"
                if ($logfile) {
                    $upload_log = $file_dir . basename($logfile);
                    $this->log($userfile, $upload_log, $message);
                }
                if ($autolink) {
                    require_once("lib/loadsave.php");
                    $pagehandle = $dbi->getPage($page);
                    if ($pagehandle->exists()) {// don't replace default contents
                        $current = $pagehandle->getCurrentRevision();
                        $version = $current->getVersion();
                        $text = $current->getPackedContent();
                        $newtext = $text . "\n* [Upload:$userfile_name]";
                        $meta = $current->_data;
                        $meta['summary'] = sprintf(_("uploaded %s"), $userfile_name);
                        $pagehandle->save($newtext, $version + 1, $meta);
                    }
                }
            } else {
                $message->pushContent($err_header);
                $message->pushContent(HTML::br(), _("Uploading failed."), HTML::br());
            }
        } else {
            $message->pushContent(HTML::br(), HTML::br());
        }

        /// {{{ Codendi Specific

        // URL arguments
        if (array_key_exists('offset', $_REQUEST)) {
            $offset = $_REQUEST['offset'];
        } else {
            $offset = 0;
        }

        if (array_key_exists('limit', $_REQUEST)) {
            $limit = $_REQUEST['limit'];
        } else {
            $limit = 10;
        }

        $attchTab = HTML::table(array('border' => '1',
                                      'width'  => '100%'));
        $attchTab->pushContent(HTML::tr(
            HTML::th(_("Attachment")),
            HTML::th(_("Number of revision"))
        ));
        $wai = WikiAttachment::getListWithCounter(
            GROUP_ID,
            UserManager::instance()->getCurrentUser()->getId(),
            array('offset' => $offset,
            'nb'     => $limit)
        );
        $wai->rewind();
        while ($wai->valid()) {
            $wa = $wai->current();

            $filename = basename($wa->getFilename());
            $url = getUploadDataPath() . urlencode($filename);

            $line = HTML::tr();
            $line->pushContent(HTML::td(HTML::a(
                array('href' => $url),
                "Attach:" . $filename
            )));
            $line->pushContent(HTML::td($wa->count()));
            $attchTab->pushContent($line);

            $wai->next();
        }
        $attchList = HTML();
        $attchList->pushContent(
            HTML::hr(),
            HTML::h2(_("Attached files"))
        );
        $attchList->pushContent($attchTab);

        $url = WikiURL("UpLoad");
        if (!empty($_REQUEST['pv'])) {
            $url .= '&pv=' . $_REQUEST['pv'];
        }
        $attchList->pushContent(HTML::a(
            array('href' => $url . '&offset=' . ($offset - $limit)),
            "<- Previous"
        ));
        $attchList->pushContent(" - ");
        $attchList->pushContent(HTML::a(
            array('href' => $url . '&offset=' . ($offset + $limit)),
            "Next ->"
        ));
        /// }}}

        //$result = HTML::div( array( 'class' => 'wikiaction' ) );
        $result = HTML();
        $result->pushContent($form);
        $result->pushContent($message);
        $result->pushContent($attchList);
        return $result;
    }

    public function log($userfile, $upload_log, &$message)
    {
        global $WikiTheme;
        $user = $GLOBALS['request']->_user;
        if (!is_writable($upload_log)) {
            trigger_error(_("The upload logfile is not writable."), E_USER_WARNING);
        } elseif (!$log_handle = fopen($upload_log, "a")) {
            trigger_error(_("Can't open the upload logfile."), E_USER_WARNING);
        } else {        // file size in KB; precision of 0.1
            $file_size = round(($userfile->getSize()) / 1024, 1);
            if ($file_size <= 0) {
                $file_size = "&lt; 0.1";
            }
            $userfile_name = $userfile->getName();
            fwrite(
                $log_handle,
                "\n"
                   . "<tr><td><a href=\"$userfile_name\">$userfile_name</a></td>"
                   . "<td align=\"right\">$file_size kB</td>"
                   . "<td>&nbsp;&nbsp;" . $WikiTheme->formatDate(time()) . "</td>"
                . "<td>&nbsp;&nbsp;<em>" . $user->getId() . "</em></td></tr>"
            );
            fclose($log_handle);
        }
        return;
    }
}

// $Log: UpLoad.php,v $
// Revision 1.19  2005/04/11 19:40:15  rurban
// Simplify upload. See https://sourceforge.net/forum/message.php?msg_id=3093651
// Improve UpLoad warnings.
// Move auth check before upload.
//
// Revision 1.18  2005/02/12 17:24:24  rurban
// locale update: missing . : fixed. unified strings
// proper linebreaks
//
// Revision 1.17  2004/11/09 08:15:50  rurban
// trim filename
//
// Revision 1.16  2004/10/21 19:03:37  rurban
// Be more stricter with uploads: Filenames may only contain alphanumeric
// characters. Patch #1037825
//
// Revision 1.15  2004/09/22 13:46:26  rurban
// centralize upload paths.
// major WikiPluginCached feature enhancement:
//   support _STATIC pages in uploads/ instead of dynamic getimg.php? subrequests.
//   mainly for debugging, cache problems and action=pdf
//
// Revision 1.14  2004/06/16 10:38:59  rurban
// Disallow refernces in calls if the declaration is a reference
// ("allow_call_time_pass_reference clean").
//   PhpWiki is now allow_call_time_pass_reference = Off clean,
//   but several external libraries may not.
//   In detail these libs look to be affected (not tested):
//   * Pear_DB odbc
//   * adodb oracle
//
// Revision 1.13  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.12  2004/06/13 11:34:22  rurban
// fixed bug #969532 (space in uploaded filenames)
// improved upload error messages
//
// Revision 1.11  2004/06/11 09:07:30  rurban
// support theme-specific LinkIconAttr: front or after or none
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

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
