<?php // -*-php-*-
rcs_id('$Id: CalendarList.php,v 1.6 2005/04/02 03:05:44 uckelman Exp $');

if (!defined('SECONDS_PER_DAY'))
define('SECONDS_PER_DAY', 24 * 3600);

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
class WikiPlugin_CalendarList
extends WikiPlugin
{
    function getName () {
        return _("CalendarList");
    }

    function getDescription () {
        return _("CalendarList");
    }

    function getDefaultArguments() {
        return array('prefix'       => '[pagename]',
                     'date_format'  => '%Y-%m-%d',
                     'order' 	    => 'normal', // or reverse (counting backwards)
                     'year'         => '',
                     'month'        => '',
                     'month_offset' => 0,
                     //support ranges, based on a simple patch by JoshWand
                     'next_n_days'  => '',
                     'last_n_days'  => '',
                     // next or last n entries:
                     'next_n'  => '',
                     //'last_n'  => '', // not yet

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
    function getWikiPageLinks ($argstr, $basepage) {
        if (isset($this->_links)) 
            return $this->_links;
        else {
            global $request;	
            $this->run($request->_dbi, $argstr, $request, $basepage);
            return $this->_links;
        }
    }

    function __date($dbi, $time) {
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
            $link = HTML::a(array('class' => 'cal-hide',
                                  'href'  => WikiURL($page_for_date,
                                                     array('action' => 'edit')),
                                  'title' => sprintf(_("Edit %s"), $page_for_date)),
                            $date_string);
            $this->_links[] = $page_for_date;
            $a = array(HTML::dt($link), HTML::dd($content));
        } else {
            $a = array();
        }
        return $a;
    }

    function run($dbi, $argstr, &$request, $basepage) {
        $this->args = $this->getArgs($argstr, $request);
        $args       = &$this->args;
        $this->_links = array();

        $now = localtime(time() + 3600 * $request->getPref('timeOffset'), 1);
        foreach ( array('month' => $now['tm_mon'] + 1,
                        'year'  => $now['tm_year'] + 1900)
                  as $param => $dflt ) {

            if (!($args[$param] = intval($args[$param])))
                $args[$param]   = $dflt;
        }
        if ($args['last_n_days']) {
            $time = mktime(0, 0, 0,                            // hh, mm, ss,
                           $args['month'] + $args['month_offset'], // month (1-12)
                           $now['tm_mday'] - $args['last_n_days'],
                           $args['year']);
        } elseif ($args['next_n_days'] or $args['next_n'] 
                  or ($args['order'] == 'reverse')
                 /* or $args['last_n']*/) {
            $time = mktime(0, 0, 0,                            // hh, mm, ss,
                           $args['month'] + $args['month_offset'], // month (1-12)
                           $now['tm_mday'] ,                   // starting today
                           $args['year']);
        } else {
            $time = mktime(12, 0, 0,                           // hh, mm, ss,
                           $args['month'] + $args['month_offset'], // month (1-12)
                           1,                                   // starting at monday
                           $args['year']);
        } 
        $t = localtime($time, 1);

        if ($now['tm_year'] == $t['tm_year'] && $now['tm_mon'] == $t['tm_mon'])
            $this->_today = $now['tm_mday'];
        else
            $this->_today = false;

        $cal = HTML::dl();

        $done = false;
        $n = 0; 
        if ($args['order'] == "reverse")
            $max = $time - (180 * SECONDS_PER_DAY);
        else
            $max = $time + (180 * SECONDS_PER_DAY);
        while (!$done) {
            $success = $cal->pushContent($this->__date($dbi, $time));
            if ($args['order'] == "reverse") {
                $time -= SECONDS_PER_DAY;
                if ($time <= $max) return $cal;
            } else {
                $time += SECONDS_PER_DAY;
                if ($time >= $max) return $cal;
            }

            $t     = localtime($time, 1);
            if ($args['next_n_days']) {
                if ($n == $args['next_n_days']) return $cal;
                $n++;
            } elseif ($args['next_n']) {
                if ($n == $args['next_n']) return $cal;
                if (!empty($success))
                    $n++;
            } elseif ($args['last_n_days']) {
                $done = ($t['tm_mday'] == $now['tm_mday']);
            } else { // stop at next/prev month
                $done = ($t['tm_mon'] != $now['tm_mon']);
            }
        }
        return $cal;
    }
};


// $Log: CalendarList.php,v $
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
// added missing 4th basepage arg at plugin->run() to almost all plugins. This caused no harm so far, because it was silently dropped on normal usage. However on plugin internal ->run invocations it failed. (InterWikiSearch, IncludeSiteMap, ...)
//
// Revision 1.1  2003/11/18 19:06:03  carstenklapp
// New plugin to be used in conjunction with the Calendar plugin.
// Upgraded to use SUBPAGE_SEPARATOR for subpages. SF patch tracker
// submission 565369.
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
