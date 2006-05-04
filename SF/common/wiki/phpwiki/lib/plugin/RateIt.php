<?php // -*-php-*-
rcs_id('$Id: RateIt.php,v 1.19 2004/11/15 16:00:01 rurban Exp $');
/*
 Copyright 2004 $ThePhpWikiProgrammingTeam

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
 * RateIt: A recommender system, based on MovieLens and suggest.
 * Store user ratings per pagename. The wikilens theme displays a navbar image bar
 * with some nice javascript magic and this plugin shows various recommendations.
 *
 * There should be two methods to store ratings:
 * In a SQL database as in wikilens http://dickens.cs.umn.edu/dfrankow/wikilens
 *
 * The most important fact: A page has more than one rating. There can
 * be (and will be!) many ratings per page (ratee): different raters
 * (users), in different dimensions. Are those stored per page
 * (ratee)? Then what if I wish to access the ratings per rater
 * (user)? 
 * wikilens plans several user-centered applications like:
 * a) show my ratings
 * b) show my buddies' ratings
 * c) show how my ratings are like my buddies'
 * d) show where I agree/disagree with my buddy
 * e) show what this group of people agree/disagree on
 *
 * If the ratings are stored in a real DB in a table, we can index the
 * ratings by rater and ratee, and be confident in
 * performance. Currently MovieLens has 80,000 users, 7,000 items,
 * 10,000,000 ratings. This is an average of 1400 ratings/page if each
 * page were rated equally. However, they're not: the most popular
 * things have tens of thousands of ratings (e.g., "Pulp Fiction" has
 * 42,000 ratings). If ratings are stored per page, you would have to
 * save/read huge page metadata every time someone submits a
 * rating. Finally, the movie domain has an unusually small number of
 * items-- I'd expect a lot more in music, for example.
 *
 * For a simple rating system one can also store the rating in the page 
 * metadata (default).
 *
 * Recommender Engines:
 * Recommendation/Prediction is a special field of "Data Mining"
 * For a list of (also free) software see 
 *  http://www.the-data-mine.com/bin/view/Software/WebIndex
 * - movielens: (Java Server) will be gpl'd in summer 2004 (weighted)
 * - suggest: is free for non-commercial use, available as compiled library
 *     (non-weighted)
 * - Autoclass: simple public domain C library
 * - MLC++: C++ library http://www.sgi.com/tech/mlc/
 *
 * Usage:    <?plugin RateIt ?>              to enable rating on this page
 *   Note: The wikilens theme must be enabled, to enable this plugin!
 *   Or use a sidebar based theme with the box method.
 *           <?plugin RateIt show=ratings ?> to show my ratings
 *           <?plugin RateIt show=buddies ?> to show my buddies
 *           <?plugin RateIt show=ratings dimension=1 ?>
 *
 * @author:  Dan Frankowski (wikilens author), Reini Urban (as plugin)
 *
 * TODO: 
 * - fix RATING_STORAGE = WIKIPAGE
 * - fix smart caching
 * - finish mysuggest.c (external engine with data from mysql)
 * - add php_prediction
 */

require_once("lib/WikiPlugin.php");
require_once("lib/wikilens/RatingsDb.php");

class WikiPlugin_RateIt
extends WikiPlugin
{
    function getName() {
        return _("RateIt");
    }
    function getDescription() {
        return _("Rating system. Store user ratings per page");
    }
    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 1.19 $");
    }

    function RatingWidgetJavascript() {
        global $WikiTheme;
        if (!empty($this->imgPrefix))
            $imgPrefix = $this->imgPrefix;
        elseif (defined("RATEIT_IMGPREFIX"))
            $imgPrefix = RATEIT_IMGPREFIX;
        else $imgPrefix = '';
        if ($imgPrefix and !$WikiTheme->_findData("images/RateIt".$imgPrefix."Nk0.png",1))
            $imgPrefix = '';
        $img   = substr($WikiTheme->_findData("images/RateIt".$imgPrefix."Nk0.png"),0,-7);
        $urlprefix = WikiURL("",0,1); // TODO: check actions USE_PATH_INFO=false
        $js = "
function displayRating(imgPrefix, ratingvalue, pred) {
  var cancel = imgPrefix + 'Cancel';
  for (i=1; i<=10; i++) {
    var imgName = imgPrefix + i;
    var imgSrc = '".$img."';   
    document[imgName].title = '"._("Your rating ")."'+ratingvalue;
    var imgType = 'N';
    if (pred) {
        imgType = 'R';
    } else if (i<=(ratingvalue*2)) {
        imgType = 'O';
    }
    document[imgName].src = imgSrc + imgType + ((i%2) ? 'k1' : 'k0') + '.png';
  }
  //document[cancel].src = imgSrc + 'Cancel.png';
}
function click(actionImg, pagename, version, imgPrefix, dimension, rating) {
  if (rating == 'X') {
    deleteRating(actionImg, pagename, dimension);
    displayRating(imgPrefix, 0, 0);
  } else {
    submitRating(actionImg, pagename, version, dimension, rating);
    displayRating(imgPrefix, rating, 0);
  }
}
function submitRating(actionImg, page, version, dimension, rating) {
  var myRand = Math.round(Math.random()*(1000000));
  var imgSrc = '".$urlprefix."' + escape(page) + '?version=' + version + '&action=".urlencode(_("RateIt"))."&mode=add&rating=' + rating + '&dimension=' + dimension + '&nopurge=1&rand=' + myRand"
        .(!empty($_GET['start_debug']) ? "+'&start_debug=1'" : '').";
  ".(DEBUG ? '' : '//')."alert('submitRating(\"'+actionImg+'\", \"'+page+'\", '+version+', '+dimension+', '+rating+') => '+imgSrc);
  document[actionImg].src = imgSrc;
}
function deleteRating(actionImg, page, dimension) {
  var myRand = Math.round(Math.random()*(1000000));
  var imgSrc = '".$urlprefix."' + escape(page) + '?action=".urlencode(_("RateIt"))."&mode=delete&dimension=' + dimension + '&nopurge=1&rand=' + myRand"
        .(!empty($_GET['start_debug']) ? "+'&start_debug=1'" : '').";
  ".(DEBUG ? '' : '//')."alert('deleteRating(\"'+actionImg+'\", \"'+page+'\", '+version+', '+dimension+')');
  document[actionImg].src = imgSrc;
}
";
        return JavaScript($js);
    }

    function actionImgPath() {
        global $WikiTheme;
        return $WikiTheme->_findFile("images/RateItAction.png");
    }

    /**
     * Take a string and quote it sufficiently to be passed as a Javascript
     * string between ''s
     */
    function _javascript_quote_string($s) {
        return str_replace("'", "\'", $s);
    }

    function getDefaultArguments() {
        return array( 'pagename'  => '[pagename]',
                      'version'   => false,
                      'id'        => 'rateit',
                      'imgPrefix' => '',      // '' or BStar or Star
                      'dimension' => false,
                      'small'     => false,
                      'show'      => false,
                      'mode'      => false,
                      );
    }

    function head() { // early side-effects (before body)
        global $WikiTheme;
        $WikiTheme->addMoreHeaders($this->RatingWidgetJavascript());
    }

    // todo: only for signed users
    // todo: set rating dbi for external rating database
    function run($dbi, $argstr, &$request, $basepage) {
        global $WikiTheme;
        //$this->_request = & $request;
        //$this->_dbi = & $dbi;
        $user = $request->getUser();
        //FIXME: fails on test with DumpHtml:RateIt
        if (!is_object($user)) return HTML();
        $this->userid = $user->getId();
        $args = $this->getArgs($argstr, $request);
        $this->dimension = $args['dimension'];
        $this->imgPrefix = $args['imgPrefix'];
        if ($this->dimension == '') {
            $this->dimension = 0;
            $args['dimension'] = 0;
        }
        if ($args['pagename']) {
            // Expand relative page names.
            $page = new WikiPageName($args['pagename'], $basepage);
            $args['pagename'] = $page->name;
        }
        if (empty($args['pagename'])) {
            return $this->error(_("no page specified"));
        }
        $this->pagename = $args['pagename'];

        $rdbi = RatingsDb::getTheRatingsDb();
        $this->_rdbi =& $rdbi;

        if ($args['mode'] === 'add') {
            //if (!$user->isSignedIn()) return $this->error(_("You must sign in"));
            $actionImg = $WikiTheme->_path . $this->actionImgPath();
            $rdbi->addRating($request->getArg('rating'), $this->userid, $this->pagename, $this->dimension);

            if (!empty($request->_is_buffering_output))
                ob_end_clean();  // discard any previous output
            // delete the cache
            $page = $request->getPage();
            //$page->set('_cached_html', false);
            $request->cacheControl('MUST-REVALIDATE');
            $dbi->touch();
            //fake validators without args
            $request->appendValidators(array('wikiname' => WIKI_NAME,
                                             'args'     => wikihash('')));
            header('Content-type: image/png');
            readfile($actionImg);
            exit();
        } elseif ($args['mode'] === 'delete') {
            //if (!$user->isSignedIn()) return $this->error(_("You must sign in"));
            $actionImg = $WikiTheme->_path . $this->actionImgPath();
            $rdbi->deleteRating($this->userid, $this->pagename, $this->dimension);
            if (!empty($request->_is_buffering_output))
                ob_end_clean();  // discard any previous output
            // delete the cache
            $page = $request->getPage();
            //$page->set('_cached_html', false);
            $request->cacheControl('MUST-REVALIDATE');
            $dbi->touch();
            //fake validators without args
            $request->appendValidators(array('wikiname' => WIKI_NAME,
                                             'args'     => hash('')));
            header('Content-type: image/png');
            readfile($actionImg);
            exit();
        } elseif (! $args['show'] ) {
            return $this->RatingWidgetHtml($args['pagename'], $args['version'], $args['imgPrefix'], 
                                           $args['dimension'], $args['small']);
        } else {
            //if (!$user->isSignedIn()) return $this->error(_("You must sign in"));
            //extract($args);
            $rating = $rdbi->getRating();
            $html = HTML::p($this->pagename.": ".
                            sprintf(_("Rated by %d users | Average rating %.1f stars"),
                                    $rdbi->getNumUsers($this->pagename, $this->dimension),
                                    $rdbi->getAvg($this->pagename, $this->dimension)),
                            HTML::br());
            if ($rating) {
                $html->pushContent(sprintf(_("Your rating was %.1f"),
                                           $rating));
            } else {
            	$pred = $rdbi->getPrediction($this->userid, $this->pagename, $this->dimension);
            	if (is_string($pred))
                    $html->pushContent(sprintf(_("%s prediction for you is %s stars"),
                                               WIKI_NAME, $pred));
                elseif ($pred)
                    $html->pushContent(sprintf(_("%s prediction for you is %.1f stars"),
                                               WIKI_NAME, $pred));
            }
            //$html->pushContent(HTML::p());
            //$html->pushContent(HTML::em("(Experimental: This might be entirely bogus data)"));
            return $html;
        }
    }

    // box is used to display a fixed-width, narrow version with common header
    function box($args=false, $request=false, $basepage=false) {
        if (!$request) $request =& $GLOBALS['request'];
        if (!$request->_user->isSignedIn()) return;
        if (!isset($args)) $args = array();
        $args['small'] = 1;
        $argstr = '';
        foreach ($args as $key => $value)
            $argstr .= $key."=".$value;
        $widget = $this->run($request->_dbi, $argstr, $request, $basepage);

        return $this->makeBox(WikiLink(_("RateIt"),'',_("Rate It")),
                              $widget);
    }

    /**
     * HTML widget display
     *
     * This needs to be put in the <body> section of the page.
     *
     * @param pagename    Name of the page to rate
     * @param version     Version of the page to rate (may be "" for current)
     * @param imgPrefix   Prefix of the names of the images that display the rating
     *                    You can have two widgets for the same page displayed at
     *                    once iff the imgPrefix-s are different.
     * @param dimension   Id of the dimension to rate
     * @param small       Makes a smaller ratings widget if non-false
     *
     * Limitations: Currently this can only print the current users ratings.
     *              And only the widget, but no value (for buddies) also.
     */
    function RatingWidgetHtml($pagename, $version, $imgPrefix, $dimension, $small = false) {
        global $WikiTheme, $request;

        $imgId = MangleXmlIdentifier($pagename) . $imgPrefix;
        $actionImgName = $imgId . 'RateItAction';
        $dbi =& $GLOBALS['request']->_dbi;
        $version = $dbi->_backend->get_latest_version($pagename);
       
        //$rdbi =& $this->_rdbi;
        $rdbi = RatingsDb::getTheRatingsDb();
	
        // check if the imgPrefix icons exist.
        if (! $WikiTheme->_findData("images/RateIt".$imgPrefix."Nk0.png", true))
            $imgPrefix = '';
        
        // Protect against 's, though not \r or \n
        $reImgPrefix     = $this->_javascript_quote_string($imgPrefix);
        $reActionImgName = $this->_javascript_quote_string($actionImgName);
        $rePagename      = $this->_javascript_quote_string($pagename);
        //$dimension = $args['pagename'] . "rat";
    
        $html = HTML::span(array("id" => $imgId));
        for ($i=0; $i < 2; $i++) {
            $nk[$i]   = $WikiTheme->_findData("images/RateIt".$imgPrefix."Nk".$i.".png");
            $none[$i] = $WikiTheme->_findData("images/RateIt".$imgPrefix."Rk".$i.".png");
        }

        $user = $request->getUser();
        $userid = $user->getId();
        //if (!isset($args['rating']))
        $rating = $rdbi->getRating($userid, $pagename, $dimension);
        if (!$rating) {
            $pred = $rdbi->getPrediction($userid, $pagename, $dimension);
        }
        for ($i = 1; $i <= 10; $i++) {
            $a1 = HTML::a(array('href' => 'javascript:click(\'' . $reActionImgName . '\',\'' . 
                                $rePagename . '\',\'' . $version . '\',\'' . 
                                $reImgPrefix . '\',\'' . $dimension . '\',' . ($i/2) . ')'));
            $img_attr = array();
            $img_attr['src'] = $nk[$i%2];
            //if (!$rating and !$pred)
              //  $img_attr['src'] = $none[$i%2];
            
            $img_attr['name'] = $imgPrefix . $i;
            $img_attr['alt'] = $img_attr['name'];
            $img_attr['border'] = 0;
            $a1->pushContent(HTML::img($img_attr));
            $a1->addToolTip(_("Rate the topic of this page"));
            $html->pushContent($a1);
            
            //This adds a space between the rating smilies:
            // if (($i%2) == 0) $html->pushContent(' ');
        }
        $html->pushContent(HTML::Raw('&nbsp;'));
       
        $a0 = HTML::a(array('href' => 'javascript:click(\'' . $reActionImgName . '\',\'' . 
                            $rePagename . '\',\'' . $version . '\',\'' . $reImgPrefix . 
                            '\',\'' . $dimension . '\',\'X\')'));

        $msg = _("Cancel rating");
        $a0->pushContent(HTML::img(array('src' => $WikiTheme->getImageUrl("RateIt".$imgPrefix."Cancel"),
                                         'name'=> $imgPrefix.'Cancel',
                                         'alt' => $msg)));
        $a0->addToolTip($msg);
        $html->pushContent($a0);
        /*} elseif ($pred) {
            $msg = _("No opinion");
            $html->pushContent(HTML::img(array('src' => $WikiTheme->getImageUrl("RateItCancelN"),
                                               'name'=> $imgPrefix.'Cancel',
                                               'alt' => $msg)));
            //$a0->addToolTip($msg);
            //$html->pushContent($a0);
        }*/
        $img_attr = array();
        $img_attr['src'] = $WikiTheme->_findData("images/RateItAction.png");
        $img_attr['name'] = $actionImgName;
        $img_attr['alt'] = $img_attr['name'];
        //$img_attr['class'] = 'k' . $i;
        $img_attr['border'] = 0;
        $html->pushContent(HTML::img($img_attr));
        // Display the current rating if there is one
        if ($rating) 
            $html->pushContent(JavaScript('displayRating(\'' . $reImgPrefix . '\','.$rating .',0)'));
        elseif ($pred)
            $html->pushContent(JavaScript('displayRating(\'' . $reImgPrefix . '\','.$pred .',1)'));
        else 
            $html->pushContent(JavaScript('displayRating(\'' . $reImgPrefix . '\',0,0)'));    
        return $html;
    }

};


// $Log: RateIt.php,v $
// Revision 1.19  2004/11/15 16:00:01  rurban
// enable RateIt imgPrefix: '' or 'Star' or 'BStar',
// enable blue prediction icons,
// enable buddy predictions.
//
// Revision 1.18  2004/11/01 10:43:59  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.17  2004/08/05 17:31:52  rurban
// more xhtml conformance fixes
//
// Revision 1.16  2004/08/05 17:23:54  rurban
// add alt tag for xhtml conformance
//
// Revision 1.15  2004/07/09 12:50:50  rurban
// references are declared, not enforced
//
// Revision 1.14  2004/07/08 20:30:07  rurban
// plugin->run consistency: request as reference, added basepage.
// encountered strange bug in AllPages (and the test) which destroys ->_dbi
//
// Revision 1.12  2004/06/30 19:59:07  dfrankow
// Make changes suitable so that wikilens theme (and wikilens.org) work properly.
// + Remove predictions (for now)
// + Use new RatingsDb singleton.
// + Change RatingWidgetHtml() to use parameters like a normal PHP function
//   so we can have PHP check that we're passing the right # of them.
// + Change RatingWidgetHtml() to be callable static-ally
//   (without a plugin object)
// + Remove the "RateIt" button for now, because we don't use it on wikilens.org.
//   Maybe if someone wants the button, there can be an arg or flag for it.
// + Always show the cancel button, because UI widgets should not hide.
// + Remove the "No opinion" button for now, because we don't yet store that.
//   This is a useful thing, tho, for the future.
//
// Revision 1.11  2004/06/19 10:22:41  rurban
// outcomment the pear specific methods to let all pages load
//
// Revision 1.10  2004/06/18 14:42:17  rurban
// added wikilens libs (not yet merged good enough, some work for DanFr)
//
// Revision 1.9  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.8  2004/06/01 15:28:01  rurban
// AdminUser only ADMIN_USER not member of Administrators
// some RateIt improvements by dfrankow
// edit_toolbar buttons
//
// Revision _1.2  2004/04/29 17:55:03  dfrankow
// Check in escape() changes to protect against leading spaces in pagename.
// This is untested with Reini's _("RateIt") additions to this plugin.
//
// Revision 1.7  2004/04/21 04:29:50  rurban
// write WikiURL consistently (not WikiUrl)
//
// Revision 1.6  2004/04/12 14:07:12  rurban
// more docs
//
// Revision 1.5  2004/04/11 10:42:02  rurban
// pgsrc/CreatePagePlugin
//
// Revision 1.4  2004/04/06 20:00:11  rurban
// Cleanup of special PageList column types
// Added support of plugin and theme specific Pagelist Types
// Added support for theme specific UserPreferences
// Added session support for ip-based throttling
//   sql table schema change: ALTER TABLE session ADD sess_ip CHAR(15);
// Enhanced postgres schema
// Added DB_Session_dba support
//
// Revision 1.3  2004/04/01 06:29:51  rurban
// better wording
// RateIt also for ADODB
//
// Revision 1.2  2004/03/31 06:22:22  rurban
// shorter javascript,
// added prediction buttons and display logic,
// empty HTML if not signed in.
// fixed deleting (empty dimension => 0)
//
// Revision 1.1  2004/03/30 02:38:06  rurban
// RateIt support (currently no recommendation engine yet)
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
