<?php // -*-php-*-
rcs_id('$Id: PageListColumns.php,v 1.10 2005/09/30 18:41:39 uckelman Exp $');

/*
 Copyright 2004 Mike Cassano

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
 * wikilens specific Custom pagelist columns
 *
 * Rationale: Certain themes should be able to extend the predefined list 
 *  of pagelist types. E.g. certain plugins, like MostPopular might use 
 *  info=pagename,hits,rating
 *  which displays the rating column whenever the wikilens theme is active.
 *  Similarly as in certain plugins, like WikiAdminRename or _WikiTranslation
 */

require_once('lib/PageList.php');
require_once("lib/wikilens/RatingsUser.php");
require_once('lib/plugin/RateIt.php');

/**
 * Column representing the number of backlinks to the page.
 * Perhaps this number should be made a 'field' of a page, in
 * which case this column type would not be necessary.
 * See also info=numbacklinks,numpagelinks at plugin/ListPages.php:_PageList_Column_ListPages_count
 * and info=count at plugin/BackLinks.php:PageList_Column_BackLinks_count
 */
class _PageList_Column_numbacklinks extends _PageList_Column_custom
{
    function _getValue ($page_handle, &$revision_handle) {
        $theIter = $page_handle->getBackLinks();
        return $theIter->count();
    }
    
    function _getSortableValue ($page_handle, &$revision_handle) {
        return $this->_getValue($page_handle, $revision_handle);
    }
};

class _PageList_Column_coagreement extends _PageList_Column_custom 
{
    function _PageList_Column_coagreement ($params) {
    	$this->_pagelist =& $params[3];
        $this->_PageList_Column($params[0], $params[1], $params[2]);
        $this->_selectedBuddies = $this->_pagelist->getOption('selectedBuddies');
    }

    function _getValue ($page_handle, &$revision_handle) 
    {
        global $request;

        $pagename = $page_handle->getName();

        $active_user = $request->getUser();
        $active_userId = $active_user->getId();
        $dbi = $request->getDbh();	
        $p = CoAgreement($dbi, $pagename, $this->_selectedBuddies, $active_userId);
        if($p == 1){
            $p = "yes";
        } elseif($p == 0){
            $p = "unsure";
        } elseif($p == -1){
            $p = "no";	
        } else {
            $p = "error";	
        }   
        //FIXME: $WikiTheme->getImageURL()
        return HTML::img(array('src' => "../images/" . $p . ".gif"));
    }
}

class _PageList_Column_minmisery extends _PageList_Column_custom 
{
    function _PageList_Column_minmisery ($params) {
    	$this->_pagelist =& $params[3];
        $this->_PageList_Column($params[0], $params[1], $params[2]);
        $this->_selectedBuddies = $this->_pagelist->getOption('selectedBuddies');
    }

    function _getValue ($page_handle, &$revision_handle) 
    {
        global $request;

        $pagename = $page_handle->getName();

        $active_user = $request->getUser();
        $active_userId = $active_user->getId();
        $dbi = $request->getDbh();	
        $p = MinMisery($dbi, $pagename, $this->_selectedBuddies, $active_userId);
       	$imgFix = floor($p * 2) / 2;
        //FIXME: $WikiTheme->getImageURL()
        return HTML::img(array('src' => "../images/" . $imgFix . ".png"));
    }
}

class _PageList_Column_averagerating extends _PageList_Column_custom
{
    function _PageList_Column_averagerating ($params) 
    {
    	$this->_pagelist =& $params[3];
        $this->_PageList_Column($params[0], $params[1], $params[2]);
        $this->_selectedBuddies = $this->_pagelist->getOption('selectedBuddies');
    }

    function _getValue ($page_handle, &$revision_handle) 
    {
        global $request;

        $pagename = $page_handle->getName();

        $active_user = $request->getUser();
        $active_userId = $active_user->getId();
        $dbi = $request->getDbh();	
        $p = round(AverageRating($dbi, $pagename, $this->_selectedBuddies, $active_userId), 2);
      
        $imgFix = floor($p * 2) / 2;
        $html = HTML();
        //FIXME: $WikiTheme->getImageURL()
        $html->pushContent(HTML::img(array('src' => "../images/" . $imgFix . ".png")));
        $html->pushContent($p);
        return $html;
    }
};
 
/**
 * Show the value of a rating as a digit (or "-" if no value), given the
 * user who is the rater.
 */
class _PageList_Column_ratingvalue extends _PageList_Column {
    var $_user;
    var $_dimension;

    function _PageList_Column_ratingvalue ($params) {
    	$this->_pagelist =& $params[3];
        $this->_user =& $params[4];//$this->_pagelist->getOption('user');
        assert(!empty($this->_user));
        $this->_PageList_Column($params[0], $params[1], $params[2]);
        $this->_dimension   = $this->_pagelist->getOption('dimension');
    }

    function format ($pagelist, $page_handle, &$revision_handle) 
    {
        $rating = $this->_getValue($page_handle, $revision_handle);
        $mean = $this->_user->mean_rating($this->_dimension);
        $td = HTML::td($this->_tdattr);

        $div = HTML::div();
        if($rating != '-' && abs($rating - $mean) >= 0.75)
            $div->setAttr('style', 'color: #' . ($rating > $mean ? '009900' : 'ff3333'));
        $div->pushContent($rating);

        $td->pushContent($div);

        return $td;
    }

    function _getValue ($page_handle, &$revision_handle) 
    {
        $pagename = $page_handle->getName();

        $tu = & $this->_user;
        $rating = $tu->get_rating($pagename, $this->_dimension);

        // a dash (or *something*) arguably looks better than a big blank space
        return ($rating ? $rating : "-");
    }
    
    function hasNoRatings($pages)
    {      
        $total = 0;
        $use = & $this->_user;
        foreach ($pages as $page) {
            if ($use->get_rating($page, $this->_dimension)) {
                $total++;
            }
        }
        if ($total == 0) {
            return true;
        }
        return false; 
          
    }
    
    function _getSortableValue ($page_handle, &$revision_handle) {
        return $this->_getValue($page_handle, $revision_handle);
    }
};

/**
 * Ratings widget for the logged-in user and the given page
 */
class _PageList_Column_ratingwidget extends _PageList_Column_custom 
{
    function _PageList_Column_ratingwidget ($params) {
    	$this->_pagelist =& $params[3];
        $this->_PageList_Column($params[0], $params[1], $params[2]);
        $this->_dimension = $this->_pagelist->getOption('dimension');
    }

    function format ($pagelist, $page_handle, &$revision_handle) {
        $plugin = new WikiPlugin_RateIt();
        $widget = $plugin->RatingWidgetHtml($page_handle->getName(), "", 
                                            "pagerat", $this->_dimension, "small");
        $td = HTML::td($widget);
        $td->setAttr('nowrap', 'nowrap');
        return $td;
    }

    function _getValue ($page_handle, &$revision_handle) 
    {
        global $request;

        $pagename = $page_handle->getName();
        $active_user   = $request->getUser();
        $active_userid = $active_user->_userid;
        
        $tu = & RatingsUserFactory::getUser($active_userid);
        return $tu->get_rating($pagename, $this->_dimension);
    }
    
    function _getSortableValue ($page_handle, &$revision_handle) {
        return $this->_getValue($page_handle, $revision_handle);
    }
};

class _PageList_Column_prediction extends _PageList_Column 
{
    var $_active_ratings_user;
    var $_users;

    function _PageList_Column_prediction ($params) 
    {
        global $request;
        $active_user = $request->getUser();
        // This needs to be a reference so things aren't recomputed for this user
        $this->_active_ratings_user =& RatingsUserFactory::getUser($active_user->getId());
    	
        $this->_pagelist =& $params[3];
        $this->_PageList_Column($params[0], $params[1], $params[2]);
        $this->_dimension = $this->_pagelist->getOption('dimension');;
        $this->_users = $this->_pagelist->getOption('users');
    }

    function format ($pagelist, $page_handle, &$revision_handle) {
        $pred = $this->_getValue($page_handle, $revision_handle);
        $mean = $this->_active_ratings_user->mean_rating($this->_dimension);
        $td = HTML::td($this->_tdattr);

        $div = HTML::div();
        if($pred > 0 && abs($pred - $mean) >= 0.75)
            $div->setAttr('style', 'color: #' . ($pred > $mean ? '009900' : 'ff3333'));
        $div->pushContent($pred);

        $td->pushContent($div);

        return $td;
    }

    function _getValue ($page_handle, &$revision_handle) 
    {
        $pagename = $page_handle->getName();

        $html = HTML();
        $pred = $this->_active_ratings_user->knn_uu_predict($pagename, $this->_users, $this->_dimension);
        return sprintf("%.1f", min(5, max(0, $pred)));
    }
    
    function _getSortableValue ($page_handle, &$revision_handle) {
        return $this->_getValue($page_handle, $revision_handle);
    }
};

class _PageList_Column_top3recs extends _PageList_Column_custom
{
    
    var $_active_ratings_user;
    var $_users;

    function _PageList_Column_top3recs ($params) {
        global $request;
        $active_user = $request->getUser();
        if (is_string($active_user)) {
        	//FIXME: try to find the bug at test.php which sets request->_user and ->_group
        	trigger_error("request->getUser => string: $active_user", E_USER_WARNING);
        	$active_user = new MockUser($active_user,true);
        }
        // No, I don't know exactly why, but this needs to be a reference for
        // the memoization in pearson_similarity and mean_rating to work
        $this->_active_ratings_user = new RatingsUser($active_user->getId());
        $this->_PageList_Column($params[0], $params[1], $params[2]);
        
        if (!empty($params[3])) {
            $this->_pagelist =& $params[3];
            $this->_dimension = $this->_pagelist->getOption('dimension');
            $this->_users = $this->_pagelist->getOption('users');
        }
    }

    function _getValue ($page_handle, &$revision_handle) 
    {
        $ratings = $this->_active_ratings_user->get_ratings();
        $iter = $page_handle->getLinks();
        $recs = array();
        while($current = $iter->next()) {
            //filter out already rated
            if (!$this->_active_ratings_user->get_rating($current->getName(), $this->_dimension)) {
                $recs[$current->getName()] = 
                    $this->_active_ratings_user->knn_uu_predict($current->getName(), 
                                                                $this->_users, $this->_dimension);
            }
        }
        arsort($recs);
        $counter = 0;
        if (count($recs) >= 3){
            $numToShow = 3;
        }
        else {
            // if <3 just show as many as there are
            $numToShow = count($recs);
        }
        $html = HTML();
        while ((list($key, $val) = each($recs)) && $counter < $numToShow) {
            if ($val < 3){ 
                break;
            }
            if ($counter > 0){
                $html->pushContent(" , ");
            }
            $html->pushContent(WikiLink($key));
            
            $counter++;
        }
        if (count($recs) == 0 || $counter == 0){
            $html->pushContent(_("None"));
        }
        
        //return $top3list;
        return $html;
    }
};

// register custom PageList type
global $WikiTheme;
$WikiTheme->addPageListColumn
  (array
   (
    'numbacklinks' 
    => array('_PageList_Column_numbacklinks','custom:numbacklinks', _("# things"), false),
    'rating' 	   
    => array('_PageList_Column_ratingwidget','custom:rating', _("Rate"), false),
    'coagreement'  
    => array('_PageList_Column_coagreement','custom:coagreement', _("Go?"), 'center'),
    'minmisery'    
    => array('_PageList_Column_minmisery','custom:minmisery', _("MinMisery"), 'center'),
    'averagerating' 
    => array('_PageList_Column_averagerating','custom:averagerating', _("Avg. Rating"), 'left'),
    'top3recs'
    => array('_PageList_Column_top3recs','custom:top3recs', _("Top Recommendations"), 'left'),
    ));

// $Log: PageListColumns.php,v $
// Revision 1.10  2005/09/30 18:41:39  uckelman
// Fixed more passes-by-reference.
//
// Revision 1.9  2004/12/26 17:08:36  rurban
// php5 fixes: case-sensitivity, no & new
//
// Revision 1.8  2004/11/06 17:13:17  rurban
// init is easier this way: no ->init(), pass params instead
//
// Revision 1.7  2004/11/01 10:43:59  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.6  2004/07/08 20:30:07  rurban
// plugin->run consistency: request as reference, added basepage.
// encountered strange bug in AllPages (and the test) which destroys ->_dbi
//
// Revision 1.5  2004/07/07 15:01:44  dfrankow
// Allow ratingvalue, ratingwidget, prediction, numbacklinks columns to be sortable
//
// Revision 1.4  2004/06/30 20:12:09  dfrankow
// + Change numbacklinks function to use existing core functions.
//   It's slower, but it'll work.
//
// + Change ratingvalue column to get its specific user as column 5 (index 4).
//
// + ratingwidget column uses WikiPlugin_RateIt's RatingWidgetHtml
//
// Revision 1.3  2004/06/21 17:01:41  rurban
// fix typo and rating method call
//
// Revision 1.2  2004/06/21 16:22:32  rurban
// add DEFAULT_DUMP_DIR and HTML_DUMP_DIR constants, for easier cmdline dumps,
// fixed dumping buttons locally (images/buttons/),
// support pages arg for dumphtml,
// optional directory arg for dumpserial + dumphtml,
// fix a AllPages warning,
// show dump warnings/errors on DEBUG,
// don't warn just ignore on wikilens pagelist columns, if not loaded.
// RateIt pagelist column is called "rating", not "ratingwidget" (Dan?)
//
// Revision 1.1  2004/06/18 14:42:17  rurban
// added wikilens libs (not yet merged good enough, some work for DanFr)
// 

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>