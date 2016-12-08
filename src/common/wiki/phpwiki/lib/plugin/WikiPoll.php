<?php // -*-php-*-
rcs_id('$Id: WikiPoll.php,v 1.9 2004/06/16 10:38:59 rurban Exp $');
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
 * This plugin provides configurable polls.
 *
 * Usage:
<?plugin WikiPoll require_all=0 require_least=2
           question[1]="Do you like PhpWiki?"
             answer[1][1]="Yes" answer[1][2]="Do not know" answer[1][3]="No"
           question[2]="Do you have PhpWiki installed by your own?"
             answer[2][1]="Yes" answer[2][2]="No"
           question[3]="Did you install any other wiki engine?"
             answer[3][1]="Yes" answer[3][2]="No"
           question[4]="What wiki engine do you like most?"
             answer[4][1]="c2Wiki" answer[4][2]="MoinMoin" answer[4][3]="PhpWiki"
             answer[4][4]="usemod" answer[4][5]="Twiki" answer[4][5]="guiki"
             answer[4][6]="Other"
           question[5]="Which PhpWiki version do you use?"
             answer[5][1]="1.2.x" answer[5][2]="1.3. 1-2" answer[5][3]="1.3.3-4"
             answer[5][4]="1.3.5-8"
?>
 *
 * Administration:
 * <?plugin WikiPoll page=PhpWikiPoll admin=1 ?>
 * and protect this page properly (e.g. PhpWikiPoll/Admin)
 *
 * TODO:
 *     admin page (view and reset statistics)
 *     for now only radio, support checkboxes (multiple selections) also?
 *
 * Author: ReiniUrban
 */

class WikiPlugin_WikiPoll
extends WikiPlugin
{
    var $_args;	
    
    function getName () {
        return _("WikiPoll");
    }

    function getDescription () {
        return _("Enable configurable polls");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 1.9 $");
    }

    function getDefaultArguments() {
        return array('page'        => '[pagename]',
                     'admin'       => false,
                     'require_all' => 1,   // 1 if all questions must be answered
                     'require_least' => 0, // how many at least
                    );
    }

    function getArgs($argstr, $request=false, $defaults = false) {
        if ($defaults === false)
            $defaults = $this->getDefaultArguments();
        //Fixme: on POST argstr is empty
        $args = array();
        list ($argstr_args, $argstr_defaults) = $this->parseArgStr($argstr);
        if (isset($argstr_args["question_1"])) {
          $args['question'] = $this->str2array("question",$argstr_args);
          $args['answer'] = array();
          for ($i = 0; $i <= count($args['question']); $i++) {
              if ($array = $this->str2array(sprintf("%s_%d","answer",$i),$argstr_args))
                  $args['answer'][$i] = $array;
          }
        }
        
        if (!empty($defaults))
          foreach ($defaults as $arg => $default_val) {
            if (isset($argstr_args[$arg]))
                $args[$arg] = $argstr_args[$arg];
            elseif ( $request and ($argval = $request->getArg($arg)) !== false )
                $args[$arg] = $argval;
            elseif (isset($argstr_defaults[$arg]))
                $args[$arg] = (string) $argstr_defaults[$arg];
            else
                $args[$arg] = $default_val;

            if ($request)
                $args[$arg] = $this->expandArg($args[$arg], $request);

            unset($argstr_args[$arg]);
            unset($argstr_defaults[$arg]);
        }

        foreach (array_merge($argstr_args, $argstr_defaults) as $arg => $val) {
            if (!preg_match("/^(answer_|question_)/",$arg))
                trigger_error(sprintf(_("argument '%s' not declared by plugin"),
                                      $arg), E_USER_NOTICE);
        }

        return $args;
    }
    
    function handle_plugin_args_cruft($argstr, $args) {
    	$argstr = str_replace("\n"," ",$argstr);
    	$argstr = str_replace(array("[","]"),array("_",""),$argstr);
    	$this->_args = $this->getArgs($argstr, $GLOBALS['request']);
        return;
    }

    function str2array($var, $obarray=false) {
    	if (!$obarray) $obarray = $GLOBALS;
    	$i = 0; $array = array();
    	$name = sprintf("%s_%d",$var,$i);
    	if (isset($obarray[$name])) $array[$i] = $obarray[$name];
    	do {
          $i++;
          $name = sprintf("%s_%d",$var,$i);
          if (isset($obarray[$name])) $array[$i] = $obarray[$name];
    	} while (isset($obarray[$name]));
    	return $array;
    }
    
    function run($dbi, $argstr, &$request, $basepage) {
    	$request->setArg('nocache','purge');
        $args = $this->getArgs($argstr, $request);
        if (!$args['page'])
            return $this->error("No page specified");
        if (!empty($args['admin']) and $request->_user->isAdmin()) {
            // reset statistics
            return $this->doPollAdmin($dbi, $request, $page);
        }
	extract($this->_args);
	$page = $dbi->getPage($args['page']);
        // check ip and last visit
        $poll = $page->get("poll");
        $ip = $_SERVER['REMOTE_ADDR'];
        $disable_submit = false;
        if (isset($poll['ip'][$ip]) and ((time() - $poll['ip'][$ip]) < 20*60)) {
            //view at least the result or disable the Go button
            $html = HTML(HTML::strong(
                        _("Sorry! You must wait at least 20 minutes until you can vote again!")));
            $html->pushContent($this->doPoll($page, $request, $request->getArg('answer'),true));
            return $html;
        }
            
        $poll['ip'][$ip] = time();
        // purge older ip's
        foreach ($poll['ip'] as $ip => $time) {
            if ((time() - $time) > 21*60)
                unset($poll['ip'][$ip]);
        }
        $html = HTML::form(array('action' => $request->getPostURL(),
                                 'method' => 'POST'));

        if ($request->isPost()) {
            // checkme: check if all answers are answered
            if ($request->getArg('answer') and (
                 ($args['require_all'] and
                  count($request->getArg('answer')) == count($question))
                 or 
                 ($args['require_least'] and
                  count($request->getArg('answer')) >= $args['require_least']))) {
                $page->set("poll", $poll);
                // update statistics and present them the user
                return $this->doPoll($page, $request, $request->getArg('answer'));
            } else {
                $html->pushContent(HTML::p(HTML::strong(_("Not enough questions answered!"))));
            }
        }
       
        $init = isset($question[0]) ? 0 : 1;
        for ($i = $init; $i <= count($question); $i++) {
            if (!isset($question[$i])) break;
            $q = $question[$i]; 
            if (!isset($answer[$i]))
            	trigger_error(fmt("Missing %s for %s","answer"."[$i]","question"."[$i]"),
            	              E_USER_ERROR);
            $a = $answer[$i];
            if (! is_array($a)) {
                // a simple checkbox
                $html->pushContent(HTML::p(HTML::strong($q)));
                $html->pushContent(HTML::div(
                                       HTML::input(array('type' => 'checkbox',
                                                         'name' => "answer[$i]",
                                                         'value' => 1)),
                                       HTML::raw("&nbsp;"), $a));
            } else {
                $row = HTML();
                for ($j=0; $j <= count($a); $j++) {
                    if (isset($a[$j]))
                        $row->pushContent(HTML::div(
                                              HTML::input(array('type' => 'radio',
                                                                'name' => "answer[$i]",
                                                                'value' => $j)),
                                              HTML::raw("&nbsp;"), $a[$j]));
                }
                $html->pushContent(HTML::p(HTML::strong($q)),$row);
            }
        }
        if (!$disable_submit)
            $html->pushContent(HTML::p(
        	HTML::input(array('type' => 'submit',
                                  'name' => "WikiPoll",
                                  'value' => _("Ok"))),
        	HTML::input(array('type' => 'reset',
                                  'name' => "reset",
                                  'value' => _("Reset")))));
        else 
             $html->pushContent(HTML::p(),HTML::strong(
                 _("Sorry! You must wait at least 20 minutes until you can vote again!")));
        return $html;
    }

    function bar($percent) {
        global $WikiTheme;
        return HTML(HTML::img(array('src' => $WikiTheme->getImageUrl('leftbar'),
                                    'alt' => '<')),
                    HTML::img(array('src' => $WikiTheme->getImageUrl('mainbar'),
                                    'alt' => '-',
                                    'width' => sprintf("%02d",$percent),
                                    'height' => 14)),
                    HTML::img(array('src' => $WikiTheme->getImageUrl('rightbar'),
                                    'alt' => '>')));
    }

    function doPoll($page, $request, $answers, $readonly = false) {
    	$question = $this->_args['question'];
    	$answer   = $this->_args['answer'];
        $html = HTML::table(array('cellspacing' => 2));
        $init = isset($question[0]) ? 0 : 1;
        for ($i = $init; $i <= count($question); $i++) {
            if (!isset($question[$i])) break;
            $poll = $page->get('poll');
            @$poll['data']['all'][$i]++;
            $q = $question[$i]; 
            if (!isset($answer[$i]))
            	trigger_error(fmt("Missing %s for %s","answer"."[$i]","question"."[$i]"),
            	              E_USER_ERROR);
            if (!$readonly)
                $page->set('poll',$poll);
            $a = $answer[$i];
            $result = (isset($answers[$i])) ? $answers[$i] : -1;
            if (! is_array($a) ) {
                $checkbox = HTML::input(array('type' => 'checkbox',
                                              'name' => "answer[$i]",
                                              'value' => $a));
                if ($result >= 0)
                    $checkbox->setAttr('checked',1);
	        if (!$readonly)
                    list($percent,$count,$all) = $this->storeResult($page, $i, $result ? 1 : 0);
                else 
                    list($percent,$count,$all) = $this->getResult($page, $i, 1);
                $print = sprintf(_("  %d%% (%d/%d)"), $percent, $count, $all);
                $html->pushContent(HTML::tr(HTML::th(array('colspan' => 4,'align'=>'left'),$q)));
                $html->pushContent(HTML::tr(HTML::td($checkbox),
                                            HTML::td($a),
                       		            HTML::td($this->bar($percent)),
                                            HTML::td($print)));
            } else {
                $html->pushContent(HTML::tr(HTML::th(array('colspan' => 4,'align'=>'left'),$q)));
                $row = HTML();
                if (!$readonly)
                    $this->storeResult($page, $i, $answers[$i]);
                for ($j=0; $j <= count($a); $j++) {
                    if (isset($a[$j])) {
                    	list($percent,$count,$all) = $this->getResult($page,$i,$j);
                        $print = sprintf(_("  %d%% (%d/%d)"), $percent, $count, $all);
                        $radio = HTML::input(array('type' => 'radio',
                                                   'name' => "answer[$i]",
                                                   'value' => $j));
                        if ($result == $j)
                            $radio->setAttr('checked',1);
                        $row->pushContent(HTML::tr(HTML::td($radio),
                        		           HTML::td($a[$j]),
                        		           HTML::td($this->bar($percent)),
                                                   HTML::td($print)));
                    }
                }
                $html->pushContent($row);
            }
        }
        if (!$readonly)
            return HTML(HTML::h3(_("The result of this poll so far:")),$html,HTML::p(_("Thanks for participating!")));
        else  
            return HTML(HTML::h3(_("The result of this poll so far:")),$html);
  
    }
    
    function getResult($page,$i,$j) {
    	$poll = $page->get("poll");
    	@$count = $poll['data']['count'][$i][$j];
    	@$all = $poll['data']['all'][$i];
    	$percent = sprintf("%d", $count * 100.0 / $all);
    	return array($percent,$count,$all);
    }
    
    function storeResult($page, $i, $j) {
    	$poll = $page->get("poll");
    	if (!$poll) {
    	    $poll = array('data' => array('count' => array(),
    	    				  'all'   => array()));
    	}
    	@$poll['data']['count'][$i][$j]++;
    	//@$poll['data']['all'][$i];
    	$page->set("poll",$poll);
  	$percent = sprintf("%d", $poll['data']['count'][$i][$j] * 100.0 / $poll['data']['all'][$i]);
	return array($percent,$poll['data']['count'][$i][$j],$poll['data']['all'][$i]);
    }

};

// $Log: WikiPoll.php,v $
// Revision 1.9  2004/06/16 10:38:59  rurban
// Disallow refernces in calls if the declaration is a reference
// ("allow_call_time_pass_reference clean").
//   PhpWiki is now allow_call_time_pass_reference = Off clean,
//   but several external libraries may not.
//   In detail these libs look to be affected (not tested):
//   * Pear_DB odbc
//   * adodb oracle
//
// Revision 1.8  2004/06/14 11:31:39  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.7  2004/05/01 15:59:29  rurban
// more php-4.0.6 compatibility: superglobals
//
// Revision 1.6  2004/03/01 18:08:53  rurban
// oops, checked in the debug version. revert to IP check on
//
// Revision 1.5  2004/03/01 16:11:13  rurban
// graphical enhancement
//
// Revision 1.4  2004/02/26 01:42:27  rurban
// don't cache this at all
//
// Revision 1.3  2004/02/24 03:54:46  rurban
// lock page, more questions, new require_least arg
//
// Revision 1.2  2004/02/24 03:21:46  rurban
// enabled require_all check in WikiPoll
// better handling of <20 min visiting client: display results so far
//
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