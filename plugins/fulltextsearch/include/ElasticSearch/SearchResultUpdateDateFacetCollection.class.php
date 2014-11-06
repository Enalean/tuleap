<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class ElasticSearch_SearchResultUpdateDateFacetCollection {

    const IDENTIFIER = 'update_date';

    const ANY_TIME   = '';
    const PAST_DAY   = 'now-1d';
    const PAST_WEEK  = 'now-1w';
    const PAST_MONTH = 'now-1M';
    const PAST_YEAR  = 'now-1y';

    public $values;

    public function __construct(array $submitted_facets) {
        $this->values = array(
            new ElasticSearch_SearchResultUpdateDateFacet($GLOBALS['Language']->getText('plugin_fulltextsearch', 'facet_update_date_any_time'), self::ANY_TIME),
            new ElasticSearch_SearchResultUpdateDateFacet($GLOBALS['Language']->getText('plugin_fulltextsearch', 'facet_update_date_past_24'), self::PAST_DAY),
            new ElasticSearch_SearchResultUpdateDateFacet($GLOBALS['Language']->getText('plugin_fulltextsearch', 'facet_update_date_past_week'), self::PAST_WEEK),
            new ElasticSearch_SearchResultUpdateDateFacet($GLOBALS['Language']->getText('plugin_fulltextsearch', 'facet_update_date_past_month'), self::PAST_MONTH),
            new ElasticSearch_SearchResultUpdateDateFacet($GLOBALS['Language']->getText('plugin_fulltextsearch', 'facet_update_date_past_year'), self::PAST_YEAR)
        );

        $this->initSelectedValue($submitted_facets);
    }

    private function initSelectedValue($submitted_facets) {
        foreach($this->values as $facet) {
            if (isset($submitted_facets[self::IDENTIFIER]) && $submitted_facets[self::IDENTIFIER] === $facet->value) {
                $facet->setSelected(true);
                return;
            }
        }
    }

    public function identifier() {
        return self::IDENTIFIER;
    }

    public function label() {
        return $GLOBALS['Language']->getText('plugin_fulltextsearch', 'facet_update_date_label');
    }

    public function placeholder() {
        return $GLOBALS['Language']->getText('plugin_fulltextsearch', 'facet_update_date_placeholder');
    }

}