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
    nodes: new Map(),
    trees: new Map(),
    getNode: function (field, is_child) {
        const field_id = parseInt(field, 10);
        if (!this.nodes.has(field_id)) {
            const node = new tuleap.tracker.RuleNode(field_id);
            this.nodes.set(field_id, node);
            if (!is_child) {
                this.trees.set(field_id, node);
            }
        }
        return this.nodes.get(field_id);
    },
    isTree: function (field) {
        return this.trees.has(field);
    },
    removeNodeFromTrees: function (field) {
        if (this.trees.has(field)) {
            delete this.trees.delete(field);
        }
    },
    reset: function () {
        this.nodes = new Map();
        this.trees = new Map();
    },
};

tuleap.tracker.RuleNode = function (field) {
    //Register event on the field
    var f = tuleap.tracker.fields.get(field);
    this.onchangeEvent = f.onchange.bind(f);
    f.element().addEventListener("change", this.onchangeEvent);

    return {
        field: field,
        targets: new Map(),
        addRule: function (source_value, target_field, target_value) {
            this.chainSourceAndTargetNodes(target_field);
            this.appendTargetValue(source_value, target_field, target_value);
        },
        chainSourceAndTargetNodes: function (target_field) {
            if (!this.targets.has(target_field)) {
                this.targets.set(target_field, {
                    field: tuleap.tracker.rule_forest.getNode(target_field, true),
                    values: {},
                });
            }
            // Once target is connected to source, it's no longer a tree root
            tuleap.tracker.rule_forest.removeNodeFromTrees(target_field);
        },
        appendTargetValue: function (source_value, target_field, target_value) {
            if (!this.targets.get(target_field).values[source_value]) {
                this.targets.get(target_field).values[source_value] = [];
            }
            this.targets.get(target_field).values[source_value].push(target_value);
        },
        process: function () {
            //retrieve selected source values
            var selected_sources = [];
            Array.from(tuleap.tracker.fields.get(this.field).element().options).forEach(
                function (option) {
                    if (option.selected) {
                        selected_sources.push(option.value);
                    }
                },
            );

            //Store only if we are root (else already stored before we reach this field)
            if (tuleap.tracker.rule_forest.isTree(this.field)) {
                tuleap.tracker.fields.get(this.field).updateSelectedState(selected_sources);
            }

            const unchanged_value = "-1";
            this.targets.forEach(function (transitions, target_field_id) {
                //retrieve options of the target
                const target_field = tuleap.tracker.fields.get(target_field_id);
                var target_options = target_field.options;

                //Build the new options accordingly to the rules
                var new_target_options = new Map();
                selected_sources.forEach(function (selected_value) {
                    if (transitions.values[selected_value]) {
                        transitions.values[selected_value].forEach(function (target_value) {
                            new_target_options.set(
                                parseInt(target_value, 10),
                                Object.clone(target_options.get(target_value)),
                            );
                        });
                    }

                    if (selected_value === unchanged_value) {
                        new_target_options.set(
                            parseInt(unchanged_value, 10),
                            new Option(unchanged_value, unchanged_value),
                        );
                    }
                });

                //Force field to new options
                tuleap.tracker.fields.get(target_field_id).force(new_target_options);

                //Chain the process
                tuleap.tracker.rule_forest.getNode(target_field_id).process();
            });
        },
    };
};

tuleap.tracker.Field = function (id, name, label) {
    return {
        id: id,
        name: name,
        label: label,
        _highlight: null,
        options: new Map(),
        addOption: function (text, value, selected, dataset) {
            this.options.set(parseInt(value, 10), {
                value: value,
                text: text,
                selected: selected,
                dataset: dataset,
            });
            return this;
        },
        force: function (new_options) {
            const el = this.element();
            //Clear the field
            var len = el.options.length;
            for (var i = len; i >= 0; i--) {
                el.options[i] = null;
            }

            //Revert selected state for all options
            this.updateSelectedState();

            const at_least_one_new_value_selected = Array.from(new_options.values()).some(
                (option) => option.selected,
            );
            //Add options
            this.options.forEach((option, value) => {
                if (!new_options.has(value)) {
                    return;
                }
                const opt = new Option(option.text, option.value);
                if (option.dataset) {
                    Object.keys(option.dataset).forEach((data_attribute_name) => {
                        opt.setAttribute(data_attribute_name, option.dataset[data_attribute_name]);
                    });
                }
                if (new_options.get(value).selected) {
                    opt.selected = true;
                    option.selected = true;
                }
                if (!at_least_one_new_value_selected && new_options.size === 1) {
                    //Select the only available option
                    opt.selected = true;
                    option.selected = true;
                }
                el.options[el.options.length] = opt;
            });

            el.dispatchEvent(new Event("change"));
            //We've finished. Highlight the field to indicate to the user that it has changed (or not)
            this.highlight();
        },
        highlight: function () {
            if (this._highlight) {
                this.removeHighlight();
            }
            if (this.element().classList.contains("list-picker-hidden-accessible")) {
                this.element().nextElementSibling.classList.add(
                    "list-picker-field-dependency-highlight",
                );
            } else {
                this.element().classList.add("tracker-field-dependency-highlight");
            }
            this._highlight = setTimeout(this.removeHighlight.bind(this), 1000);
        },
        removeHighlight: function () {
            if (this.element().classList.contains("list-picker-hidden-accessible")) {
                this.element().nextElementSibling.classList.remove(
                    "list-picker-field-dependency-highlight",
                );
            } else {
                this.element().classList.remove("tracker-field-dependency-highlight");
            }
            clearTimeout(this._highlight);
        },
        updateSelectedState: function (selected_values) {
            //Revert selected state for all options
            this.options.forEach(function (option) {
                option.selected = selected_values && selected_values[option.value] ? true : false;
            });
        },
        element: function () {
            var id_sb = "tracker_field_" + this.id;
            return document.getElementById(id_sb);
        },
        onchange: function () {
            const el = this.element();
            //Store the selected state
            for (const html_option of el.options) {
                const bind_value_id = Number.parseInt(html_option.value, 10);
                const state_option = this.options.get(bind_value_id);
                if (state_option) {
                    state_option.selected = html_option.selected;
                }
            }
            //Process rules
            tuleap.tracker.rule_forest.getNode(this.id).process();
        },
    };
};

tuleap.tracker.fields = {
    fields: {},
    add: function (id, name, label) {
        this.fields[id] = new tuleap.tracker.Field(id, name, label);
        return this.fields[id];
    },
    get: function (id) {
        return this.fields[id];
    },
};

tuleap.tracker.runTrackerFieldDependencies = function () {
    //Load rules definitions
    //Only if fields and values exist
    tuleap.tracker.rule_forest.reset();
    tuleap.tracker.rules_definitions.forEach(function (rule_definition) {
        if (
            rule_definition.source_field !== rule_definition.target_field &&
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
                    rule_definition.target_value,
                );
        }
    });

    //Apply the initial rules
    tuleap.tracker.rule_forest.trees.forEach(function (rule_node) {
        rule_node.process();
    });
};
