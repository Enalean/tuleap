<?php
// -*-php-*-
rcs_id('$Id: CalendarList.php,v 1.9 2006/05/14 17:40:31 rurban Exp $');

/**
 Copyright 1999,2000,2001,2002,2005 $ThePhpWikiProgrammingTeam

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

// if not defined in config.ini
if (!defined('PLUGIN_CALENDARLIST_ORDER')) {
    define('PLUGIN_CALENDARLIST_ORDER', 'normal');
}
if (!defined('PLUGIN_CALENDARLIST_NEXT_N_DAYS')) {
    define('PLUGIN_CALENDARLIST_NEXT_N_DAYS', '');
}
if (!defined('PLUGIN_CALENDARLIST_NEXT_N')) {
    define('PLUGIN_CALENDARLIST_NEXT_N', '');
}
if (!defined('PLUGIN_CALENDARLIST_LAST_N_DAYS')) {
    define('PLUGIN_CALENDARLIST_LAST_N_DAYS', '');
}
if (!defined('PLUGIN_CALENDARLIST_LAST_N')) {
    define('PLUGIN_CALENDARLIST_LAST_N', '');
}

/**
 * This is a list of calendar appointments.
 * Same arguments as Calendar, so no one is confused
 * Uses <dl><dd>DATE<dt>page contents...
 * Derived from Calendar.php by Martin Norbäck <martin@safelogic.se>
 *
 * Insert this plugin into your Calendar page, for example in:
 *     WikiUser/Calendar
 * Add the line: <?plugin CalendarList ?>
 *
 */
class WikiPlugin_CalendarList extends WikiPlugin
{
    public function getName()
    {
        return _("CalendarList");
    }

    public function getDescription()
    {
        return _("CalendarList");
    }

    public function getDefaultArguments()
    {
        return array('prefix'       => '[pagename]',
                     'date_format'  => '%Y-%m-%d',
                     'order'         => PLUGIN_CALENDARLIST_ORDER, // normal or reverse (report sequence)
                     'year'         => '',
                     'month'        => '',
                     'month_offset' => 0,
                     //support ranges: next n days/events
                     'next_n_days'  => PLUGIN_CALENDARLIST_NEXT_N_DAYS,    // one or the other, not both
                     'next_n'        => PLUGIN_CALENDARLIST_NEXT_N,
                     // last n days/entries:
                     'last_n_days'  => PLUGIN_CALENDARLIST_LAST_N_DAYS,    // one or the other, not both
                     'last_n'        => PLUGIN_CALENDARLIST_LAST_N,

                     'month_format' => '%B, %Y',
                     'wday_format'  => '%a',
                     'start_wday'   => '0');
    }

    /**
     * return links (static only as of action=edit)
     *
     * @param string $argstr The plugin argument string.
     * @param string $basepage The pagename the plugin is invoked from.
     * @return array List of pagenames linked to (or false).
     */
    public function getWikiPageLinks($argstr, $basepage)
    {
        if (isset($this->_links)) {
            return $this->_links;
        } else {
            global $request;
            $this->run($request->_dbi, $argstr, $request, $basepage);
            return $this->_links;
        }
    }

    public function _count_events($dbi, $n = 7, $direction = 1)
    {
        //    This is used by the last_n/next_n options to determine the date that
        //    accounts for the number of N events in the past/future.
        //    RETURNS: date of N-th event or the last item found
        $args = &$this->args;                // gather the args array
        $timeTMP = time();                // start with today's date
        $t = $timeTMP;                    // init the control date variable to now

        for ($i = 0; $i <= 180; $i++) {            // loop thru 180 days, past or future
            $date_string = strftime($args['date_format'], $t);
            $page_for_date = $args['prefix'] . SUBPAGE_SEPARATOR . $date_string;
            if ($dbi->isWikiPage($page_for_date)) { // if this date has any comments/events
                $timeTMP = $t;                //  capture the date of this event for return
                if ($n-- <= 0) {
                    break; //  if we reached the limit, return the date
                }
            }
            $t += 24 * 3600 * $direction;        // advance one day back or forward
        }

        // return the date of the N-th or last, most past/future event in the range
        return $timeTMP;
    }

    public function _date($dbi, $time)
    {
        $args = &$this->args;
        $date_string = strftime($args['date_format'], $time);

        $page_for_date = $args['prefix'] . SUBPAGE_SEPARATOR . $date_string;
        $t = localtime($time, 1);

        $td = HTML::td(array('align' => 'center'));

        if ($dbi->isWikiPage($page_for_date)) {
            // Extract the page contents for this date
            $p = $dbi->getPage($page_for_date);
            $r = $p->getCurrentRevision();
            $c = $r->getContent();
            include_once('lib/BlockParser.php');
            $content = TransformText(implode("\n", $c), $r->get('markup'));
            $link = HTML::a(
                array(
                    'class' => 'cal-hide',
                    'href' => WikiURL($page_for_date, array('action' => 'edit')),
                    'title' => sprintf(_("Edit %s"), $page_for_date)
                ),
                $date_string
            );
            $this->_links[] = $page_for_date;
            $a = array(HTML::dt($link), HTML::dd($content));
        } else {
            $a = array();
        }
        return $a;
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $this->args = $this->getArgs($argstr, $request);
        $args       = &$this->args;
        $this->_links = array();

        // default to this month
        $now = localtime(time() + 3600 * $request->getPref('timeOffset'), 1);
        foreach (array('month' => $now['tm_mon'] + 1, 'year'  => $now['tm_year'] + 1900) as $param => $dflt) {
            if (!($args[$param] = intval($args[$param]))) {
                $args[$param] = $dflt;
            }
        }

        // ***************************************************
        // start of Plugin CalendarList display logic
        // determine start date
        if ($args['last_n_days']) {
            // n days ago (should not be affected by month or month_offset)
            $start = mktime(
                0,
                0,
                0,
                $now['tm_mon'] + 1, // month (1-12)
                $now['tm_mday'] - $args['last_n_days'], // days prior
                $now['tm_year'] + 1900
            );
        } elseif ($args['last_n']) {
            // get date for last nth event
            $start = $this->_count_events($dbi, $args['last_n'], -1);
        } else {
            // start of requested month
            $start = mktime(
                0,
                0,
                0,
                $args['month'] + $args['month_offset'], // month (1-12)
                1, // days prior
                $args['year']
            );
        }

        // determine end date
        if ($args['next_n_days']) {
            // n days from now (should not be affected by month or month_offset)
            $end = mktime(
                23,
                59,
                59,
                $now['tm_mon'] + 1, // month (1-12)
                $now['tm_mday'] + $args['next_n_days'], // days prior
                $now['tm_year'] + 1900
            );
        } elseif ($args['last_n']) {
            // get date for next nth event
            $end = $this->_count_events($dbi, $args['next_n'], 1);
        } else {
            // trick to get last day of requested month
            $end = mktime(
                0,
                0,
                -1,
                $args['month'] + 1 + $args['month_offset'],
                1, // days prior
                $args['year']
            );
        }

        // switch values for reverse order
        $step = 24 * 3600;
        if ($args['order'] == 'reverse') {
            $time_tmp = $start;
            $start = $end;
            $end = $time_tmp;
            $step *= -1;
        }

        // style tag on wiki description but not in here
        $cal = HTML::dl();

        // loop through dates and create list
        for ($i = $start; ($step > 0) ? $i < $end : $i > $end; $i += $step) {
            $cal->pushContent($this->_date($dbi, $i));
        }
        //    end of Plugin CalendarList display logic
        // ***************************************************

        return $cal;
    }
}
// $Log: CalendarList.php,v $
// Revision 1.9  2006/05/14 17:40:31  rurban
// Patch #1232730 by banjo
//
// Revision 1.8  2005/10/12 06:18:31  rurban
// dont overdo constants
//
// Revision 1.7  2005/07/21 18:55:55  rurban
// applied mpullen patch (Revised to work on all date range combinations...),
// but still does not work as documented.
//
// Revision 1.6.2  2005/06/24 12:00:00  mpullen
//   Corrected bug in the main WHILE loop to detect proper termination point in time
//   {it was stopping one day too soon in either direction}.
//
// Revision 1.6.1  2005/06/23 12:00:00  mpullen
//   Revised to work on all date range combinations (past and future, by days or count of events)
//   Externalized five control parameter constants to the config.ini file (new section 8 for PLUGINs)
//
// Revision 1.6  2005/04/02 03:05:44  uckelman
// Removed & from vars passed by reference (not needed, causes PHP to complain).
//
// Revision 1.5  2004/12/06 19:15:04  rurban
// save edit-time links as requested in #946679
//
// Revision 1.4  2004/12/06 18:32:39  rurban
// added order=reverse: feature request from #981109
//
// Revision 1.3  2004/09/22 13:36:45  rurban
// Support ranges, based on a simple patch by JoshWand
//   next_n_days, last_n_days, next_n
//   last_n not yet
//
// Revision 1.2  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far,
//    because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed.
//    (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.1  2003/11/18 19:06:03  carstenklapp
// New plugin to be used in conjunction with the Calendar plugin.
// Upgraded to use SUBPAGE_SEPARATOR for subpages. SF patch tracker
// submission 565369.

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
