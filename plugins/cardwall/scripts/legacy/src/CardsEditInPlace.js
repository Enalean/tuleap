/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

/* global jQuery:readonly */

/**
 * This script is responsible for the edition of cards directly
 * on the Agile Dashboard.
 */

var tuleap = tuleap || {};
tuleap.cardwall = tuleap.cardwall || {};

(function ($) {
    function getNewCardData(artifact_id, planning_id) {
        var params = {
            id: artifact_id,
            planning_id: planning_id,
            action: "get-card",
        };

        $.ajax({
            url: "/plugins/cardwall/?" + $.param(params),
            dataType: "json",
        });
    }

    function getConcernedPlanningId() {
        return $("div.hidden[data-planning-id]").attr("data-planning-id");
    }

    tuleap.cardwall.isOnAgiledashboard = function () {
        return $("div.hidden[data-planning-id]").length > 0;
    };

    tuleap.cardwall.cardsEditInPlace = {
        init: function () {
            var self = this;
            if (!tuleap.cardwall.isOnAgiledashboard()) {
                return;
            }

            $("div.cardwall_board div.card li > a.edit-card").click(function (event) {
                event.preventDefault();

                var artifact_id = $(this).attr("data-artifact-id");

                tuleap.tracker.artifactModalInPlace.loadEditArtifactModal(
                    artifact_id,
                    self.moveCardCallback(artifact_id)
                );
            });
        },

        validateEdition: function (artifact_id) {
            var planning_id = getConcernedPlanningId();
            getNewCardData(artifact_id, planning_id);
        },

        moveCardCallback: function (artifact_id) {
            var planning_id = getConcernedPlanningId();
            getNewCardData(artifact_id, planning_id);
        },
    };

    $(document).ready(function () {
        tuleap.cardwall.cardsEditInPlace.init();
    });
})(jQuery);
