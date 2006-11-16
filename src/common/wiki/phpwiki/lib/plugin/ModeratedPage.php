<?php // -*-php-*-
rcs_id('$Id: ModeratedPage.php,v 1.4 2005/01/29 19:52:09 rurban Exp $');
/*
 Copyright 2004,2005 $ThePhpWikiProgrammingTeam
 
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
 * This plugin requires an action page (default: ModeratedPage)
 * and provides delayed execution of restricted actions, 
 * after a special moderators request. Usually by email.
 *   http://mywiki/SomeModeratedPage?action=ModeratedPage&id=kdclcr78431zr43uhrn&pass=approve
 *
 * Not yet ready! part 3/3 is missing: The moderator approve/reject methods.
 *
 * See http://phpwiki.org/PageModeration
 * Author: ReiniUrban
 */

require_once("lib/WikiPlugin.php");

class WikiPlugin_ModeratedPage
extends WikiPlugin
{
    function getName () {
        return _("ModeratedPage");
    }
    function getDescription () {
        return _("Support moderated pages");
    }
    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 1.4 $");
    }
    function getDefaultArguments() {
        return array('page'          => '[pagename]',
                     'moderators'    => false,
                     'require_level' => false,   // 1=bogo
                     'require_access' => 'edit,remove,change',
                     'id'   => '',
                     'pass' => '',
                    );
    }

    function run($dbi, $argstr, &$request, $basepage) {
        $args = $this->getArgs($argstr, $request);

        // handle moderation request from the email
        if (!empty($args['id']) and !empty($args['pass'])) {
            if (!$args['page'])
                return $this->error("No page specified");
	    $page = $dbi->getPage($args['page']);
            if ($moderated = $page->get("moderated")) {
                if (array_key_exists($args['id'], $moderated['data'])) {
                    $moderation = $moderated['data'][$args['id']];
                    // handle defaults:
                    //   approve or reject
                    if ($args['pass'] == 'approve')
                        return $this->approve($request, $args, $moderation);
                    elseif ($args['pass'] == 'reject')
                        return $this->reject($request, $args, $moderation);
                    else
                        return $this->error("Wrong pass ".$args['pass']);
                } else {
                    return $this->error("Wrong id ".htmlentities($args['id']));
                }
            }
        }
        return '';
    }

    /**
     * resolve moderators and require_access (not yet) from actionpage plugin argstr
     */
    function resolve_argstr(&$request, $argstr) {
        $args = $this->getArgs($argstr);
        $group = $request->getGroup();
        if (empty($args['moderators'])) {
            $admins = $group->getSpecialMembersOf(GROUP_ADMIN);
            // email or usernames?
            $args['moderators'] = array_merge($admins, array(ADMIN_USER));
        } else { 
            // resolve possible group names
            $moderators = explode(',', $args['moderators']); 
            for ($i=0; $i < count($moderators); $i++) {
                $members = $group->getMembersOf($moderators[$i]);
                if (!empty($members)) {
                    array_splice($moderators, $i, 1, $members);
                }
            }
            if (!$moderators) $moderators = array(ADMIN_USER);
            $args['moderators'] = $moderators;
        }
        //resolve email for $args['moderators']
        $page = $request->getPage();
        $users = array();
        foreach ($args['moderators'] as $userid) {
            $users[$userid] = 0;
        }
        list($args['emails'], $args['moderators']) = 
            $page->getPageChangeEmails(array($page->getName() => $users));

        if (!empty($args['require_access'])) {
            $args['require_access'] = preg_split("/\s*,\s*/", $args['require_access']);
            if (empty($args['require_access']))
                unset($args['require_access']);
        }
        if ($args['require_level'] !== false) {
            $args['require_level'] = (integer) $args['require_level'];
        }
        unset($args['id']);
        unset($args['page']);
        unset($args['pass']);
        return $args;
    }
    
    /**
     * Handle client-side moderation change request.
     * Hook called on the lock action, if moderation metadata already exists.
     */
    function lock_check(&$request, &$page, $moderated) {
        $action_page = $request->getPage(_("ModeratedPage"));
        $status = $this->getSiteStatus($request, $action_page);
        if (is_array($status)) {
            if (!empty($status['emails'])) {
                trigger_error(_("ModeratedPage: No emails for the moderators defined"), 
                              E_USER_WARNING);
                return false;
            }
            $page->set('moderation', array('_status' => $status));
            return $this->notice(
                       fmt("ModeratedPage status update:\n  Moderators: '%s'\n  require_access: '%s'", 
                       join(',', $status['moderators']), $status['require_access']));
        } else {
            $page->set('moderation', false);
            return $this->notice(HTML($status,
                        fmt("'%s' is no ModeratedPage anymore.", $page->getName()))); 
        }
    }

    /**
     * Handle client-side moderation change request by the user.
     * Hook called on the lock action, if moderation metadata should be added.
     * Need to store the the plugin args (who, when) in the page meta-data
     */
    function lock_add(&$request, &$page, &$action_page) {
        $status = $this->getSiteStatus($request, $action_page);
        if (is_array($status)) {
            if (!empty($status['emails'])) {
                trigger_error(_("ModeratedPage: No emails for the moderators defined"), 
                              E_USER_WARNING);
                return false;
            }
            $page->set('moderation', array('_status' => $status));
            return $this->notice(
                       fmt("ModeratedPage status update: '%s' is now a ModeratedPage.\n  Moderators: '%s'\n  require_access: '%s'", 
                       $page->getName(), join(',', $status['moderators']), $status['require_access']));
        }
        else { // error
            return $status;
        }
    }
    
    function notice($msg) {
    	return HTML::div(array('class' => 'wiki-edithelp'), $msg);
    }

    function generateId() {
        better_srand();
        $s = "";
        for ($i = 1; $i <= 16; $i++) {
            $r = function_exists('mt_rand') ? mt_rand(55, 90) : rand(55, 90);
            $s .= chr(($r < 65) ? ($r-17) : $r);
        }
        return $s;
    }

    /** 
     * Handle client-side POST moderation request on any moderated page.
     *   if ($page->get('moderation')) WikiPlugin_ModeratedPage::handler(...);
     * return false if not handled (pass through), true if handled and displayed.
     */
    function handler(&$request, &$page) {
    	$action = $request->getArg('action');
    	$moderated = $page->get('moderated');
    	// cached version, need re-lock of each page to update moderators
    	if (!empty($moderated['_status'])) 
    	    $status = $moderated['_status'];
    	else {
            $action_page = $request->getPage(_("ModeratedPage"));
            $status = $this->getSiteStatus($request, $action_page);
            $moderated['_status'] = $status;
    	}
        if (!empty($status['emails'])) {
            trigger_error(_("ModeratedPage: No emails for the moderators defined"), E_USER_WARNING);
            return true;
        }
        // which action?
        if (!empty($status['require_access']) 
            and !in_array(action2access($action), $status['require_access']))
            return false; // allow and fall through, not moderated
        if (!empty($status['require_level']) and $request->_user->_level >= $status['require_level'])
            return false; // allow and fall through, not moderated
        // else all post actions are moderated by default
    	if (1) /* or in_array($action, array('edit','remove','rename')*/ {
    	    //$moderated = $page->get('moderated');
    	    $id = $this->generateId();
    	    while (!empty($moderated[$id])) $id = $this->generateId(); // avoid duplicates
    	    $moderated['id'] = $id; 		// overwrite current id
    	    $moderated['data'][$id] = array( 	// add current request
    	                                    'timestamp' => time(),
    	    	          		    'userid' => $request->_user->getId(),
                                            'args' => $request->getArgs(),
                                            'user'   => serialize($request->_user),
                                            );
            $this->_tokens['CONTENT'] = HTML::div(array('class' => 'wikitext'),
            					  fmt("%s: action forwarded to moderator %s", 
                                                      $action, 
                                                      join(", ", $status['moderators'])
                                                      ));
	    // send email
            $pagename = $page->getName();
            $subject = "[".WIKI_NAME.'] '.$action.': '._("ModeratedPage").' '.$pagename;
            if (mail(join(",", $status['emails']), 
                     $subject, 
                     $action.': '._("ModeratedPage").' '.$pagename."\n"
                     . serialize($moderated['data'][$id])
                     ."\n<".WikiURL($pagename, array('action' => _("ModeratedPage"), 
                                                     'id' => $id, 'pass' => 'approve'), 1).">"
                     ."\n<".WikiURL($pagename, array('action' => _("ModeratedPage"), 
                                                     'id' => $id, 'pass' => 'reject'), 1).">\n"
                     )) {
                $page->set('moderated', $moderated);
                return false; // pass thru
            } else {
            	//FIXME: This msg get lost on the edit redirect
                trigger_error(_("ModeratedPage Notification Error: Couldn't send email"), 
                              E_USER_WARNING);
                return true;
            }
    	}
        return false;
    }

    /** 
     * Handle admin-side moderation resolve.
     * We might have to convert the GET to a POST request to continue 
     * with the left-over stored request.
     * Better we display a post form for verification.
     */
    function approve(&$request, $args, &$moderation) {
        // check id, convert to POST, continue
        if ($request->isPost()) {
            $this->error("ModeratedPage::approve not yet implemented");
        } else {
            return $this->_approval_form($request, $args, $moderation, 'approve');
        }
    }
    /** 
     * Handle admin-side moderation resolve.
     */
    function reject(&$request, $args, &$moderation) {
        // check id, delete action
        if ($request->isPost()) {
            $this->error("ModeratedPage::reject not yet implemented");
        } else {
            return $this->_approval_form($request, $args, $moderation, 'reject');
        }
    }

    function _approval_form(&$request, $args, $moderation, $pass='approve') {
        $header = HTML::h3(_("Please approve or reject this request:"));
        $loader = new WikiPluginLoader();
        $BackendInfo = $loader->getPlugin("_BackendInfo");
        $content = HTML::table(array('border' => 1,
                                     'cellpadding' => 2,
                                     'cellspacing' => 0));
	$myargs  = $args;
        $BackendInfo->_fixupData($myargs);
        $content->pushContent($BackendInfo->_showhash("request args", $myargs));
        $BackendInfo->_fixupData($moderation);
        $content->pushContent($BackendInfo->_showhash("moderation data", $moderation));
        $approve = Button('submit:ModeratedPage[approve]', _("Approve"), 
                          $pass == 'approve' ? 'wikiadmin' : 'button');
        $reject  = Button('submit:ModeratedPage[reject]', _("Reject"),
                          $pass == 'reject' ? 'wikiadmin' : 'button');
        return HTML::form(array('action' => $request->getPostURL(),
                                'method' => 'post'),
                          $header,
                          $content,
                          ENABLE_PAGEPERM ? ''
                          : HiddenInputs(array('require_authority_for_post' => WIKIAUTH_ADMIN)),
                          HiddenInputs($args),
                          $pass == 'approve' ? HTML::p($approve, $reject) 
                          		     : HTML::p($reject, $approve));
    }
    
    /**
     * Get the side-wide ModeratedPage status, reading the action-page args.
     * Who are the moderators? What actions should be moderated?
     */
    function getSiteStatus(&$request, &$action_page) {
        $loader = new WikiPluginLoader();
        $rev = $action_page->getCurrentRevision();
        $content = $rev->getPackedContent();
        list($pi) = explode("\n", $content, 2); // plugin ModeratedPage must be first line!
        if ($parsed = $loader->parsePI($pi)) {
            $plugin =& $parsed[1];
            if ($plugin->getName() != _("ModeratedPage"))
                return $this->error(sprintf(_("<?plugin ModeratedPage ... ?> not found in first line of %s"),
                                            $action_page->getName()));
            if (!$action_page->get('locked'))
                return $this->error(sprintf(_("%s is not locked!"),
                                            $action_page->getName()));
            return $plugin->resolve_argstr($request, $parsed[2]);
        } else {
            return $this->error(sprintf(_("<?plugin ModeratedPage ... ?> not found in first line of %s"),
                                        $action_page->getName()));
        }
    }
    
};

// $Log: ModeratedPage.php,v $
// Revision 1.4  2005/01/29 19:52:09  rurban
// more work on the last part
//
// Revision 1.3  2004/12/06 19:50:05  rurban
// enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
// renamed delete_page to purge_page.
// enable action=edit&version=-1 to force creation of a new version.
// added BABYCART_PATH config
// fixed magiqc in adodb.inc.php
// and some more docs
//
// Revision 1.2  2004/11/30 17:46:49  rurban
// added ModeratedPage POST action hook (part 2/3)
//
// Revision 1.1  2004/11/19 19:22:35  rurban
// ModeratePage part1: change status
//

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>