/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

/* global Class:readonly Ajax:readonly $:readonly $H:readonly $F:readonly $$:readonly codendi:readonly */

var tuleap = tuleap || {};
tuleap.trackers = tuleap.trackers || {};

/**
 * Used in the tracker workflow (admin area) to construct a new trigger.
 * Fetches data and injects it into a form.
 */
tuleap.trackers.trigger = Class.create({
    triggering_fields: [],
    counter: 0,
    id: null,

    initialize: function () {
        var self = this;

        (function populateOptions() {
            if (typeof tuleap.trackers.trigger.form_data.targets === "undefined") {
                addTriggerFormDataFromAjax(self);
            } else {
                addTriggerFormData(self);
            }
        })();

        function addTriggerFormDataFromAjax(self) {
            var tracker_id = self.getUrlParam("tracker");

            new Ajax.Request(
                codendi.tracker.base_url +
                    "?tracker=" +
                    tracker_id +
                    "&func=admin-get-triggers-rules-builder-data",
                {
                    method: "GET",
                    onComplete: function (transport) {
                        tuleap.trackers.trigger.form_data = transport.responseJSON;
                        addTriggerFormData(self);
                    },
                },
            );
        }

        function addTriggerFormData(self) {
            var form_data = tuleap.trackers.trigger.form_data;

            if (form_data.triggers.length === 0) {
                showNoChildrenMessage();
            } else {
                showAddLink();
            }
            populateConditions(form_data.conditions);
            populateChildTrackers(form_data.triggers);
            populateTargetFields(form_data.targets);
            addFirstTriggeringField(self);
        }

        function showNoChildrenMessage() {
            $("triggers_form").hide();
            $("triggers_no_children").show();
        }

        function showAddLink() {
            $("triggers_form").show();
        }

        function addFirstTriggeringField(self) {
            var triggering_field = self.addTriggeringField();

            triggering_field.removeDeleteButton();
            triggering_field.addConditionSelector();
            triggering_field.makeOperatorDynamic();
        }

        function populateConditions(conditions) {
            conditions.each(function (condition) {
                var option,
                    locales = codendi.locales.tracker_trigger;

                option = new Element("option", {
                    value: condition.name,
                    "data-condition-operator": condition.operator,
                }).update(locales[condition.name].name);

                $("trigger_condition_quantity").appendChild(option);
            });
        }

        function populateChildTrackers(child_trackers) {
            $H(child_trackers).each(function (child_tracker) {
                var option = new Element("option", {
                    value: child_tracker.value.id,
                }).update(child_tracker.value.name);

                $$(".trigger_triggering_field_child_tracker_name").first().appendChild(option);
            });
        }

        function populateTargetFields(target_trackers) {
            $H(target_trackers).each(function (target_tracker) {
                var option = createOption(target_tracker.value, "");

                $("trigger_target_field_name").appendChild(option);
            });

            makeTargetFieldValuesDynamic(target_trackers);
        }

        function createOption(data, class_names) {
            return new Element("option", {
                value: tuleap.escaper.html(data.id),
                class: tuleap.escaper.html(class_names),
            }).update(tuleap.escaper.html(data.label));
        }

        function makeTargetFieldValuesDynamic(child_trackers) {
            Event.observe($("trigger_target_field_name"), "change", function (evt) {
                var field_id = Event.element(evt).value,
                    field_values;

                removeExistingValues();

                if (typeof child_trackers[field_id] === "undefined") {
                    return;
                }

                field_values = child_trackers[field_id];
                populateTargetFieldValues(field_values);
            });
        }

        function removeExistingValues() {
            $$(".trigger-target-field-value").each(function (field_value) {
                field_value.remove();
            });
        }

        function populateTargetFieldValues(field_values) {
            field_values.values.forEach(function (field_value) {
                $("trigger_target_field_value").appendChild(
                    createOption(field_value, "trigger-target-field-value"),
                );
            });
        }
    },

    getUrlParam: function (name) {
        var params = window.location.href.toQueryParams();

        return params[name];
    },

    addTriggeringField: function () {
        var triggering_field = new tuleap.trackers.trigger.triggering_field();

        triggering_field.setId(this.counter);
        this.triggering_fields[this.counter] = triggering_field;
        this.counter++;

        return triggering_field;
    },

    getTriggeringFields: function () {
        return this.triggering_fields;
    },

    removeTriggeringField: function (triggering_field) {
        triggering_field.getContainer().remove();
        delete this.triggering_fields[triggering_field.getId()];
    },

    save: function (callback) {
        var trigger_data = this.toJSON(),
            self = this,
            tracker_id = this.getUrlParam("tracker");

        if (!trigger_data) {
            return;
        }

        new Ajax.Request(
            codendi.tracker.base_url +
                "?tracker=" +
                tracker_id +
                "&func=admin-workflow-add-trigger",
            {
                contentType: "application/json",
                method: "POST",
                postBody: Object.toJSON(trigger_data),
                onSuccess: function (response) {
                    self.setId(response.responseText);
                    callback();
                },
                onFailure: function (response) {
                    //eslint-disable-next-line no-alert
                    alert(response.responseText);
                },
            },
        );
    },

    getTargetFieldId: function () {
        return $F("trigger_target_field_name");
    },

    getTargetFieldValueId: function () {
        return $F("trigger_target_field_value");
    },

    getTargetFieldLabel: function () {
        return $("trigger_target_field_name").options[$("trigger_target_field_name").selectedIndex]
            .innerHTML;
    },

    getTargetFieldValueLabel: function () {
        return $("trigger_target_field_value").options[
            $("trigger_target_field_value").selectedIndex
        ].innerHTML;
    },

    toJSON: function () {
        var triggering_fields = getTriggeringFields(this),
            target = getTarget(this),
            condition = $F("trigger_condition_quantity");

        if (!target || !triggering_fields) {
            //eslint-disable-next-line no-alert
            alert(codendi.locales.tracker_trigger.save_missing_data);
            return "";
        }

        return {
            target: target,
            condition: condition,
            triggering_fields: triggering_fields,
        };

        function getTriggeringFields(self) {
            var triggering_fields_as_JSON = [],
                triggering_fields = self.getTriggeringFields().compact();

            triggering_fields.each(function (triggering_field) {
                var field_id = triggering_field.getChildTrackerFieldId(),
                    field_value_id = triggering_field.getChildTrackerFieldValueId(),
                    field_label = triggering_field.getChildTrackerFieldLabel(),
                    field_value_label = triggering_field.getChildTrackerFieldValueLabel(),
                    tracker = triggering_field.getChildTrackerName();

                if (field_id === "" || field_value_id === "") {
                    return false;
                }

                triggering_fields_as_JSON.push({
                    field_id: field_id,
                    field_value_id: field_value_id,
                    field_label: field_label,
                    field_value_label: field_value_label,
                    tracker_name: tracker,
                });
            });

            if (triggering_fields.length != triggering_fields_as_JSON.length) {
                return "";
            }

            return triggering_fields_as_JSON;
        }

        function getTarget(self) {
            var field_id = self.getTargetFieldId(),
                field_value_id = self.getTargetFieldValueId(),
                field_label = self.getTargetFieldLabel(),
                field_value_label = self.getTargetFieldValueLabel();

            if (field_id === "" || field_value_id === "") {
                return false;
            }

            return {
                field_id: field_id,
                field_value_id: field_value_id,
                field_label: field_label,
                field_value_label: field_value_label,
            };
        }
    },

    setId: function (id) {
        this.id = id;
    },

    getId: function () {
        return this.id;
    },
});

tuleap.trackers.trigger.triggering_field = Class.create({
    container: null,
    tracker_fields: [],
    id: null,

    initialize: function () {
        var triggering_field_data = $$("#trigger_triggering_fields_template > tbody").first()
            .innerHTML;

        $("trigger_triggering_field_list").insert(triggering_field_data);

        this.container = $$("#trigger_triggering_field_list .trigger_triggering_field").last();
        this.makeChildTrackerFieldsDynamic();
        this.makeChildTrackerFieldValuesDynamic();
    },

    activateDeleteButton: function (trigger) {
        var button = this.container.down(".trigger_triggering_field_remove"),
            self = this;

        Event.observe(button, "click", function () {
            trigger.removeTriggeringField(self);
        });
    },

    removeDeleteButton: function () {
        this.container.down(".trigger_triggering_field_remove").remove();
    },

    addConditionSelector: function () {
        var selector = $("trigger_quantity_template");

        selector.show();
        this.container.down("td").update(selector);
        this.container.writeAttribute("data-trigger-condition-initial", "true");
    },

    makeChildTrackerFieldsDynamic: function () {
        var self = this;

        Event.observe(
            $$(".trigger_triggering_field_child_tracker_name").last(),
            "change",
            function (evt) {
                var select_box_element = Event.element(evt),
                    tracker_id = select_box_element.value;

                self.removeAllOptions();
                self.addTrackerFieldsData(tracker_id, select_box_element);
            },
        );
    },

    removeAllOptions: function () {
        var container = this.container;

        removeExistingTrackerFields();
        this.removeExistingFieldValues();

        function removeExistingTrackerFields() {
            $$(".trigger-triggering_field-tracker-field").each(function (field_value) {
                if (field_value.descendantOf(container)) {
                    field_value.remove();
                }
            });
        }
    },

    makeChildTrackerFieldValuesDynamic: function () {
        var container = this.container,
            self = this;

        Event.observe(
            $$(".trigger_triggering_field_child_tracker_field_name").last(),
            "change",
            function (evt) {
                var field_id = Event.element(evt).value,
                    tracker_id = container.down(
                        ".trigger_triggering_field_child_tracker_name",
                    ).value;

                self.removeExistingFieldValues();
                self.addTrackerFieldValuesData(tracker_id, field_id);
            },
        );
    },

    removeExistingFieldValues: function () {
        var container = this.container;

        $$(".trigger-triggering_field-tracker-field-value").each(function (field_value) {
            if (field_value.descendantOf(container)) {
                field_value.remove();
            }
        });
    },

    addTrackerFieldsData: function (tracker_id, select_box_element) {
        var tracker_fields = this.tracker_fields;

        if (typeof tracker_fields[tracker_id] === "undefined") {
            tracker_fields[tracker_id] = fetchFromFormData();
        }

        if (!tracker_fields[tracker_id]) {
            return;
        }

        populateTrackerFields(tracker_fields[tracker_id], select_box_element);

        function fetchFromFormData() {
            var form_data = tuleap.trackers.trigger.form_data;

            if (
                typeof form_data.triggers === "undefined" ||
                typeof form_data.triggers[tracker_id] === "undefined" ||
                typeof form_data.triggers[tracker_id].fields === "undefined"
            ) {
                return false;
            }

            return form_data.triggers[tracker_id].fields;
        }

        function populateTrackerFields(fields, select_box_element) {
            $H(fields).each(function (field) {
                var option = createOption(field.value, "trigger-triggering_field-tracker-field");
                select_box_element
                    .next("select", ".trigger_triggering_field_child_tracker_field_name")
                    .appendChild(option);
            });
        }

        function createOption(data, class_names) {
            return new Element("option", {
                value: tuleap.escaper.html(data.id),
                class: tuleap.escaper.html(class_names),
            }).update(tuleap.escaper.html(data.label));
        }
    },

    addTrackerFieldValuesData: function (tracker_id, field_id) {
        var tracker_fields = this.tracker_fields,
            field_values;

        if (
            typeof tracker_fields[tracker_id] === "undefined" ||
            typeof tracker_fields[tracker_id][field_id] === "undefined" ||
            typeof tracker_fields[tracker_id][field_id].values === "undefined"
        ) {
            return;
        }

        field_values = tracker_fields[tracker_id][field_id].values;
        populateFieldValues(field_values, this.container);

        function populateFieldValues(fields, container) {
            fields.forEach(function (field) {
                var option = createOption(field, "trigger-triggering_field-tracker-field-value");

                container
                    .down(".trigger_triggering_field_child_tracker_field_value")
                    .appendChild(option);
            });
        }

        function createOption(data, class_names) {
            return new Element("option", {
                value: tuleap.escaper.html(data.id),
                class: tuleap.escaper.html(class_names),
            }).update(tuleap.escaper.html(data.label));
        }
    },

    makeOperatorDynamic: function () {
        updateOperators();

        Event.observe($("trigger_condition_quantity"), "change", function () {
            updateOperators();
        });

        function updateOperators() {
            var option = $("trigger_condition_quantity").options[
                    $("trigger_condition_quantity").selectedIndex
                ],
                operator_name = option.value,
                locales = codendi.locales.tracker_trigger;

            $$(".trigger_condition_artifact_operator_updater").each(function (span) {
                var operator = option.readAttribute("data-condition-operator");

                span.update(locales[operator] + " " + locales[operator_name].name);
            });

            $$(".trigger_triggering_have_field").each(function (span) {
                span.update(locales[operator_name].have_field);
            });
        }
    },

    getChildTrackerFieldId: function () {
        return this.container.down(".trigger_triggering_field_child_tracker_field_name").value;
    },

    getChildTrackerFieldValueId: function () {
        return this.container.down(".trigger_triggering_field_child_tracker_field_value").value;
    },

    getChildTrackerFieldLabel: function () {
        var selector = this.container.down(".trigger_triggering_field_child_tracker_field_name");

        return selector.options[selector.selectedIndex].innerHTML;
    },

    getChildTrackerFieldValueLabel: function () {
        var selector = this.container.down(".trigger_triggering_field_child_tracker_field_value");

        return selector.options[selector.selectedIndex].innerHTML;
    },

    getChildTrackerName: function () {
        var selector = this.container.down(".trigger_triggering_field_child_tracker_name");

        return selector.options[selector.selectedIndex].innerHTML;
    },

    getId: function () {
        return this.id;
    },

    setId: function (id) {
        this.id = id;
    },

    getContainer: function () {
        return this.container;
    },
});

tuleap.trackers.trigger.form_data = tuleap.trackers.trigger.form_data || {};
