/**
 * Copyright (c) Tuleap, 2017 - 2018. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/* global $$:readonly codendi:readonly $:readonly tuleap:readonly Ajax:readonly */

document.observe("dom:loaded", function () {
    $$(".tracker-field-richtext").each(function define_rich_text(elem) {
        var r = new codendi.RTE(elem); //eslint-disable-line @typescript-eslint/no-unused-vars
    });

    $$("input[type=checkbox][name^=remove_rule]").each(function (elem) {
        elem.observe("click", function () {
            if (elem.checked) {
                elem.up("tr").down("div").addClassName("deleted");
                elem.up("tr")
                    .select("select")
                    .each(function (e) {
                        e.disabled = true;
                        e.readOnly = true;
                    });
                elem.up("tr")
                    .select("input")
                    .each(function (e) {
                        e.required = false;
                    });
            } else {
                elem.up("tr").down("div").removeClassName("deleted");
                elem.up("tr")
                    .select("select")
                    .each(function (e) {
                        e.disabled = false;
                        e.readOnly = false;
                    });
                elem.up("tr")
                    .select("input")
                    .each(function (e) {
                        if (e.hasClassName("required")) {
                            e.required = true;
                        }
                    });
            }
        });
    });

    $$(".add_new_rule_title").each(function (add) {
        var link = new Element("a", { href: "#add_new_rule" })
            .update(add.innerHTML)
            .observe("click", function (evt) {
                add.next().toggle();
                Event.stop(evt);
                return false;
            });
        link.dataset.test = "add-new-rule";
        add.update(link);
        add.next().hide();
    });

    /*
     * Trigger event handling
     */
    (function trigger_init() {
        if (!$("add_new_trigger_title")) {
            return;
        }

        var trigger = new tuleap.trackers.trigger(),
            existing_triggers_table = $("triggers_existing").down("tbody"),
            triggering_field_template = detachElement(
                existing_triggers_table.down(".trigger_description_triggering_field")
            ),
            trigger_template = detachElement(existing_triggers_table.down("tr"));

        function displayExistingTriggers() {
            if (tuleap.trackers.trigger.existing.size() == 0) {
                return;
            }

            tuleap.trackers.trigger.existing.each(function (trigger) {
                displayTrigger(trigger);
            });
        }

        function detachElement(element) {
            element.remove();
            return element;
        }

        function displayTrigger(trigger_as_JSON) {
            var trigger_id = trigger_as_JSON.id,
                trigger_element = addTriggerContainer(trigger_template, trigger_id);

            trigger_element
                .down(".trigger_description_target_field_name")
                .update(trigger_as_JSON.target.field_label);
            trigger_element
                .down(".trigger_description_target_field_value")
                .update(trigger_as_JSON.target.field_value_label);

            trigger_as_JSON.triggering_fields.each(function (triggering_field) {
                addTriggeringField(triggering_field, trigger_element);
            });
            removeFirstOperator(trigger_element);
            bindRemove(trigger_element, trigger_id);

            function addTriggerContainer(trigger_template, trigger_id) {
                var trigger_element;

                existing_triggers_table.insert(trigger_template.cloneNode(true));
                trigger_element = existing_triggers_table.childElements().last();
                trigger_element.writeAttribute("data-trigger-id", trigger_id);

                return trigger_element;
            }

            function addTriggeringField(triggering_field, trigger_element) {
                var triggering_fields_list = trigger_element.down(
                        ".trigger_description_triggering_fields"
                    ),
                    condition = codendi.locales.tracker_trigger[trigger_as_JSON.condition].name,
                    operator = codendi.locales.tracker_trigger[trigger_as_JSON.condition].operator,
                    have_field =
                        codendi.locales.tracker_trigger[trigger_as_JSON.condition].have_field,
                    triggering_field_element;

                triggering_fields_list.insert(triggering_field_template.cloneNode(true));
                triggering_field_element = triggering_fields_list.childElements().last();

                triggering_field_element
                    .down(".trigger_description_triggering_field_operator")
                    .update(operator);
                triggering_field_element
                    .down(".trigger_description_triggering_field_quantity")
                    .update(condition);
                triggering_field_element
                    .down(".trigger_description_triggering_have_field")
                    .update(have_field);
                triggering_field_element
                    .down(".trigger_description_triggering_field_tracker")
                    .update(triggering_field.tracker_name);
                triggering_field_element
                    .down(".trigger_description_triggering_field_field_name")
                    .update(triggering_field.field_label);
                triggering_field_element
                    .down(".trigger_description_triggering_field_field_value")
                    .update(triggering_field.field_value_label);

                if (triggering_fields_list.childElements().size() > 1) {
                    triggering_field_element
                        .down(".trigger_description_triggering_field_when")
                        .hide();
                }
            }

            function removeFirstOperator(trigger_element) {
                trigger_element.down(".trigger_description_triggering_field_operator").update("");
            }

            function bindRemove(trigger_element, trigger_id) {
                Event.observe(trigger_element.down(".trigger_remove"), "click", function () {
                    var query_params = window.location.href.toQueryParams();
                    new Ajax.Request(
                        codendi.tracker.base_url +
                            "?tracker=" +
                            query_params.tracker +
                            "&id=" +
                            trigger_id +
                            "&func=admin-workflow-delete-trigger",
                        {
                            method: "POST",
                            onSuccess: function () {
                                trigger_element.remove();
                            },
                            onFailure: function (response) {
                                alert(response.responseText); //eslint-disable-line no-alert
                            },
                        }
                    );
                });
            }
        }

        function reset() {
            trigger.getTriggeringFields().each(function (triggering_field) {
                if (
                    triggering_field
                        .getContainer()
                        .readAttribute("data-trigger-condition-initial") !== "true"
                ) {
                    trigger.removeTriggeringField(triggering_field);
                } else {
                    triggering_field.removeAllOptions();
                }

                $("triggers_form").reset();
            });
        }

        (function display() {
            $("trigger_create_new").hide();

            (function bindAddNewTrigger() {
                Event.observe($("add_new_trigger_title"), "click", function (evt) {
                    $("add_new_trigger_title").hide();
                    $("trigger_create_new").show();
                    Event.stop(evt);
                });
            })();

            displayExistingTriggers();
        })();

        (function bindAddExtraTriggeringField() {
            Event.observe($("trigger_add_condition"), "click", function (evt) {
                var triggering_field = trigger.addTriggeringField();

                triggering_field.activateDeleteButton(trigger);
                triggering_field.makeOperatorDynamic();
                Event.stop(evt);
            });
        })();

        (function bindCancelAddNewTrigger() {
            Event.observe($("trigger_add_cancel"), "click", function (evt) {
                $("trigger_create_new").hide();
                $("add_new_trigger_title").show();
                reset();
                Event.stop(evt);
            });
        })();

        (function bindSubmitNewTrigger() {
            var callback = function () {
                $("trigger_create_new").hide();
                $("add_new_trigger_title").show();
                displayNewTrigger(trigger);
            };

            Event.observe($("trigger_submit_new"), "click", function () {
                trigger.save(callback);
            });

            function displayNewTrigger(trigger) {
                var trigger_as_JSON = trigger.toJSON();

                trigger_as_JSON.id = trigger.getId();
                displayTrigger(trigger_as_JSON);
                reset();
            }
        })();
    })();
});
