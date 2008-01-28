<?php
// display.php: fetch page or get default content
rcs_id('$Id: display.php,v 1.65 2005/05/05 08:54:40 rurban Exp $');

require_once('lib/Template.php');

/**
 * Extract keywords from Category* links on page. 
 */
function GleanKeywords ($page) {
    if (!defined('KEYWORDS')) return '';
    include_once("lib/TextSearchQuery.php");
    $search = new TextSearchQuery(KEYWORDS, true);
    $KeywordLinkRegexp = $search->asRegexp();
    // iterate over the pagelinks (could be a large number) [15ms on PluginManager]
    // or do a titleSearch and check the categories if they are linked?
    $links = $page->getPageLinks();
    $keywords[] = SplitPagename($page->getName());
    while ($link = $links->next()) {
        if (preg_match($KeywordLinkRegexp, $link->getName(), $m))
            $keywords[] = SplitPagename($m[0]);
    }
    $keywords[] = WIKI_NAME;
    return join(', ', $keywords);
}

/** Make a link back to redirecting page.
 *
 * @param $pagename string  Name of redirecting page.
 * @return XmlContent Link to the redirecting page.
 */
function RedirectorLink($pagename) {
    $url = WikiURL($pagename, array('redirectfrom' => ''));
    return HTML::a(array('class' => 'redirectfrom wiki',
                         'href' => $url),
                   $pagename);
}

    
function actionPage(&$request, $action) {
    global $WikiTheme;

    $pagename = $request->getArg('pagename');
    $version = $request->getArg('version');

    $page = $request->getPage();
    $revision = $page->getCurrentRevision();

    $dbi = $request->getDbh();
    $actionpage = $dbi->getPage($action);
    $actionrev = $actionpage->getCurrentRevision();

    $pagetitle = HTML(fmt("%s: %s", 
                          $actionpage->getName(),
                          $WikiTheme->linkExistingWikiWord($pagename, false, $version)));

    $validators = new HTTP_ValidatorSet(array('pageversion' => $revision->getVersion(),
                                              '%mtime' => $revision->get('mtime')));
                                        
    $request->appendValidators(array('pagerev' => $revision->getVersion(),
                                     '%mtime' => $revision->get('mtime')));
    $request->appendValidators(array('actionpagerev' => $actionrev->getVersion(),
                                     '%mtime' => $actionrev->get('mtime')));

    $transformedContent = $actionrev->getTransformedContent();
    $template = Template('browse', array('CONTENT' => $transformedContent));
/*
    if (!headers_sent()) {
        //FIXME: does not work yet. document.write not supported (signout button)
        // http://www.w3.org/People/mimasa/test/xhtml/media-types/results
        if (ENABLE_XHTML_XML 
            and (!isBrowserIE() and
                 strstr($request->get('HTTP_ACCEPT'),'application/xhtml+xml')))
            header("Content-Type: application/xhtml+xml; charset=" . $GLOBALS['charset']);
        else
            header("Content-Type: text/html; charset=" . $GLOBALS['charset']);
    }
*/    
    GeneratePage($template, $pagetitle, $revision);
    $request->checkValidators();
    flush();
}

function isDocmanAvailable() {
    $plugin_manager =& getPluginManager();
    $p =& $plugin_manager->getPluginByName('docman');
    if($p && $plugin_manager->isPluginAvailable($p)) {
        return true;
    }
    else {
        return false;
    }
}

function isPageReferencedInDocman($pagename, $group_id) {
    $item_dao =& getDocmanItemDao();
    $exist = $item_dao->isWikiPageReferenced($pagename, $group_id);
    if($exist) {
        return true;
    }
    else {
        return false;
    }
}

function getDocmanItemId($pagename, $group_id) {
    $item_dao =& getDocmanItemDao();
    $id = $item_dao->getItemId($pagename, $group_id);
    return $id;
}

function userCanReadDocmanItem($item_id, $group_id) {
    $dPM =& getDocmanPermissionsManager($group_id);
    $user =& getUser();
    return $dPM->userCanRead($user, $item_id);
}

function userCanWriteDocmanItem($item_id, $group_id) {
    $dPM =& getDocmanPermissionsManager($group_id);
    $user =& getUser();
    return $dPM->userCanWrite($user, $item_id);
}

function userCanManageDocmanItem($item_id, $group_id) {
    $dPM =& getDocmanPermissionsManager($group_id);
    $user =& getUser();
    return $dPM->userCanManage($user, $item_id);
}

function userCanAdminDocman($group_id) {
    $dPM =& getDocmanPermissionsManager($group_id);
    $user =& getUser();
    return $dPM->userCanAdmin($user);
}

function &getUser() {
    $uM =& getUserManager();
    $user = $uM->getCurrentUser();
    return $user;
}

function getDocmanItemDao() {
    require_once(dirname(__FILE__).'/../../../../../plugins/docman/include/Docman_ItemDao.class.php');
    $item_dao =& new Docman_ItemDao(CodexDataAccess::instance());
    return $item_dao;
}

function getDocmanItem($item_id, $group_id) {
    require_once(dirname(__FILE__).'/../../../../../plugins/docman/include/Docman_ItemFactory.class.php');
    $dIF =& Docman_ItemFactory::instance($group_id);
    $item =& $dIF->getItemFromDb($item_id);
    return $item;
}

function getDocmanPermissionsManager($group_id) {
    require_once(dirname(__FILE__).'/../../../../../plugins/docman/include/Docman_PermissionsManager.class.php');
    $dPM =& Docman_PermissionsManager::instance($group_id);
    return $dPM;
}

function getPluginManager() {
    require_once('common/plugin/PluginManager.class.php');
    $plugin_manager =& PluginManager::instance();
    return $plugin_manager;
}

function getUserManager() {
    require_once('common/include/UserManager.class.php');
    $uM =& UserManager::instance();
    return $uM;
}

function getNotificationsManager($group_id, $url) {
    // Create feedback object
    $flash = user_get_preference('plugin_docman_flash');
    if ($flash) {
        user_del_preference('plugin_docman_flash');
        $feedback = @unserialize($flash);
    } else {
        $feedback =& $GLOBALS['Response']->_feedback;
    }

    // Instanciate NotificationsManager
    require_once(dirname(__FILE__).'/../../../../../plugins/docman/include/Docman_NotificationsManager.class.php');
    $nM =& new Docman_NotificationsManager($group_id, get_server_url().$url, &$feedback);
    return $nM;
}

function buildDocmanDetailsSections(&$docman_item, $group_id) {
    $default_url = "/plugins/docman/?group_id=". $group_id;
    $theme_path = "/plugins/docman/themes/default";

    // Docman item factory
    require_once(dirname(__FILE__).'/../../../../../plugins/docman/include/Docman_ItemFactory.class.php');
    $item_factory =& Docman_ItemFactory::instance($group_id);

    // Docman View Item Details
    require_once(dirname(__FILE__).'/../../../../../plugins/docman/include/view/Docman_View_ItemDetails.class.php');
    $details = new Docman_View_ItemDetails(&$item, $default_url);
    
    // sections initialisation
    $sections = array();


    // fetch current user perms on the item
    $user_can_read = userCanReadDocmanItem($docman_item->getId(), $group_id);
    $user_can_write = userCanWriteDocmanItem($docman_item->getId(), $group_id);
    $user_can_manage = userCanManageDocmanItem($docman_item->getId(), $group_id);
    $user_can_admin = userCanAdminDocman($group_id);

    // Restrict access to obselete items details to docman admin
    if($docman_item->isObsolete()) {
        if(!userCanAdminDocman()) {
            $user_can_manage = false;
            $user_can_write  = false;
            // Save read value to let user (according to their rights) to see
            // the properties.
            $user_can_read_obsolete = $user_can_read;
            $user_can_read = false;
        }
    }

    // Properties details section
    if($user_can_read || $user_can_read_obsolete) {
        require_once(dirname(__FILE__).'/../../../../../plugins/docman/include/view/Docman_View_ItemDetailsSectionProperties.class.php');
        $props =& new Docman_View_ItemDetailsSectionProperties($docman_item, $default_url, $theme_path, $user_can_write, 1);
        $sections['properties'] = true;
        $details->addSection($props);
    }

    // permissions details section
    if($user_can_manage) {
        $sections['permissions'] = true;
        require_once(dirname(__FILE__).'/../../../../../plugins/docman/include/view/Docman_View_ItemDetailsSectionPermissions.class.php');
        $details->addSection(new Docman_View_ItemDetailsSectionPermissions($docman_item, $default_url, 1));
    }

    // Notification details section
    if($user_can_read) {
        $sections['notifications'] = true;
        require_once(dirname(__FILE__).'/../../../../../plugins/docman/include/view/Docman_View_ItemDetailsSectionNotifications.class.php');
        $details->addSection(new Docman_View_ItemDetailsSectionNotifications($docman_item, $default_url, getNotificationsManager($group_id, $default_url), 1));
    }

    // Approval details section
    /*if($user_can_read) {
        $sections['approval'] = true;
        require_once(dirname(__FILE__).'/../../../../../plugins/docman/include/view/Docman_View_ItemDetailsSectionApproval.class.php');
        $details->addSection(new Docman_View_ItemDetailsSectionApproval($docman_item, $default_url, $theme_path, getNotificationsManager($group_id, $default_url)));
    }*/

    if($user_can_read) {
        // History details section
        require_once(dirname(__FILE__).'/../../../../../plugins/docman/include/Docman_Log.class.php');
        $logger =& new Docman_Log();
        $sections['history'] = true;
        require_once(dirname(__FILE__).'/../../../../../plugins/docman/include/view/Docman_View_ItemDetailsSectionHistory.class.php');
        $details->addSection(new Docman_View_ItemDetailsSectionHistory($docman_item, $default_url, $user_can_manage, $logger));
    }
    // Set Properties section as current section
    $details->setCurrentSection('properties');
    $details->current_section = 'properties';

    $html = '';
    $html = HTML::br();
    if (count($details->sections)) {
        $secs = HTML();
        foreach($details->sections as $section) {
            if ($section->getId() == $details->current_section) {
                $class = 'docman_properties_navlist_current';
            }
            else {
                $class = '';
            }
            $secs->pushContent(HTML::li(HTML::a(array('href' => $default_url .'&amp;action=details&amp;id='. $docman_item->getId() .'&amp;section='. $section->getId(), 'class' => $class), $section->getTitle())));
        }
        // Content
        $content_div = HTML::div(array('class' => 'docman_properties_content'), $details->sections[$details->current_section]->getContent());
        $html->pushContent(HTML::ul(array('class' => 'docman_properties_navlist'), $secs));
        $html->pushContent($content_div);
    }
    return $html;
    
}

function displayPage(&$request, $template=false) {
    global $WikiTheme, $pv;
    $group_id = $request->getArg('group_id');
    $pagename = $request->getArg('pagename');
    $version = $request->getArg('version');
    $page = $request->getPage();
    if ($version) {
        $revision = $page->getRevision($version);
        if (!$revision)
            NoSuchRevision($request, $page, $version);
    }
    else {
        $revision = $page->getCurrentRevision();
    }

    if (isSubPage($pagename)) {
        $pages = explode(SUBPAGE_SEPARATOR, $pagename);
        $last_page = array_pop($pages); // deletes last element from array as side-effect
        $pageheader = HTML::span(HTML::a(array('href' => WikiURL($pages[0]),
                                              'class' => 'pagetitle'
                                              ),
                                        $WikiTheme->maybeSplitWikiWord($pages[0] . SUBPAGE_SEPARATOR)));
        $first_pages = $pages[0] . SUBPAGE_SEPARATOR;
        array_shift($pages);
        foreach ($pages as $p)  {
	    if ($pv != 2){	//Add the Backlink in page title
            $pageheader->pushContent(HTML::a(array('href' => WikiURL($first_pages . $p),
                                                  'class' => 'backlinks'),
                                            $WikiTheme->maybeSplitWikiWord($p . SUBPAGE_SEPARATOR)));
	    }else{	// Remove Backlinks
	    $pageheader->pushContent(HTML::h1($pagename));
	    }
            $first_pages .= $p . SUBPAGE_SEPARATOR;
        }
	if ($pv != 2){
		$backlink = HTML::a(array('href' => WikiURL($pagename,
							    array('action' => _("BackLinks"))),
					  'class' => 'backlinks'),
				    $WikiTheme->maybeSplitWikiWord($last_page));
		$backlink->addTooltip(sprintf(_("BackLinks for %s"), $pagename));
	}else{
		$backlink = HTML::h1($pagename);
	}
	$pageheader->pushContent($backlink);
	
    } else {
	if ($pv != 2){
		$pageheader = HTML::a(array('href' => WikiURL($pagename,
							     array('action' => _("BackLinks"))),
					   'class' => 'backlinks'),
				     $WikiTheme->maybeSplitWikiWord($pagename));
		$pageheader->addTooltip(sprintf(_("BackLinks for %s"), $pagename));
	}else{
		$pageheader = HTML::h1($pagename); //Remove Backlinks
	}
        if ($request->getArg('frame'))
            $pageheader->setAttr('target', '_top');
    }

    if(isDocmanAvailable()) {
        if(isPageReferencedInDocman($pagename, $group_id)){ 

            // Get docman Item Dao
            $dao =& getDocmanItemDao();
            
            $docman_item_id = $dao->getItemId($pagename, $group_id); // TODO treat the case where the page have more than one reference in docman.
            
            // Create the docman item object
            $item =& getDocmanItem($docman_item_id, $group_id);

            // Expand/Collapse icon
            //$on_click_behaviour = "javascript:toggle_md_section(\'md_'.$package_id.'\'); return false;";
            
            // Add item details section legend.
            $legend = HTML::legend('Docman Metadata of ', HTML::strong($pagename));
            

            $details = buildDocmanDetailsSections(&$item, $group_id);

            $docman_md = HTML::br();
            $docman_md->pushContent(HTML::fieldset(array('class' => 'docman_md_frame'),$legend, $details));
            
            // build sections into docman details view
            //$docman_md->pushContent(buildDocmanDetailsSections(&$item, $group_id));

        }
    }

    $pagetitle = SplitPagename($pagename);
    if (($redirect_from = $request->getArg('redirectfrom'))) {
        $redirect_message = HTML::span(array('class' => 'redirectfrom'),
                                       fmt("(Redirected from %s)",
                                           RedirectorLink($redirect_from)));
    // abuse the $redirected template var for some status update notice                                       
    } elseif ($request->getArg('errormsg')) { 
        $redirect_message = $request->getArg('errormsg');
        $request->setArg('errormsg', false);
    }

    $request->appendValidators(array('pagerev' => $revision->getVersion(),
                                     '%mtime' => $revision->get('mtime')));
/*
    // FIXME: This is also in the template...
    if ($request->getArg('action') != 'pdf' and !headers_sent()) {
      // FIXME: enable MathML/SVG/... support
      if (ENABLE_XHTML_XML
             and (!isBrowserIE()
                  and strstr($request->get('HTTP_ACCEPT'),'application/xhtml+xml')))
            header("Content-Type: application/xhtml+xml; charset=" . $GLOBALS['charset']);
        else
            header("Content-Type: text/html; charset=" . $GLOBALS['charset']);
    }
*/
    $page_content = $revision->getTransformedContent();

    // if external searchengine (google) referrer, highlight the searchterm
    // FIXME: move that to the transformer?
    // OR: add the searchhightplugin line to the content?
    if ($result = isExternalReferrer($request)) {
    	if (DEBUG and !empty($result['query'])) {
            //$GLOBALS['SearchHighlightQuery'] = $result['query'];
            /* simply add the SearchHighlight plugin to the top of the page. 
               This just parses the wikitext, and doesn't highlight the markup */
            include_once('lib/WikiPlugin.php');
	    $loader = new WikiPluginLoader;
            $xml = $loader->expandPI('<'.'?plugin SearchHighlight s="'.$result['query'].'"?'.'>', $request, $markup);
            if ($xml and is_array($xml)) {
              foreach (array_reverse($xml) as $line) {
                array_unshift($page_content->_content, $line);
              }
              array_unshift($page_content->_content, 
                            HTML::div(_("You searched for: "), HTML::strong($result['query'])));
            }
            
            if (0) {
            /* Parse the transformed (mixed HTML links + strings) lines?
               This looks like overkill.
             */
            require_once("lib/TextSearchQuery.php");
            $query = new TextSearchQuery($result['query']);
            $hilight_re = $query->getHighlightRegexp();
            //$matches = preg_grep("/$hilight_re/i", $revision->getContent());
            // FIXME!
            for ($i=0; $i < count($page_content->_content); $i++) {
                $found = false;
                $line = $page_content->_content[$i];
            	if (is_string($line)) {
                    while (preg_match("/^(.*?)($hilight_re)/i", $line, $m)) {
                        $found = true;
                        $line = substr($line, strlen($m[0]));
                        $html[] = $m[1];    // prematch
                        $html[] = HTML::strong(array('class' => 'search-term'), $m[2]); // match
                    }
            	}
                if ($found) {
                    $html[] = $line;  // postmatch
                    $page_content->_content[$i] = HTML::span(array('class' => 'search-context'),
                                                             $html);
                }
            }
            }
        }
    }
   
    $toks['CONTENT'] = new Template('browse', $request, $page_content);
    
    $toks['TITLE'] = $pagetitle;   // <title> tag
    $toks['HEADER'] = $pageheader; // h1 with backlink
    $toks['DOCMAN_METADATA'] = $docman_md;
    $toks['revision'] = $revision;
    if (!empty($redirect_message))
        $toks['redirected'] = $redirect_message;
    $toks['ROBOTS_META'] = 'index,follow';
    $toks['PAGE_DESCRIPTION'] = $page_content->getDescription();
    $toks['PAGE_KEYWORDS'] = GleanKeywords($page);
    if (!$template)
        $template = new Template('html', $request);
    
    $template->printExpansion($toks);
    $page->increaseHitCount();

    if ($request->getArg('action') != 'pdf')
        $request->checkValidators();
    flush();
}

// $Log: display.php,v $
// Revision 1.65  2005/05/05 08:54:40  rurban
// fix pagename split for title and header
//
// Revision 1.64  2005/04/23 11:21:55  rurban
// honor theme-specific SplitWikiWord in the HEADER
//
// Revision 1.63  2004/11/30 17:48:38  rurban
// just comments
//
// Revision 1.62  2004/11/30 09:51:35  rurban
// changed KEYWORDS from pageprefix to search term. added installer detection.
//
// Revision 1.61  2004/11/21 11:59:19  rurban
// remove final \n to be ob_cache independent
//
// Revision 1.60  2004/11/19 19:22:03  rurban
// ModeratePage part1: change status
//
// Revision 1.59  2004/11/17 20:03:58  rurban
// Typo: call SearchHighlight not SearchHighLight
//
// Revision 1.58  2004/11/09 17:11:16  rurban
// * revert to the wikidb ref passing. there's no memory abuse there.
// * use new wikidb->_cache->_id_cache[] instead of wikidb->_iwpcache, to effectively
//   store page ids with getPageLinks (GleanDescription) of all existing pages, which
//   are also needed at the rendering for linkExistingWikiWord().
//   pass options to pageiterator.
//   use this cache also for _get_pageid()
//   This saves about 8 SELECT count per page (num all pagelinks).
// * fix passing of all page fields to the pageiterator.
// * fix overlarge session data which got broken with the latest ACCESS_LOG_SQL changes
//
// Revision 1.57  2004/11/01 10:43:57  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.56  2004/10/14 13:44:14  rurban
// fix lib/display.php:159: Warning[2]: Argument to array_reverse() should be an array
//
// Revision 1.55  2004/09/26 14:58:35  rurban
// naive SearchHighLight implementation
//
// Revision 1.54  2004/09/17 14:19:41  rurban
// disable Content-Type header for now, until it is fixed
//
// Revision 1.53  2004/06/25 14:29:20  rurban
// WikiGroup refactoring:
//   global group attached to user, code for not_current user.
//   improved helpers for special groups (avoid double invocations)
// new experimental config option ENABLE_XHTML_XML (fails with IE, and document.write())
// fixed a XHTML validation error on userprefs.tmpl
//
// Revision 1.52  2004/06/14 11:31:37  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.51  2004/05/18 16:23:39  rurban
// rename split_pagename to SplitPagename
//
// Revision 1.50  2004/05/04 22:34:25  rurban
// more pdf support
//
// Revision 1.49  2004/04/18 01:11:52  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
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