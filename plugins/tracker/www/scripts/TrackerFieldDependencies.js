/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

// Define namespace to prevent clashes
var tuleap = tuleap || {};
tuleap.tracker = tuleap.tracker || {};

tuleap.tracker.rules_definitions = [];

tuleap.tracker.rule_forest = {
    nodes: {},
    trees: {},
    getNode: function(field, is_child) {
        if (!this.nodes[field]) {
            this.nodes[field] = new tuleap.tracker.RuleNode(field);
            if (!is_child) {
                this.trees[field] = this.nodes[field];
            }
        }
        return this.nodes[field];
    },
    isTree: function(field) {
        return this.trees[field] ? true : false;
    },
    removeNodeFromTrees: function(field) {
        if (this.trees[field]) {
            delete this.trees[field];
        }
    },
    reset: function() {
        this.nodes = {};
        this.trees = {};
    }
};

tuleap.tracker.RuleNode = Class.create({
    initialize: function(field) {
        this.field = field;
        this.targets = {};

        //Register event on the field
        var f = tuleap.tracker.fields.get(this.field);
        this.onchangeEvent = f.onchange.bind(f);
        f.element().observe("change", this.onchangeEvent);
    },
    addRule: function(source_value, target_field, target_value) {
        this.chainSourceAndTargetNodes(target_field);
        this.appendTargetValue(source_value, target_field, target_value);
    },
    chainSourceAndTargetNodes: function(target_field) {
        if (!this.targets[target_field]) {
            this.targets[target_field] = {
                field: tuleap.tracker.rule_forest.getNode(target_field, true),
                values: {}
            };
        }
        // Once target is connected to source, it's no longer a tree root
        tuleap.tracker.rule_forest.removeNodeFromTrees(target_field);
    },
    appendTargetValue: function(source_value, target_field, target_value) {
        if (!this.targets[target_field].values[source_value]) {
            this.targets[target_field].values[source_value] = [];
        }
        this.targets[target_field].values[source_value].push(target_value);
    },
    process: function() {
        //retrieve selected source values
        var selected_sources = tuleap.tracker.fields
            .get(this.field)
            .element()
            .getValue();

        if (!Object.isArray(selected_sources)) {
            selected_sources = [selected_sources];
        }

        //Store only if we are root (else already stored before we reach this field)
        if (tuleap.tracker.rule_forest.isTree(this.field)) {
            tuleap.tracker.fields.get(this.field).updateSelectedState(selected_sources);
        }

        const unchanged_value = "-1";
        //for each targets of this field
        $H(this.targets).each(function(target) {
            var target_field_id = target.key;
            var transitions = target.value;

            //retrieve options of the target
            const target_field = tuleap.tracker.fields.get(target_field_id);
            var target_options = target_field.options;

            //Build the new options accordingly to the rules
            var new_target_options = {};
            selected_sources.each(function(selected_value) {
                if (transitions.values[selected_value]) {
                    transitions.values[selected_value].each(function(target_value) {
                        new_target_options[target_value] = Object.clone(
                            target_options[target_value]
                        );
                    });
                }

                if (selected_value === unchanged_value) {
                    new_target_options[unchanged_value] = new Option(
                        unchanged_value,
                        unchanged_value
                    );
                }
            });

            //Force field to new options
            tuleap.tracker.fields.get(target_field_id).force(new_target_options);

            //Chain the process
            tuleap.tracker.rule_forest.getNode(target_field_id).process();
        });
    }
});

tuleap.tracker.Field = Class.create({
    initialize: function(id, name, label) {
        this.id = id;
        this.name = name;
        this.label = label;
        this._highlight = null;
        this.options = {};
    },
    addOption: function(text, value, selected) {
        this.options[value] = {
            value: value,
            text: text,
            selected: selected
        };
        return this;
    },
    force: function(new_options) {
        var el = this.element();
        //Clear the field
        var len = el.options.length;
        for (var i = len; i >= 0; i--) {
            el.options[i] = null;
        }

        //Revert selected state for all options
        this.updateSelectedState();

        //Add options
        $H(this.options)
            .values()
            .each(
                function(option) {
                    if (
                        $H(new_options)
                            .keys()
                            .find(function(value) {
                                return value == option.value;
                            })
                    ) {
                        var opt = new Option(option.text, option.value);
                        if (new_options[option.value].selected) {
                            opt.selected = true;
                            //Store the selected state for this option
                            this.options[option.value].selected = true;
                        }
                        el.options[el.options.length] = opt;
                    }
                }.bind(this)
            );

        //We've finished. Highlight the field to indicate to the user that it has changed (or not)
        this.highlight();
    },
    highlight: function() {
        if (this._highlight) {
            this.removeHighlight();
        }
        this.element().classList.add("tracker-field-dependency-highlight");
        this._highlight = setTimeout(this.removeHighlight.bind(this), 1000);
    },
    removeHighlight: function() {
        this.element().classList.remove("tracker-field-dependency-highlight");
        clearTimeout(this._highlight);
    },
    updateSelectedState: function(selected_values) {
        //Revert selected state for all options
        $H(this.options)
            .keys()
            .each(
                function(value) {
                    this.options[value].selected =
                        selected_values && selected_values[value] ? true : false;
                }.bind(this)
            );
    },
    element: function() {
        var id_sb = "tracker_field_" + this.id;
        return $(id_sb);
    },
    onchange: function() {
        var el = this.element();
        //Store the selected state
        var len = el.options.length;

        for (var i = 0; i < len; ++i) {
            if (typeof this.options[el.options[i].value] !== "undefined") {
                this.options[el.options[i].value].selected = el.options[i].selected;
            }
        }
        //Process rules
        tuleap.tracker.rule_forest.getNode(this.id).process();
    }
});

tuleap.tracker.fields = {
    fields: {},
    add: function(id, name, label) {
        this.fields[id] = new tuleap.tracker.Field(id, name, label);
        return this.fields[id];
    },
    get: function(id) {
        return this.fields[id];
    }
};

tuleap.tracker.runTrackerFieldDependencies = function() {
    //Load rules definitions
    //Only if fields and values exist
    tuleap.tracker.rule_forest.reset();
    tuleap.tracker.rules_definitions.each(function(rule_definition) {
        if (
            rule_definition.source_field != rule_definition.target_field &&
            tuleap.tracker.fields.get(rule_definition.source_field) &&
            tuleap.tracker.fields.get(rule_definition.target_field) &&
            tuleap.tracker.fields.get(rule_definition.source_field).element() &&
            tuleap.tracker.fields.get(rule_definition.target_field).element()
        ) {
            tuleap.tracker.rule_forest
                .getNode(rule_definition.source_field)
                .addRule(
                    rule_definition.source_value,
                    rule_definition.target_field,
                    rule_definition.target_value
                );
        }
    });

    //Apply the initial rules
    $H(tuleap.tracker.rule_forest.trees).each(function(rule_node) {
        rule_node.value.process();
    });
};

document.addEventListener("DOMContentLoaded", tuleap.tracker.runTrackerFieldDependencies);
