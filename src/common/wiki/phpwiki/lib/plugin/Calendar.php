<?php
// -*-php-*-
rcs_id('$Id: Calendar.php,v 1.30 2005/04/02 03:05:44 uckelman Exp $');
/**
 Copyright 1999, 2000, 2001, 2002 $ThePhpWikiProgrammingTeam

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

if (!defined('SECONDS_PER_DAY')) {
    define('SECONDS_PER_DAY', 24 * 3600);
}

// FIXME: Still needs:
//
//   o Better way to navigate to distant months.
//     (Maybe a form with selectors for month and year)?
//
// It would be nice to have some way to get from the individual date
// pages back to the calendar page. (Subpage support might make this
// easier.)

class WikiPlugin_Calendar extends WikiPlugin
{
    public function getName()
    {
        return _("Calendar");
    }

    public function getDescription()
    {
        return _("Calendar");
    }

    public function getVersion()
    {
        return preg_replace("/[Revision: $]/", '', "\$Revision: 1.30 $");
    }

    public function getDefaultArguments()
    {
        return array('prefix'           => '[pagename]' . SUBPAGE_SEPARATOR,
                     'date_format'      => '%Y-%m-%d',
                     'year'             => '',
                     'month'            => '',
                     'month_offset'     => 0,

                     'month_format'     => '%B, %Y',
                     'wday_format'      => '%a',
                     'start_wday'       => '0');
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

    private function header($pagename, $time)
    {
        $args = &$this->args;

        $t = localtime($time - SECONDS_PER_DAY, 1);
        $prev_url = WikiURL($pagename, array('month' => $t['tm_mon'] + 1,
                                             'year'  => $t['tm_year'] + 1900));

        $t = localtime($time + 32 * SECONDS_PER_DAY, 1);
        $next_url = WikiURL($pagename, array('month' => $t['tm_mon'] + 1,
                                             'year'  => $t['tm_year'] + 1900));

        $prev = HTML::a(array('href'  => $prev_url, 'class' => 'cal-arrow', 'title' => _("Previous Month")), '<');
        $next = HTML::a(array('href'  => $next_url, 'class' => 'cal-arrow', 'title' => _("Next Month")), '>');

        $row = HTML::tr(
            HTML::td(array('align' => 'left'), $prev),
            HTML::td(
                array('align' => 'center'),
                HTML::strong(array('class' => 'cal-header'), strftime($args['month_format'], $time))
            ),
            HTML::td(array('align' => 'right'), $next)
        );

        return HTML::tr(
            HTML::td(
                array('colspan' => 7, 'align' => 'center'),
                HTML::table(array('width' => '100%', 'class' => 'cal-header'), $row)
            )
        );
    }


    private function daynames($start_wday)
    {
        $time  = mktime(12, 0, 0, 1, 1, 2001);
        $t     = localtime($time, 1);
        $time += (7 + $start_wday - $t['tm_wday']) * SECONDS_PER_DAY;

        $t = localtime($time, 1);
        assert($t['tm_wday'] == $start_wday);

        $fs = $this->args['wday_format'];
        $row = HTML::tr();
        $row->setattr('class', 'cal-dayname');
        for ($i = 0; $i < 7; $i++) {
            $row->pushContent(HTML::td(array('class' => 'cal-dayname', 'align' => 'center'), strftime($fs, $time)));
            $time += SECONDS_PER_DAY;
        }
        return $row;
    }

    private function date($dbi, $time)
    {
        $args = &$this->args;

        $page_for_date = $args['prefix'] . strftime($args['date_format'], $time);
        $t = localtime($time, 1);

        $td = HTML::td(array('align' => 'center'));

        $mday = $t['tm_mday'];
        if ($mday == $this->_today) {
            $mday = HTML::strong($mday);
            $td->setAttr('class', 'cal-today');
        } elseif ($dbi->isWikiPage($page_for_date)) {
            $this->_links[] = $page_for_date;
            $td->setAttr('class', 'cal-day');
        }

        if ($dbi->isWikiPage($page_for_date)) {
            $this->_links[] = $page_for_date;
            $date = HTML::a(
                array('class' => 'cal-day', 'href' => WikiURL($page_for_date), 'title' => $page_for_date),
                HTML::em($mday)
            );
        } else {
            $date = HTML::a(
                array(
                    'class' => 'cal-hide',
                    'href' => WikiURL($page_for_date, array('action' => 'edit')),
                    'title' => sprintf(_("Edit %s"), $page_for_date)
                ),
                $mday
            );
        }
        $td->pushContent(HTML::raw('&nbsp;'), $date, HTML::raw('&nbsp;'));
        return $td;
    }

    public function run($dbi, $argstr, &$request, $basepage)
    {
        $this->args = $this->getArgs($argstr, $request);
        $args       = &$this->args;
        $this->_links = array();

        $now = localtime(time() + 3600 * $request->getPref('timeOffset'), 1);
        foreach (array('month' => $now['tm_mon'] + 1, 'year'  => $now['tm_year'] + 1900) as $param => $dflt) {
            if (!($args[$param] = intval($args[$param]))) {
                $args[$param] = $dflt;
            }
        }

        $time = mktime(
            12,
            0,
            0,                               // hh, mm, ss,
            $args['month'] + $args['month_offset'], // month (1-12)
            1,                                      // mday (1-31)
            $args['year']
        );

        $cal = HTML::table(
            array('cellspacing' => 0, 'cellpadding' => 2, 'class' => 'cal'),
            HTML::thead(
                $this->header($request->getArg('pagename'), $time),
                $this->daynames($args['start_wday'])
            )
        );

        $t = localtime($time, 1);

        if ($now['tm_year'] == $t['tm_year'] && $now['tm_mon'] == $t['tm_mon']) {
            $this->_today = $now['tm_mday'];
        } else {
            $this->_today = false;
        }

        $tbody = HTML::tbody();
        $row = HTML::tr();

        $col = (7 + $t['tm_wday'] - $args['start_wday']) % 7;
        if ($col > 0) {
            $row->pushContent(HTML::td(array('colspan' => $col)));
        }
        $done = false;

        while (!$done) {
            $row->pushContent($this->date($dbi, $time));

            if (++$col % 7 == 0) {
                $tbody->pushContent($row);
                $col = 0;
                $row = HTML::tr();
            }

            $time += SECONDS_PER_DAY;
            $t     = localtime($time, 1);
            $done  = $t['tm_mday'] == 1;
        }

        if ($row->getContent()) {
            $row->pushContent(HTML::td(array('colspan' => (42 - $col) % 7)));
            $tbody->pushContent($row);
        }
        $cal->pushContent($tbody);
        return $cal;
    }
}
// $Log: Calendar.php,v $
// Revision 1.30  2005/04/02 03:05:44  uckelman
// Removed & from vars passed by reference (not needed, causes PHP to complain).
//
// Revision 1.29  2004/12/06 19:15:04  rurban
// save edit-time links as requested in #946679
//
// Revision 1.28  2004/05/08 14:06:13  rurban
// new support for inlined image attributes: [image.jpg size=50x30 align=right]
// minor stability and portability fixes
//
// Revision 1.27  2004/02/17 12:11:36  rurban
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.26  2003/01/18 21:19:25  carstenklapp
// Code cleanup:
// Reformatting; added copyleft, getVersion, getDescription
// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
