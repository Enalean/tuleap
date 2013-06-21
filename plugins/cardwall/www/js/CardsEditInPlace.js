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

/**
 * This script is responsible for the edition of cards directly
 * on the Agile Dashboard.
 */

var tuleap = tuleap || {};
tuleap.cardwall = tuleap.cardwall || { };

(function ($) {
    var overlay_window;

    function displayOverlay(event) {
        event.preventDefault();

        var artifact_id = $(this).attr('data-artifact-id');
        var params = {
            aid  : artifact_id,
            func : 'show-in-overlay'
        };

        overlay_window.activateWindow({
                href        : codendi.tracker.base_url + '?' + $.param(params),
                title       : codendi.locales['cardwall']['edit_card'],
                iframeEmbed : true
        });
    }

    function getNewCardData(artifact_id, planning_id) {
        var params = {
            id          : artifact_id,
            planning_id : planning_id,
            action : 'get-card'
        };

        $.ajax({
            url: "/plugins/cardwall/?" + $.param(params),
            dataType: "json"
        }).success(updateCards);
    }

    function updateCards(update_json) {
        for (artifact_id in update_json) {
            updateCard(artifact_id, update_json[artifact_id]);
        }
    }

    function updateCard(artifact_id, update_json) {
        var $card = getCardArtifact(artifact_id);
        moveCardIfNeeded($card, update_json);
        updateCardContent($card, update_json);
    }

    function updateCardContent($card, artifact_json) {
        if (artifact_json.title || artifact_json.title === "") {
            updateCardTitle($card, artifact_json.title);
        }

        if (artifact_json.fields.remaining_effort || artifact_json.fields.remaining_effort === null) {
            updateCardRemainingEffort($card, artifact_json.fields.remaining_effort);
        }

        if (artifact_json.fields.assigned_to) {
            updateCardAssignTo($card, artifact_json.fields.assigned_to);
        }

        if (artifact_json.html_fields.impediment || artifact_json.html_fields.impediment === "") {
            updateCardImpediment($card, artifact_json.html_fields.impediment);
        }

        if (artifact_json.accent_color || artifact_json.accent_color === null) {
            updateColorOnCard($card, artifact_json.accent_color);
        }
    }

    function updateColorOnCard($card, color) {
        $card.css('background-color','');
        $card.css('background-color', color);
    }

    function setEmptyPlaceHolder($element) {
        $element.html("-");
    }

    function updateCardImpediment($card, impediment) {
        $card.find('div.card-details td.valueOf_impediment').html(impediment);
    }

    function updateCardAssignTo($card, assigned_to) {
        if (assigned_to.length === 0) {
            setEmptyPlaceHolder($('.valueOf_assigned_to > div', $card));
            return;
        }

        var card_id          = $card.attr('data-artifact-id');
        var $assigned_to_div = $('.valueOf_assigned_to > div', $card);
        var assigned_to_div  = $assigned_to_div.get(0);

        $assigned_to_div.html("");

        tuleap.agiledashboard.cardwall.cards.selectEditors[card_id].updateAssignedToValue(assigned_to_div, assigned_to);
    }

    function updateCardRemainingEffort($card, remaining_effort) {
        if (remaining_effort === null) {
            setEmptyPlaceHolder($('td.valueOf_remaining_effort > div', $card));
            return;
        }
        $card.find('div.card-details td.valueOf_remaining_effort > div').html(remaining_effort);
    }

    function updateCardTitle($card, title) {
        $card.find('span.card-title').html(title);
    }

    function getCardArtifact(artifact_id) {
        return $('div.card[data-artifact-id='+ artifact_id +']');
    }

    function moveCardIfNeeded($card, artifact_json) {
        if (isAnAncestor($card)) {
            return;
        }

        if (cardHasToBeMoved($card, artifact_json)) {
            var $element = $card.parent().detach();
            var $cell    = getConcernedCell(artifact_json);
            $cell.append($element);

            updateDroppableAreas($card, artifact_json);
        }
    }

    function getConcernedCell(artifact_json) {
        return $('tbody.cardwall tr[data-row-id='+artifact_json['ancestor_id']+'] td.cardwall-cell[data-column-id='+artifact_json['column_id']+'] ul');
    }

    function cardHasToBeMoved($card, artifact_json) {
        var current_parent_id = $card.parents('tr[data-row-id]').attr('data-row-id');
        var current_status_id = $card.parents('td[data-column-id]').attr('data-column-id');

        return current_parent_id !== artifact_json['ancestor_id'] || current_status_id !== artifact_json['column_id'];
    }

    function isAnAncestor($card) {
        if ($card.parents('td.cardwall-cell:not([data-column-id])').length > 0) {
            return true;
        }
        return false;
    }

    function updateDroppableAreas($card, artifact_json) {
        removeAllDropIntoClassesFromCard($card);

        artifact_json.drop_into.each(function(drop_class){
            $card.parent().addClass(drop_class);
        });
    }

    function removeAllDropIntoClassesFromCard($card) {
        var current_classes = $card.parent().attr('class');

        var cleared_classes = current_classes.replace(/drop-into-(\S)*(\s|$)/g, "");
        cleared_classes = cleared_classes.trim();

        $card.parent().attr('class', cleared_classes);
    }

    function getConcernedPlanningId() {
        return $('div.hidden[data-planning-id]').attr('data-planning-id');
    }

    function isOnAgiledashboard() {
        return $('div.hidden[data-planning-id]').length > 0;
    }

    tuleap.cardwall.cardsEditInPlace = {

        init: function() {
            if (isOnAgiledashboard()) {
                $('div.cardwall_board div.card li > a.edit-card').each(function(){
                    $(this).click(displayOverlay);
                });
            }
        },

        validateEdition: function(artifact_id) {
            var planning_id = getConcernedPlanningId();
            getNewCardData(artifact_id, planning_id);
            overlay_window.deactivate();
        }

    };

    $(document).ready(function () {
        tuleap.cardwall.cardsEditInPlace.init();
        overlay_window = new lightwindow({
            resizeSpeed: 10,
            delay: 0,
            finalAnimationDuration: 0,
            finalAnimationDelay: 0
        });
    });
})(jQuery);
