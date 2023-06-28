/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

/* global
    $:readonly
    Draggable:readonly
    $F:readonly
    Droppables:readonly
    $$:readonly
    tuleap:readonly
    codendi:readonly
    Ajax:readonly
*/

/**
 * This script manage the update of the cardwall (drag'n drop of card, edit of
 * fields, etc).
 */
document.observe("dom:loaded", function () {
    $$(".cardwall_board").each(function (board) {
        (function checkForLatestCardWallVersion() {
            if ($("tracker_report_cardwall_to_be_refreshed")) {
                //    Eg: board > drag n drop > go to a page > click back (the post it should be drag 'n dropped)
                if ($F("tracker_report_cardwall_to_be_refreshed") === "1") {
                    $("tracker_report_cardwall_to_be_refreshed").value = 0;
                    location.reload();
                } else {
                    $("tracker_report_cardwall_to_be_refreshed").value = 1;
                }
            }
        })();

        (function defineDraggableCards() {
            board.select(".cardwall_board_postit").each(function (postit) {
                function start_effect(element) {
                    Draggable._dragging[element] = true;
                    element.addClassName("cardwall_board_postit_flying");
                }

                function end_effect(element) {
                    Draggable._dragging[element] = false;
                    element.removeClassName("cardwall_board_postit_flying");
                }

                // eslint-disable-next-line no-new
                new Draggable(postit, {
                    revert: "failure",
                    delay: 175,
                    starteffect: start_effect,
                    endeffect: end_effect,
                });
            });
        })();

        (function dragAndDropColumns() {
            var cols = board.select("col");

            cols.each(function (col, col_index) {
                var table_body_rows = col.up("table").down("tbody.cardwall").childElements();

                table_body_rows.each(function (tr) {
                    var value_id = col.id.split("-")[1],
                        swimline_id = tr.id.split("-")[1],
                        current_td = tr.down("td.cardwall-cell", col_index),
                        accept_class = "drop-into-" + swimline_id + "-" + value_id;

                    const child_elements = tr.childElements();
                    child_elements[col_index]
                        .select(".cardwall_board_postit")
                        .invoke("removeClassName", accept_class);

                    Droppables.add(current_td, {
                        hoverclass: "cardwall_board_column_hover",
                        accept: accept_class,

                        onDrop: function (dragged) {
                            var value_id = col.id.split("-")[1],
                                new_column = dragged
                                    .up("tr")
                                    .childElements()
                                    .indexOf(dragged.up("td")),
                                new_column_id = cols[new_column].id.split("-")[1],
                                new_class_name = "drop-into-" + swimline_id + "-" + new_column_id;

                            //change the classname of the post it to be accepted by the formers columns
                            dragged.addClassName(new_class_name);
                            dragged.removeClassName(accept_class);

                            //switch to the new column
                            dragged.remove();
                            current_td.down("ul").appendChild(dragged);

                            setStyle(dragged);
                            ajaxUpdate(dragged, value_id);
                        },
                    });
                });
            });

            function setStyle(dragged) {
                dragged.setStyle({
                    left: "auto",
                    top: "auto",
                });
            }

            function ajaxUpdate(dragged, value_id) {
                var artifact_id = dragged.id.split("-")[1],
                    field_id,
                    url,
                    parameters = {};

                if ($("tracker_report_cardwall_settings_column")) {
                    field_id = $F("tracker_report_cardwall_settings_column");
                    parameters["artifact[" + field_id + "]"] = value_id;
                } else {
                    let values = [];
                    const field_id = dragged.readAttribute("data-column-field-id");
                    parameters["artifact[field_id]"] = field_id;
                    let columns = document.getElementsByClassName(
                        "cardwall_column_mapping_" + value_id + "_" + field_id
                    );

                    [].forEach.call(columns, function (element) {
                        values.push(parseInt(element.getValue(), 10));
                    });
                    parameters["artifact[possible_values]"] = JSON.stringify(values);
                }

                url = codendi.tracker.base_url + "?func=update-in-place&aid=" + artifact_id;

                //save the new state
                // eslint-disable-next-line no-new
                new Ajax.Request(url, {
                    method: "POST",
                    parameters: parameters,
                    onComplete: afterAjaxUpdate,
                    onFailure: function (response) {
                        function update_callback() {
                            if (tuleap.cardwall.isOnAgiledashboard()) {
                                tuleap.cardwall.cardsEditInPlace.moveCardCallback(artifact_id);
                            } else {
                                tuleap.tracker.artifactModalInPlace.defaultCallback();
                            }
                        }
                        function resetCard() {
                            $$(".tuleap-modal-close").invoke("observe", "click", update_callback);
                        }
                        function load_modal_callback() {
                            tuleap.tracker.artifactModalInPlace.showSubmitFailFeedback(
                                response.responseText
                            );
                            resetCard();
                        }

                        tuleap.tracker.artifactModalInPlace.loadEditArtifactModal(
                            artifact_id,
                            update_callback,
                            load_modal_callback,
                            parameters
                        );
                    },
                });
            }

            function afterAjaxUpdate(transport) {
                tuleap.agiledashboard.cardwall.card.updateAfterAjax(transport);
            }
        })();

        (function enableRemainingEffortInPlaceEditing() {
            $$(".valueOf_remaining_effort").each(function (remaining_effort_container) {
                // eslint-disable-next-line no-new
                new tuleap.agiledashboard.cardwall.card.TextElementEditor(
                    remaining_effort_container
                );
            });
        })();

        (function enableAssignedToInPlaceEditing() {
            $$(".valueOf_assigned_to").each(function (assigned_to_container) {
                var select_editor = new tuleap.agiledashboard.cardwall.card.SelectElementEditor(
                    assigned_to_container
                );
                var card_id = assigned_to_container.up(".card").readAttribute("data-artifact-id");
                tuleap.agiledashboard.cardwall.cards.selectEditors[card_id] = select_editor;
            });
        })();

        (function searchInCardwall() {
            var cardwall = board.down(".cardwall");
            board.up().select(".search-in-cardwall").each(registerTextFieldEvents);

            function registerTextFieldEvents(text_field) {
                text_field.observe("keyup", function () {
                    onUpdate(text_field);
                });
            }

            function onUpdate(text_field) {
                var swimlines = cardwall.childElements(),
                    all_cards = cardwall.select(".cardwall_board_postit"),
                    cards_to_hide = selectCardsToHide(all_cards, text_field.value);

                all_cards.invoke("show");
                cards_to_hide.invoke("hide");
                swimlines.map(showSwimlineCard);
            }

            function selectCardsToHide(all_cards, text_to_search) {
                try {
                    var regexp = new RegExp(text_to_search, "i");
                } catch (e) {
                    // the user broke the regexp, hide nothing
                    return [];
                }

                return all_cards.reject(function (card) {
                    var searchable_content_list = card.select(
                        ".card-title",
                        ".dropdown-toggle",
                        ".valueOf_assigned_to",
                        ".valueOf_remaining_effort"
                    );

                    return (
                        searchInTextContent(searchable_content_list, regexp) ||
                        searchInAvatarTitle(card, regexp)
                    );
                });
            }

            function searchInTextContent(searchable_content_list, regexp) {
                return searchable_content_list.find(function (searchable_content) {
                    var text;
                    if (searchable_content.innerText !== undefined) {
                        // Yay IE Family!
                        text = searchable_content.innerText;
                    } else {
                        text = searchable_content.textContent;
                    }

                    return text.match(regexp);
                });
            }

            function searchInAvatarTitle(card, regexp) {
                return card.select(".valueOf_assigned_to .avatar").find(function (avatar) {
                    return avatar.title.match(regexp);
                });
            }

            function showSwimlineCard(swimline) {
                var swimline_card = swimline.down("td:first > .nodrag .card"),
                    visible_cards = swimline.select(".card").find(function (card) {
                        return card.visible();
                    });

                if (!swimline_card.visible() && visible_cards) {
                    swimline_card.show();
                }
            }
        })();

        (function stackCards() {
            var stacked_classname = "cardwall-cell-stacked";

            $$(".cardwall_board th").each(function (th) {
                var toggle_input = th.down(".cardwall-auto-stack-toggle"),
                    cell_index = th.cellIndex,
                    cells_in_column = ".cardwall > tr > td:nth-child(" + (cell_index + 1) + ")";

                if (!toggle_input) {
                    return;
                }

                toggle_input.observe("click", function () {
                    var toggle = toggle_input.checked ? "addClassName" : "removeClassName";
                    $$(cells_in_column).invoke(toggle, stacked_classname);

                    // Persist toggle on backend
                    // eslint-disable-next-line no-new
                    new Ajax.Request(tuleap.cardwall.base_url, {
                        method: "POST",
                        parameters: {
                            action: "toggle_user_autostack_column",
                            name: toggle_input.value,
                        },
                    });
                });
            });

            $$(".cardwall-cell-controls-stack").each(function (icon_resize) {
                var td = icon_resize.up("td");

                icon_resize.observe("click", function () {
                    td.toggleClassName(stacked_classname);
                });
            });
        })();
    });

    (function initTooltipForCompletionBarHelper() {
        if ($("#milestone_points_completion_bar")) {
            $("#milestone_points_completion_bar").tooltip();
        }
    })();
});
