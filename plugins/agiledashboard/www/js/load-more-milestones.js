/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

var tuleap = tuleap || { };
tuleap.agiledashboard = tuleap.agiledashboard || { };

tuleap.agiledashboard.loadMoreMilestones = function (more_link) {

    var offset_url_parameter_pattern = /offset=(\d+)/,
        list_of_plannings            = more_link.up('.ad_index_plannings');

    function getNumberOfDisplayedMilestones(more_link) {
        // -1 as we should not count the link itself
        return more_link.up('ul').childElements().size() - 1;
    }

    function increaseOffsetForNextCall(more_link, next_offset) {
        more_link.href = more_link.href.sub(offset_url_parameter_pattern, 'offset=' + next_offset);
    }

    function getOffsetFromHref(more_link) {
        return more_link.href.match(offset_url_parameter_pattern)[1];
    }

    function loadMilestones(evt) {
        Event.stop(evt);

        var offset       = getOffsetFromHref(more_link),
            nb_displayed = getNumberOfDisplayedMilestones(more_link);

        new Ajax.Request(
            more_link.href,
            {
                onSuccess: function (transport) {
                    var no_more_milestone,
                        old_nb_displayed = nb_displayed;

                    tuleap.agiledashboard.resetHeightOfShortAccessBox(list_of_plannings);
                    more_link.up('li').insert({ before: transport.responseText });
                    nb_displayed = getNumberOfDisplayedMilestones(more_link);

                    no_more_milestone = (nb_displayed - old_nb_displayed) != offset;


                    if (no_more_milestone) {
                        more_link.remove();
                    } else {
                        increaseOffsetForNextCall(more_link, nb_displayed)
                    }

                    tuleap.agiledashboard.fixHeightOfShortAccessBox(list_of_plannings);;
                }
            }
        );

        return false;
    }

    more_link.observe('click', loadMilestones);
}
