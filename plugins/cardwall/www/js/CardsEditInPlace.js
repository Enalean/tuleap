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
    var specific_fields = ['assigned_to','remaining_effort'];
    var overlay_window;

    function displayIframeOverlay(event, link) {
        event.preventDefault();

        overlay_window = new lightwindow({
            resizeSpeed: 10,
            delay: 0,
            finalAnimationDuration: 0,
            finalAnimationDelay: 0
        });

        var artifact_id = link.attr('data-artifact-id');
        var params = {
            aid  : artifact_id,
            func : 'show-in-overlay'
        };


        overlay_window.activateWindow({
                href        : codendi.tracker.base_url + '?' + $.param(params),
                title       : codendi.locales['cardwall']['edit_card'],
                iframeEmbed : true
        });

        bindCancelEvent();
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
        updateNonSpecificFields($card, artifact_json);

        updateSpecificFields($card, artifact_json);
    }

    function updateSpecificFields($card, artifact_json) {

        if (artifact_json.title || artifact_json.title === "") {
            updateCardTitle($card, artifact_json.title);
        }

        if (artifact_json.fields.remaining_effort || artifact_json.fields.remaining_effort === null) {
            updateCardRemainingEffort($card, artifact_json.fields.remaining_effort);
        }

        if (artifact_json.fields.assigned_to) {
            updateCardAssignTo($card, artifact_json.fields.assigned_to);
        }

        if (artifact_json.accent_color || artifact_json.accent_color === null) {
            updateColorOnCard($card, artifact_json.accent_color);
        }

    }

    function isSpecificField(field_name) {
        return $.inArray(field_name, specific_fields) > -1;
    }

    function updateNonSpecificFields($card, json) {
        for (var html_field in json.html_fields) {
            if (json.html_fields.hasOwnProperty(html_field) && ! isSpecificField(html_field)) {
                updateNonSpecificField($card, html_field, json.html_fields);
            }
        }
    }

    function updateNonSpecificField($card, html_field, json) {
        $card.find('div.card-details td.valueOf_' + html_field).html(json[html_field]);
    }

    function updateColorOnCard($card, color) {
        $card.css('background-color','');
        $card.css('background-color', color);
    }

    function setEmptyPlaceHolder($element) {
        $element.html("-");
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
        $card.find('div.card-title').html(title);
    }

    function getCardArtifact(artifact_id) {
        return $('div.card[data-artifact-id='+ artifact_id +']');
    }

    function moveCardIfNeeded($card, artifact_json) {
        if (isAnAncestor($card)) {
            return;
        }

        if (cardHasToBeMoved($card, artifact_json)) {
            var swimline_id = getSwimlineId($card);
            var $cell       = getConcernedCell(artifact_json, swimline_id);
            var $element    = $card.parent().detach();
            $cell.append($element);

            updateDroppableAreas($card, artifact_json, swimline_id);
        }
    }

    function getConcernedCell(artifact_json, swimline_id) {
        return $('tbody.cardwall tr[data-row-id='+swimline_id+'] td.cardwall-cell[data-column-id='+artifact_json['column_id']+'] ul');
    }

    function getSwimlineId($card) {
        return $card.parents('tr[data-row-id]').attr('data-row-id');
    }

    function cardHasToBeMoved($card, artifact_json) {
        var current_status_id = $card.parents('td[data-column-id]').attr('data-column-id');

        return current_status_id !== artifact_json['column_id'];
    }

    function isAnAncestor($card) {
        if ($card.parents('td.cardwall-cell:not([data-column-id])').length > 0) {
            return true;
        }
        return false;
    }

    function updateDroppableAreas($card, artifact_json, swimline_id) {
        removeAllDropIntoClassesFromCard($card);

        artifact_json.drop_into.each(function(cell_id){
            $card.parent().addClass('drop-into-' + swimline_id + '-' + cell_id);
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

    function disableOverlay() {
        overlay_window.deactivate();
    }

    function bindCancelEvent() {
        var iframe = $('#lightwindow_iframe').get(0);
        $(iframe).load(function() {
            var content = iframe.contentWindow.document;
            $('button[name=cancel]',content).each(function(){
                $(this).on('click', function(e){
                   disableOverlay();
                   e.preventDefault();
                });
            });
        });
    }

    tuleap.cardwall.cardsEditInPlace = {

        init: function() {
            var self = this;
            if (! isOnAgiledashboard()) {
                return;
            }

            $('div.cardwall_board div.card li > a.edit-card').click(function(event){
                event.preventDefault();

                var artifact_id = $(this).attr('data-artifact-id');

                if (! self.isBrowserCompatible()) {
                    displayIframeOverlay(event, $(this));
                    return;
                }

                $.ajax({
                    url: codendi.tracker.base_url + '?aid='+artifact_id+'&func=get-edit-in-place'
                }).done(function( data ) {
                    self.showArtifactEditForm(data, artifact_id)
                    codendi.tracker.runTrackerFieldDependencies();

                    $('.tuleap-modal-main-panel form textarea').each( function(){
                        var element = $(this).get(0); //transform to prototype
                        enableRichTextArea(element)
                    });
                }).fail(function() {
                    displayIframeOverlay(event, $(self));
                });
            });

            function enableRichTextArea(element) {
                var html_id    = element.id,
                    id         = html_id.match(/_(\d+)$/),
                    htmlFormat = false,
                    name;

                if (id) {
                    id   = id[1];
                    name = 'artifact['+ id +'][format]';

                    if (Element.readAttribute('artifact['+id+']_body_format', 'value') == 'html') {
                        htmlFormat = true;
                    }

                    new tuleap.trackers.textarea.RTE(
                        element,
                        {toggle: true, default_in_html: false, id: id, name: name, htmlFormat: htmlFormat, no_resize : true}
                    );
                }
            }
        },

        validateEdition: function(artifact_id) {
            var planning_id = getConcernedPlanningId();
            getNewCardData(artifact_id, planning_id);
            disableOverlay();
        },

        isBrowserCompatible : function() {
            return ! tuleap.browserCompatibility.isIE7();
        },

        showArtifactEditForm : function(form_html, artifact_id) {
            var self = this;
            $('body').append(form_html);
            tuleap.modal.init();

            $('#tuleap-modal-submit').click(function(event) {
                self.updateRichTextAreas();

                if (! self.isArtifactSubmittable(event)) {
                    return;
                }

                $('#artifact-form-errors').hide();

                $.ajax({
                    url     : '/plugins/tracker/?aid='+artifact_id+'&func=update-in-place',
                    type    : 'post',
                    data    : $('.tuleap-modal-main-panel form').serialize()
                }).done( function() {
                    var planning_id = getConcernedPlanningId();
                    tuleap.modal.closeModal();
                    getNewCardData(artifact_id, planning_id);
                }).fail( function(response) {
                    var data = JSON.parse(response.responseText);

                    $('#artifact-form-errors h5').html(data.message);
                    $.each(data.errors, function() {
                      $('#artifact-form-errors ul').html('').append('<li>' + this + '</li>');
                    });

                    $('.tuleap-modal-main-panel .tuleap-modal-content').scrollTop(0);
                    $('#artifact-form-errors').show();
                });
                return false;
            });

            $('.tuleap-modal-close').click(function(event) {
                $('.artifact-event-popup').remove();
            });
        },

        isArtifactSubmittable : function(event) {
            return tuleap.trackers.submissionKeeper.isArtifactSubmittable(event);
        },

        updateRichTextAreas : function() {
            for (instance in CKEDITOR.instances) {
                CKEDITOR.instances[instance].updateElement();
            }
        }
    };

    $(document).ready(function () {
        tuleap.cardwall.cardsEditInPlace.init();
    });
})(jQuery);
