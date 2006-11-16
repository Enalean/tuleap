<?php
rcs_id('$Id: AnalyseAccessLogSql.php,v 1.1 2005/02/12 17:26:24 rurban Exp $');
/*
 Copyright 2005 Charles Corrigan and $ThePhpWikiProgrammingTeam

 This file is (not yet) part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */
/**
 * A plugin that provides a framework and some useful queries to analyse the SQL
 * access log. This information may be sensitive and so is limited to
 * administrator access only.
 *
 * To add a new query, see _getQueryString()
 */
class WikiPlugin_AnalyseAccessLogSql
extends WikiPlugin
{
    /**
     * Build the query string
     *
     * FIXME: some or all of these queries may be MySQL specific / non-portable
     * FIXME: properly quote the string args
     *
     * The column names displayed are generated from the actual query column
     * names, so make sure that each column in the query is given a user
     * friendly name. Note that the column names are passed to _() and so may be
     * translated.
     *
     * If there are query specific where conditions, then the construction
     * "    if ($where_conditions<>'')
     *          $where_conditions = 'WHERE '.$where_conditions.' ';"
     * should be changed to
     * "    if ($where_conditions<>'')
     *          $where_conditions = 'AND '.$where_conditions.' ';"
     * and in the assignment to query have something like
     * "    $query= "SELECT "
     *          ."referer "
     *          ."FROM $accesslog "
     *          ."WHERE referer IS NOT NULL "
     *          .$where_conditions
     */
    function _getQueryString(&$args) {
        // extract any parametrised conditions from the arguments,
        // in particular, how much history to select
        $where_conditions = $this->_getWhereConditions($args);

        // get the correct name for the table
        //FIXME is there a more correct way to do this?
        global $DBParams;
        $accesslog = (!empty($DBParams['prefix']) ? $DBParams['prefix'] : '')."accesslog";

        $query = '';
        if ($args['mode']=='referring_urls') {
            if ($where_conditions<>'')
                $where_conditions = 'WHERE '.$where_conditions.' ';
            $query = "SELECT "
                ."left(referer,length(referer)-instr(reverse(referer),'?')) AS Referring_URL, "
                ."count(*) AS Referral_Count "
                ."FROM $accesslog "
                .$where_conditions
                ."GROUP BY Referring_URL";

        } elseif ($args['mode']=='external_referers') {
            $args['local_referrers'] = 'false';
            $where_conditions = $this->_getWhereConditions($args);
            if ($where_conditions<>'')
                $where_conditions = 'WHERE '.$where_conditions.' ';
            $query = "SELECT "
                ."left(referer,length(referer)-instr(reverse(referer),'?')) AS Referring_URL, "
                ."count(*) AS Referral_Count "
                ."FROM $accesslog "
                .$where_conditions
                ."GROUP BY Referring_URL";

        } elseif ($args['mode']=='referring_domains') {
            if ($where_conditions<>'')
                $where_conditions = 'WHERE '.$where_conditions.' ';
            $query = "SELECT "
                ."left(referer, if(locate('/', referer, 8) > 0,locate('/', referer, 8) -1, length(referer))) AS Referring_Domain, "
                ."count(*) AS Referral_Count "
                ."FROM $accesslog "
                .$where_conditions
                ."GROUP BY Referring_Domain";

        } elseif ($args['mode']=='remote_hosts') {
            if ($where_conditions<>'')
                $where_conditions = 'WHERE '.$where_conditions.' ';
            $query = "SELECT "
                ."remote_host AS Remote_Host, "
                ."count(*) AS Access_Count "
                ."FROM $accesslog "
                .$where_conditions
                ."GROUP BY Remote_Host";

        } elseif ($args['mode']=='users') {
            if ($where_conditions<>'')
                $where_conditions = 'WHERE '.$where_conditions.' ';
            $query = "SELECT "
                ."remote_user AS User, "
                ."count(*) AS Access_Count "
                ."FROM $accesslog "
                .$where_conditions
                ."GROUP BY User";

        } elseif ($args['mode']=='host_users') {
            if ($where_conditions<>'')
                $where_conditions = 'WHERE '.$where_conditions.' ';
            $query = "SELECT "
                ."remote_host AS Remote_Host, "
                ."remote_user AS User, "
                ."count(*) AS Access_Count "
                ."FROM $accesslog "
                .$where_conditions
                ."GROUP BY Remote_Host, User";

        } elseif ($args['mode']=="search_bots") {
            // This queries for all entries in the SQL access log table that
            // have a dns name that I know to be a web search engine crawler and
            // categorises the results into time buckets as per the list below
            // 0 - 1 minute - 60
            // 1 - 1 hour   - 3600     = 60 * 60
            // 2 - 1 day    - 86400    = 60 * 60 * 24
            // 3 - 1 week   - 604800   = 60 * 60 * 24 * 7
            // 4 - 1 month  - 2629800  = 60 * 60 * 24 * 365.25 / 12
            // 5 - 1 year   - 31557600 = 60 * 60 * 24 * 365.25
            
            $now = time();
            $query = "SELECT "
                ."IF($now-time_stamp<60, '"._("0 - last minute")."',"
                  ."IF($now-time_stamp<3600, '"._("1 - 1 minute to 1 hour")."',"
                    ."IF($now-time_stamp<86400, '"._("2 - 1 hour to 1 day")."',"
                      ."IF($now-time_stamp<604800, '"._("3 - 1 day to 1 week")."',"
                        ."IF($now-time_stamp<2629800, '"._("4 - 1 week to 1 month")."',"
                          ."IF($now-time_stamp<31557600, '"._("5 - 1 month to 1 year")."'," 
                            ."'"._("6 - more than 1 year")."')))))) AS 'Time_Scale', "
                ."remote_host AS 'Remote_Host', "
                ."count(*) AS 'Access_Count' "
                ."FROM $accesslog "
                ."WHERE (remote_host LIKE '%googlebot.com' "
                ."OR remote_host LIKE '%alexa.com' "
                ."OR remote_host LIKE '%inktomisearch.com' "
                ."OR remote_host LIKE '%msnbot.msn.com') "
                .($where_conditions ? 'AND '.$where_conditions : '')
                ."GROUP BY 'Time_Scale', 'Remote_Host'";
            
        } elseif ($args['mode']=="search_bots_hits") {
            // This queries for all entries in the SQL access log table that
            // have a dns name that I know to be a web search engine crawler and
            // displays the URI that was hit.
            // If PHPSESSID appears in the URI, just display the URI to the left of this
            
            $now = time();
            $query = "SELECT "
                ."IF($now-time_stamp<60, '"._("0 - last minute")."',"
                  ."IF($now-time_stamp<3600, '"._("1 - 1 minute to 1 hour")."',"
                    ."IF($now-time_stamp<86400, '"._("2 - 1 hour to 1 day")."',"
                      ."IF($now-time_stamp<604800, '"._("3 - 1 day to 1 week")."',"
                        ."IF($now-time_stamp<2629800, '"._("4 - 1 week to 1 month")."',"
                          ."IF($now-time_stamp<31557600, '"._("5 - 1 month to 1 year")."'," 
                            ."'"._("6 - more than 1 year")."')))))) AS 'Time_Scale', "
                ."remote_host AS 'Remote_Host', "
                ."IF(instr(request_uri, 'PHPSESS')=0, request_uri,left(request_uri, instr(request_uri, 'PHPSESS')-2)) AS 'Request_URI' "
                ."FROM $accesslog "
                ."WHERE (remote_host LIKE '%googlebot.com' "
                ."OR remote_host LIKE '%alexa.com' "
                ."OR remote_host LIKE '%inktomisearch.com' "
                ."OR remote_host LIKE '%msnbot.msn.com') "
                .($where_conditions ? 'AND '.$where_conditions : '')
                ."ORDER BY time_stamp";
        }
        return $query;
    }

    /** Honeypot for xgettext. Those strings are translated dynamically.
     */
    function _locale_dummy() {
        $dummy = array(
                       // mode caption
                       _("referring_urls"),
                       _("external_referers"),
                       _("referring_domains"),
                       _("remote_hosts"),
                       _("users"),
                       _("host_users"),
                       _("search_bots"),
                       _("search_bots_hits"),
                       // period header
                       _("minutes"),
                       _("hours"),
                       _("days"),
                       _("weeks"),
                       );
    }

    function getDefaultArguments() {
        return array(
                     'mode'             => 'referring_domains',
                     // referring_domains, referring_urls, remote_hosts, users, host_users
                     'caption'          => '', 
                     // blank means use the mode as the caption/title for the output
                     'local_referrers'  => 'true',  // only show external referring sites
                     'period'           => '',      // the type of period to report:
                     // may be weeks, days, hours, minutes, or blank for all
                     'count'            => '0'      // the number of periods to report
                     );
    }

    function getName () {
        return _("AnalyseAccessLogSql");
    }

    function getDescription () {
        return _("Show summary information from the access log table.");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 1.1 $");
    }

    function run($dbi, $argstr, &$request, $basepage) {
        // flag that the output may not be cached - i.e. it is dynamic
        $request->setArg('nocache', 1);

        if (!$request->_user->isAdmin())
            return HTML::p(_("The requested information is available only to Administrators."));

        if (!ACCESS_LOG_SQL) // need read access
            return HTML::p(_("The SQL_ACCESS_LOG is not enabled."));

        // set aside a place for the table headers, see _setHeaders()
        $this->_theadrow = HTML::tr();
        $this->_headerSet = false;

        $args = $this->getArgs($argstr, $request);

        $query = $this->_getQueryString($args);

        if ($query=='')
            return HTML::p(sprintf( _("Unrecognised parameter 'mode=%s'"),
                                    $args['mode']));

        // get the data back.
        // Note that this must be done before the final generation ofthe table,
        // otherwise the headers will not be ready
        $tbody = $this->_getQueryResults($query, $dbi);

        return HTML::table(array('border'        => 1,
                                 'cellspacing'   => 1,
                                 'cellpadding'   => 1),
                    HTML::caption(HTML::h1(HTML::br(),$this->_getCaption($args))),
                    HTML::thead($this->_theadrow),
                    $tbody);
    }

    function _getQueryResults($query, &$dbi) {
        $queryResult = $dbi->genericSqlIter($query);
        if (!$queryResult) {
            $tbody = HTML::tbody(HTML::tr(HTML::td(_("<empty>"))));
        } else {
            $tbody = HTML::tbody();
            while ($row = $queryResult->next()) {
                $this->_setHeaders($row);
                $tr = HTML::tr();
                foreach ($row as $value) {
                    // output a '-' for empty values, otherwise the table looks strange
                    $tr->pushContent(HTML::td( empty($value) ? '-' : $value ));
                }
                $tbody->pushContent($tr);
            }
        }
        $queryResult->free();
        return $tbody;
    }

    function _setHeaders($row) {
        if (!$this->_headerSet) {
            foreach ($row as $key => $value) {
                $this->_theadrow->pushContent(HTML::th(_($key)));
            }
            $this->_headerSet = true;
        }
    }

    function _getWhereConditions(&$args) {
        $where_conditions = '';

        if ($args['period']<>'') {
            $since = 0;
            if ($args['period']=='minutes') {
                $since = 60;
            } elseif ($args['period']=='hours') {
                $since = 60 * 60;
            } elseif ($args['period']=='days') {
                $since = 60 * 60 * 24;
            } elseif ($args['period']=='weeks') {
                $since = 60 * 60 * 24 * 7;
            }
            $since = $since * $args['count'];
            if ($since>0) {
                if ($where_conditions<>'')
                    $where_conditions = $where_conditions.' AND ';
                $since = time() - $since;
                $where_conditions = $where_conditions."time_stamp > $since";
            }
        }

        if ($args['local_referrers']<>'true') {
            if ($where_conditions<>'')
                $where_conditions = $where_conditions.' AND ';
            $localhost = SERVER_URL;
            $len = strlen($localhost);
            $where_conditions = $where_conditions."left(referer,$len)<>'$localhost'";
        }

        // The assumed contract is that there is a space at the end of the
        // conditions string, so that following SQL clauses (such as GROUP BY) 
        // will not cause a syntax error
        if ($where_conditions<>'')
            $where_conditions = $where_conditions.' ';

        return $where_conditions;
    }

    function _getCaption(&$args) {
        $caption = $args['caption'];
        if ($caption=='')
            $caption = gettext($args['mode']);
        if ($args['period']<>'' && $args['count'])
            $caption = $caption." - ".$args['count']." ". gettext($args['period']);
        return $caption;
    }

}

// $Log: AnalyseAccessLogSql.php,v $
// Revision 1.1  2005/02/12 17:26:24  rurban
// renamed to Sql. Added translations (inc not yet)
//
// Revision 1.1  2005/02/02 20:41:02  rurban
// Accesslog sql queries
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