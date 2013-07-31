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

var tuleap              = tuleap || {};
tuleap.trackers         = tuleap.trackers || {};

/**
 * Used in the tracker workflow (admin area) to construct a new trigger.
 * Fetches data and injects it into a form.
 */
tuleap.trackers.trigger = Class.create({
    conditions : [],
    counter : 0,
    id : null,

    initialize: function() {
        var self = this;

        (function populateOptions() {
            if (typeof tuleap.trackers.trigger.form_data.targets === 'undefined') {
                addTriggerFormDataFromAjax(self);
            } else {
                addTriggerFormData(self);
            }
        })();

        function addTriggerFormDataFromAjax(self) {
            var tracker_id = self.getUrlParam('tracker');

            new Ajax.Request(
                codendi.tracker.base_url+'?tracker='+tracker_id+'&func=admin-get-triggers-rules-builder-data',
                {
                    'method' : "GET",
                    'onComplete': function (transport) {
                        tuleap.trackers.trigger.form_data = transport.responseJSON;
                        addTriggerFormData(self);
                    }
                }
            );
        }

        function addTriggerFormData(self) {
            var form_data = tuleap.trackers.trigger.form_data;

            populateConditions(form_data.conditions);
            populateChildTrackers(form_data.triggers);
            populateTargetFields(form_data.targets);
            addFirstCondition(self);
        }

        function addFirstCondition(self) {
            var condition = self.addCondition();

            condition.removeDeleteButton();
            condition.addQuantitySelector();
        }

        function populateConditions(conditions) {
            conditions.each(function(condition) {
                var option,
                    locales = codendi.locales.tracker_trigger;

                option = new Element('option', {
                    "value" : condition.name,
                    "data-condition-operator" : condition.operator
                }).update(locales[condition.name].name);

                $('trigger_condition_quantity').appendChild(option);
            });
        }

        function populateChildTrackers(child_trackers) {
            $H(child_trackers).each(function(child_tracker) {
                var option = new Element('option', {
                        "value" : child_tracker.value.id
                    }).update(child_tracker.value.name);

                $$('.trigger_condition_child_tracker_name').first().appendChild(option);
            });
        }

        function populateTargetFields(target_trackers) {
            $H(target_trackers).each(function(target_tracker) {
                var option = createOption(target_tracker.value, "");

                $('trigger_condition_field_name').appendChild(option);
            });

            makeTargetFieldValuesDynamic(target_trackers);
        }

        function createOption(data, class_names) {
            return new Element('option', {
                "value" : data.id,
                "class" : class_names
            }).update(data.label)
        }

        function makeTargetFieldValuesDynamic(child_trackers) {
            Event.observe($('trigger_condition_field_name'), 'change', function(event) {
                var field_id = event.currentTarget.value,
                    field_values;

                removeExistingValues();

                if (typeof child_trackers[field_id] === 'undefined') {
                    return;
                }

                field_values = child_trackers[field_id];
                populateTargetFieldValues(field_values);
            });
        }

        function removeExistingValues() {
            $$('.trigger-target-field-value').each(function(field_value) {
                field_value.remove();
            });
        }

        function populateTargetFieldValues(field_values) {
            $H(field_values.values).each(function(field_value) {
                var option = createOption(field_value.value, "trigger-target-field-value");
                $('trigger_condition_field_value').appendChild(option);
            })
        }
    },

    getUrlParam : function (name) {
        var params = window.location.href.toQueryParams();

        return params[name];
    },

    addCondition : function() {
        var condition = new tuleap.trackers.trigger.condition();

        condition.setId(this.counter);
        this.conditions[this.counter] = condition;
        this.counter++;

        return condition;
    },

    getConditions : function() {
        return this.conditions;
    },

    removeCondition : function(condition) {
        condition.getContainer().remove();
        delete this.conditions[condition.getId()];
    },

    save : function(callback) {
        var trigger_data    = this.toJSON(),
            self            = this,
            tracker_id      = this.getUrlParam('tracker');

        if (! trigger_data) {
            return;
        }

        new Ajax.Request(
            codendi.tracker.base_url+'?tracker='+tracker_id+'&func=admin-workflow-add-trigger',
            {
                'contentType' : 'application/json',
                'method' : 'POST',
                'postBody' : Object.toJSON(trigger_data),
                'onSuccess' : function(response) {
                    self.setId(response.responseText)
                    callback();
                },
                'onFailure' : function(response) {
                    alert(response.responseText);
                }
            }
        );
    },

    getTargetFieldId : function() {
        return $F('trigger_condition_field_name');
    },

    getTargetFieldValueId : function() {
        return $F('trigger_condition_field_value');
    },

    getTargetFieldLabel : function() {
        return $('trigger_condition_field_name').options[$('trigger_condition_field_name').selectedIndex].innerHTML;
    },

    getTargetFieldValueLabel : function() {
        return $('trigger_condition_field_value').options[$('trigger_condition_field_value').selectedIndex].innerHTML;
    },

    toJSON : function() {
        var triggering_fields   = getTriggeringFields(this),
            target              = getTarget(this),
            condition           = $F('trigger_condition_quantity');

        if (! target || ! triggering_fields) {
            return '';
        }

        return {
            "target" : target,
            "condition" : condition,
            "triggering_fields" : triggering_fields
        };

        function getTriggeringFields(self) {
            var triggering_fields = [];

            self.getConditions().each(function(condition){
                var field_id            = condition.getChildTrackerFieldId(),
                    field_value_id      = condition.getChildTrackerFieldValueId(),
                    field_label         = condition.getChildTrackerFieldLabel(),
                    field_value_label   = condition.getChildTrackerFieldValueLabel(),
                    tracker             = condition.getChildTrackerName();

                if (field_id === '' || field_value_id === '') {
                    alert(codendi.locales.tracker_trigger.save_missing_data);
                    return false;
                }

                triggering_fields.push({
                    "field_id"          : field_id,
                    "field_value_id"    : field_value_id,
                    "field_label"       : field_label,
                    "field_value_label" : field_value_label,
                    "tracker_name"      : tracker
                });
            });

            return triggering_fields;
        }

        function getTarget(self) {
             var field_id           = self.getTargetFieldId(),
                 field_value_id     = self.getTargetFieldValueId(),
                 field_label        = self.getTargetFieldLabel(),
                 field_value_label  = self.getTargetFieldValueLabel();

            if (field_id === '' || field_value_id === '') {
                alert(codendi.locales.tracker_trigger.save_missing_data);
                return false;
            }

            return {
                "field_id"          : field_id,
                "field_value_id"    : field_value_id,
                "field_label"       : field_label,
                "field_value_label" : field_value_label
            };
        }
    },

    setId : function(id) {
        this.id = id;
    },

    getId : function() {
        return this.id;
    }
});

tuleap.trackers.trigger.condition = Class.create({
    container : null,
    tracker_fields : [],
    id : null,

    initialize: function() {
        var condition_data = $$('#trigger_condition_template > tbody').first().innerHTML;

        $('trigger_condition_list').insert(condition_data);

        this.container = $$('#trigger_condition_list .trigger_condition').last();
        this.makeChildTrackerFieldsDynamic();
        this.makeChildTrackerFieldValuesDynamic();
    },

    activateDeleteButton : function(trigger) {
        var button      = this.container.down('.trigger_condition_remove'),
            container   = this.container,
            self        = this;

        Event.observe(button, 'click', function() {
            trigger.removeCondition(self);
        });
    },

    removeDeleteButton : function() {
        this.container.down('.trigger_condition_remove').remove();
    },

    addQuantitySelector : function() {
        var selector = $('trigger_condition_quantity');

        this.container.down('td').update(selector);
        this.container.writeAttribute('data-trigger-condition-initial', 'true')
    },

    makeChildTrackerFieldsDynamic : function() {
        var self = this;

        Event.observe($$('.trigger_condition_child_tracker_name').last(), 'change', function(event) {
            var tracker_id          = event.currentTarget.value,
                select_box_element  = event.currentTarget;

            self.removeAllOptions();
            self.addTrackerFieldsData(tracker_id, select_box_element);
        });
    },

    removeAllOptions : function() {
        var container   = this.container;

        removeExistingTrackerFields();
        this.removeExistingFieldValues();

        function removeExistingTrackerFields() {
            $$('.trigger-condition-tracker-field').each(function(field_value) {
                if (field_value.descendantOf(container)) {
                    field_value.remove();
                }
            });
        }
    },

    makeChildTrackerFieldValuesDynamic : function() {
        var container   = this.container,
            self        = this;

        Event.observe($$('.trigger_condition_child_tracker_field_name').last(), 'change', function(event) {
            var field_id    = event.currentTarget.value,
                tracker_id  = container.down('.trigger_condition_child_tracker_name').value;

            self.removeExistingFieldValues();
            self.addTrackerFieldValuesData(tracker_id, field_id);
        });
    },

    removeExistingFieldValues : function() {
        var container = this.container;

         $$('.trigger-condition-tracker-field-value').each(function(field_value) {
            if (field_value.descendantOf(container)) {
                field_value.remove();
            }
        });
    },

    addTrackerFieldsData : function(tracker_id, select_box_element) {
        var tracker_fields = this.tracker_fields;

        if (typeof(tracker_fields[tracker_id]) === 'undefined') {
            tracker_fields[tracker_id] = fetchFromFormData();
        }

        if (! tracker_fields[tracker_id]) {
            return;
        }

        populateTrackerFields(tracker_fields[tracker_id], select_box_element);

        function fetchFromFormData() {
            var form_data = tuleap.trackers.trigger.form_data;

            if (typeof form_data.triggers === 'undefined'
                    || typeof form_data.triggers[tracker_id] === 'undefined'
                    || typeof form_data.triggers[tracker_id].fields === 'undefined') {
                return false;
            }

            return form_data.triggers[tracker_id].fields;
        }

        function populateTrackerFields(fields, select_box_element) {
            $H(fields).each(function(field) {
                var option = createOption(field.value, "trigger-condition-tracker-field");
                select_box_element.next('select', '.trigger_condition_child_tracker_field_name').appendChild(option);
            });
        }

        function createOption(data, class_names) {
            return new Element('option', {
                "value" : data.id,
                "class" : class_names
            }).update(data.label);
        }
    },

    addTrackerFieldValuesData : function(tracker_id, field_id) {
        var tracker_fields = this.tracker_fields,
            field_values;

        if (typeof(tracker_fields[tracker_id]) === 'undefined'
                || typeof(tracker_fields[tracker_id][field_id]) === 'undefined'
                || typeof(tracker_fields[tracker_id][field_id].values) === 'undefined') {
            return;
        }

        field_values = tracker_fields[tracker_id][field_id].values;
        populateFieldValues(field_values, this.container);

        function populateFieldValues(fields, container) {
            $H(fields).each(function(field) {
                var option = createOption(field.value, "trigger-condition-tracker-field-value");
                container.down('.trigger_condition_child_tracker_field_value').appendChild(option);
            });
        }

        function createOption(data, class_names) {
            return new Element('option', {
                "value" : data.id,
                "class" : class_names
            }).update(data.label);
        }
    },

    makeQuantityDynamic : function() {
        updateQuantities();

        Event.observe($('trigger_condition_quantity'), 'change', function() {
            updateQuantities();
        });

        function updateQuantities() {
           $$('.trigger_condition_artifact_quantity_updater').each(function(span){
                var option = $('trigger_condition_quantity').options[$('trigger_condition_quantity').selectedIndex],
                    quantity_name = option.value,
                    operator = option.readAttribute('data-condition-operator'),
                    locales = codendi.locales.tracker_trigger;

                span.update(locales[operator] + ' ' + locales[quantity_name].name);
            });
        }
    },

    getChildTrackerFieldId : function() {
        return this.container.down('.trigger_condition_child_tracker_field_name').value;
    },

    getChildTrackerFieldValueId : function() {
        return this.container.down('.trigger_condition_child_tracker_field_value').value;
    },

    getChildTrackerFieldLabel : function() {
        var selector = this.container.down('.trigger_condition_child_tracker_field_name');

        return selector.options[selector.selectedIndex].innerHTML;
    },

    getChildTrackerFieldValueLabel : function() {
        var selector = this.container.down('.trigger_condition_child_tracker_field_value');

        return selector.options[selector.selectedIndex].innerHTML;
    },

    getChildTrackerName : function() {
        var selector = this.container.down('.trigger_condition_child_tracker_name');

        return selector.options[selector.selectedIndex].innerHTML;
    },

    getId : function() {
        return this.id;
    },

    setId : function(id) {
        this.id = id;
    },

    getContainer : function() {
        return this.container;
    }
});

tuleap.trackers.trigger.form_data = tuleap.trackers.trigger.form_data || {};
