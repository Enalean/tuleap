/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

/**
 * This script is responsible for the monitoring of changes made by other users
 * on the artifact before submission
 */

var tuleap = tuleap || {};
tuleap.trackers = tuleap.trackers || {};

(function ($) {

    tuleap.trackers.submissionKeeper = {

        init: function() {
            if (isOnArtifactEditionView()) {
                $("form p input[type='submit']").each(function() {
                    $(this).click(checkArtifactIsSubmittable);
                });
            }
        }
    };

    $(document).ready(function () {
        tuleap.trackers.submissionKeeper.init();
    });

    var last_viewed_changeset,
        infos_element      = 'input#artifact_informations';

    function checkArtifactIsSubmittable(event) {
        var new_changesets;

        processSubmitQuery(event);
    }

    function processSubmitQuery(event) {
        var artifact_id       = getArtifactId(),
            last_changeset_id = getLastChangesetId(),
            new_changesets    = getNewChangesets(artifact_id, last_changeset_id);

        if (thereAreNewChangesets(new_changesets) || thereAreNotificationsPending()) {
            event.preventDefault();
            processNewChangesets(new_changesets);
            updateLastViewedChangeset(new_changesets);
        }
    }

    function thereAreNotificationsPending() {
        return $(".artifact-event-popup").length > 0;
    }

    function processNewChangesets(changesets) {
        changesets.each(function (changeset) {
            $('#notification-placeholder').append(changeset['html']);
        });
        $(".artifact-event-popup").fadeIn(500);
        $(".artifact-event-popup button").click(detachPopup);

        disableSubmitButtons();
    }

    function reenableSubmitButtonsIfNeeded() {
        if (! thereAreNotificationsPending()) {
            $("form p input[type='submit']").each(function() {
                $(this).removeAttr('disabled');
            });
        }
    }

    function disableSubmitButtons() {
        $("form p input[type='submit']").each(function() {
            $(this).attr('disabled','disabled');
        });
    }

    function detachPopup() {
        $(this).parents(".artifact-event-popup").detach();
        reenableSubmitButtonsIfNeeded();
    }

    function updateLastViewedChangeset(json) {
        last_viewed_changeset = json.last().id;
    }

    function getNewChangesets(artifact_id, last_changeset_id) {
        var params = {
            func         : 'get-new-changesets',
            aid          : artifact_id,
            changeset_id : last_changeset_id
        };

        var response = $.ajax({
            url: codendi.tracker.base_url + '?' + $.param(params),
            dataType: "json",
            async: false,
            cache: false
        }).responseText;

        return $.parseJSON(response);
    }


    function thereAreNewChangesets(json) {
        return json.length > 0;
    }

    function getArtifactId() {
        return $(infos_element).attr('data-artifact-id');
    }

    function getLastChangesetId() {
        return last_viewed_changeset || $(infos_element).attr('data-changeset-id');
    }

    function isOnArtifactEditionView() {
        return $(infos_element).length > 0;
    }
})(jQuery);
