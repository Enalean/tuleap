<?php
  /**
   * Copyright (c) STMicroelectronics, 2005. All Rights Reserved.
   *
   * Originally written by Manuel Vacelet, 2005
   *
   * This file is a part of Codendi.
   *
   * Codendi is free software; you can redistribute it and/or modify
   * it under the terms of the GNU General Public License as published by
   * the Free Software Foundation; either version 2 of the License, or
   * (at your option) any later version.
   *
   * Codendi is distributed in the hope that it will be useful,
   * but WITHOUT ANY WARRANTY; without even the implied warranty of
   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   * GNU General Public License for more details.
   *
   * You should have received a copy of the GNU General Public License
   * along with Codendi; if not, write to the Free Software
   * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
   */

require_once 'pre.php';
require_once 'html.php';

// First, check plugin availability
$pluginManager = PluginManager::instance();
$p = $pluginManager->getPluginByName('statistics');
if (! $p || ! $pluginManager->isPluginAvailable($p)) {
    $GLOBALS['Response']->redirect('/');
}

// Grant access only to site admin
if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    $GLOBALS['Response']->redirect('/');
}

// Ajax response needed to fill the list of days per month used in simple search
if ($request->get('method') == 'daylist') {
    echo '<option value="0">All</option>'.PHP_EOL;
    if($request->exist('year') && $request->exist('month') &&  $request->get('month') > 0) {
        $dayspermonth =  date("t", mktime(0, 0, 0, $request->get('month'), 1, $request->get('year')));
        for($i = 1; $i <= $dayspermonth; $i++) {
            echo '<option value="'.$i.'">'.$i.'</option>'.PHP_EOL;
        }
    }
    exit;
}

if (isset($_REQUEST['start']) && isset($_REQUEST['end'])) {
    if (strtotime($_REQUEST['start']) >= strtotime($_REQUEST['end']) || $_REQUEST['start'] == '' || $_REQUEST['end'] == '' ) {
        $GLOBALS['Response']->addFeedback('error', 'You make a mistake in selecting period. Please try again!');
    }
}

$GLOBALS['HTML']->includeCalendarScripts();

$GLOBALS['HTML']->header(array('title'=> 'Frequence stat', 'main_classes' => array('tlp-framed')));

$allData = array('session' => 'Sessions',
                 'user' => 'Users',
                 'forum' => 'Messages in forums',
                 'filedl' => 'Files downloaded',
                 'file' => 'Files released',
                 'groups' => 'Project created',
                 'docdl' => 'Legacy document viewed',
                 'wikidl' => 'Wiki pages viewed',
                 'oartifact'=> 'Opened Artifacts (V3)',
                 'cartifact' => 'Closed (or wished end date) Artifacts (V3)');
EventManager::instance()->processEvent(
    Statistics_Event::FREQUENCE_STAT_ENTRIES,
    array('entries' => &$allData)
);

//value min and max of the year list
$min = 2003;
$max = date("Y");

$allMonths = array(0 => 'All', '01', '02', '03', '04', '05',
                 '06', '07', '08', '09', '10', '11', '12');

$request = HTTPRequest::instance();
$data    = $request->get('data');

if ($data == null) {
    $_REQUEST['data'][0] = 'session';
}

$year = $request->get('year');

if ($year == null) {
    $year = date("Y");
}

$month = $request->get('month');

if ($month == null) {
    $month = 0;
}

$day = $request->get('day');

if ($day == null) {
    $day = 0;
}

$allFilter = array('month' => 'Group by Month',
                   'day' => 'Group by Day',
                   'hour' => 'Group by Hour',
                   'month1' => 'Month',
                   'day1' => 'Day');

$startdate = $request->get('start');

$enddate = $request->get('end');

$filter = $request->get('filter');

$advsrch = $request->get('advsrch');


print '<form method="GET" action="frequence_stat.php" id="id_form" name="date_form">';

print '<select name="data[]">';

foreach ($allData as $key => $val) {
    $selected = '';

    if (is_array($request->get('data')) && in_array($key, $request->get('data'))) {
            $selected = 'selected';
    }
    print '<option value="'.$key.'"  '.$selected.'>'.$val.'</option>';
}
print '</select>';


// advanced search form
if (isset($advsrch) && $advsrch == 1) {

    print '<div id="id_advanced_block">';

    print '<label>Start:</label>';
    print(html_field_date('start', $startdate, false, 10, 10, 'date_form', false));

    print '<label>End:</label>';
    print(html_field_date('end', $enddate, false, 10, 10, 'date_form', false));

    print '<label>Filter:</label>';

    print '<select name="filter" id="id_filter">';

    foreach ($allFilter as $key => $val) {
        $selected = '';
        if ($key == $filter) {
            $selected = 'selected';
        }
        print '<option value="'.$key.'" '.$selected.'>'.$val.'</option>';
    }
    print '</select>';

    print '(or use <a id="id_link_advanced" href="frequence_stat.php?advsrch=0">Simple Search</a>)';

    print '<input type="hidden" name="advsrch" value="1" id="id_advanced">';

    print '</div>';

} else { // simple search form

    print '<div id="id_simple_block">';

    print'<select name="year" id="id_year">';

    foreach (range($min, $max) as $allYears) {
        $selected = '';
        if ($allYears == $year) {
            $selected = 'selected';
        }
        print '<option value="'.$allYears.'" '.$selected.'>'.$allYears.'</option>';
    }
    print '</select>';


    print'<select name="month" id="id_month">';

    foreach ($allMonths as $key => $val) {
        $selected = '';
        if ($key == $month) {
            $selected = 'selected';
        }
        print '<option value="'.$key.'" '.$selected.'>'.$val.'</option>';
    }
    print '</select>';


    print'<select name="day" id="id_day">';

    print '<option value="0">All</option>';

    print '</select>';


    print '(or use <a id="id_link_simple" href="frequence_stat.php?advsrch=1" >Advanced Search</a>)';

    print '<input type="hidden" name="advsrch" value="0" id="id_simple">';

    print '</div>';

}

print '<input type="submit" value="Submit">';

print '</form>';

$datastr = 'session';
if (is_array($request->get('data'))) {
    foreach ($request->get('data') as $k => $v) {
        $datastr = $v;
    }
}

print '<img src="frequence_stat_graph.php?year='.urlencode($year).
                                        '&month='.urlencode($month).
                                        '&day='.urlencode($day).
                                        '&data='.urlencode($datastr).
                                        '&advsrch='.urlencode($advsrch).
                                        '&start='.urlencode($startdate).
                                        '&end='.urlencode($enddate).
                                        '&filter='.urlencode($filter).'" />';
?>


<script type="text/javascript">

function updateDayList() {
    new Ajax.Updater('id_day', 'frequence_stat.php?method=daylist&year='+$F('id_year')+'&month='+$F('id_month'), {method: 'get'});
}

document.observe('dom:loaded', function () {
    Event.observe($('id_month'), 'change', updateDayList);
    Event.observe($('id_year'), 'change', updateDayList);
    updateDayList();
});

</script>


<?php
$HTML->footer(array());
?>
