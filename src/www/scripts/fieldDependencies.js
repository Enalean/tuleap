/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Originally written by Nicolas Terray, 2006
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

// Define namespace to prevent clashes
var codendi = codendi || {};
codendi.trackerv3 = codendi.trackerv3 || {};

//==============================================================================
//==============================================================================

//{{{                         Client part

//==============================================================================
//==============================================================================

// The highlight color for the Yellow Fade Technique
var HIGHLIGHT_STARTCOLOR = "#ffff99";

codendi.trackerv3.rules_definitions = [];

codendi.trackerv3.rule_forest = {
    nodes: {},
    trees: {},
    getNode: function(field, is_child) {
        if (!this.nodes[field]) {
            this.nodes[field] = new codendi.trackerv3.RuleNode(field);
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
    }
};

/**
 * A Rule
 */
codendi.trackerv3.RuleNode = Class.create({
    initialize: function(field) {
        this.field = field;
        this.targets = {};

        //Register event on the field
        var f = codendi.trackerv3.fields.get(this.field);
        if (f.element().nodeName != "SPAN") {
            this.onchangeEvent = f.onchange.bind(f);
            f.element().observe("change", this.onchangeEvent);
        }
    },
    addRule: function(source_value, target_field, target_value) {
        this.chainSourceAndTargetNodes(target_field);
        this.appendTargetValue(source_value, target_field, target_value);
    },
    chainSourceAndTargetNodes: function(target_field) {
        if (!this.targets[target_field]) {
            this.targets[target_field] = {
                field: codendi.trackerv3.rule_forest.getNode(target_field, true),
                values: {}
            };
        }
        // Once target is connected to source, it's no longer a tree root
        codendi.trackerv3.rule_forest.removeNodeFromTrees(target_field);
    },
    appendTargetValue: function(source_value, target_field, target_value) {
        if (!this.targets[target_field].values[source_value]) {
            this.targets[target_field].values[source_value] = [];
        }
        this.targets[target_field].values[source_value].push(target_value);
    },
    process: function() {
        //retrieve selected source values
        var el = codendi.trackerv3.fields.get(this.field).element();
        if (el.nodeName == "SPAN") {
            //in case of MB, the returned value is a list a value ids (i.e: v_id1,v_id2,...)
            var e = el.innerHTML;
            var c = ",";
            if (e.indexOf(c) != -1) {
                var selected_sources = e.split(",");
            } else {
                var selected_sources = el.innerHTML;
            }
        } else {
            var selected_sources = el.getValue();
        }
        if (!Object.isArray(selected_sources)) {
            selected_sources = [selected_sources];
        }

        //Store only if we are root (else already stored before we reach this field)
        if (codendi.trackerv3.rule_forest.isTree(this.field)) {
            codendi.trackerv3.fields.get(this.field).updateSelectedState(selected_sources);
        }

        //for each targets of this field
        $H(this.targets).each(function(target) {
            var target_field_id = target.key;
            var transitions = target.value;

            //retrieve options of the target
            var target_options = codendi.trackerv3.fields.get(target_field_id).options;

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
            });

            //Force field to new options
            codendi.trackerv3.fields.get(target_field_id).force(new_target_options);

            //Chain the process
            codendi.trackerv3.rule_forest.getNode(target_field_id).process();
        });
    }
});

/**
 * Codendi field
 */
codendi.trackerv3.Field = Class.create({
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
        //We store the actual effect to not load a lot of new objects
        //and also to prevent locks of the background color
        if (!this._highlight) {
            this._highlight = new Effect.Highlight(this.element(), {
                startcolor: HIGHLIGHT_STARTCOLOR
            });
        } else {
            this._highlight.start(this._highlight.options);
        }
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
        return $(this.name);
    },
    onchange: function() {
        var el = this.element();

        //Store the selected state
        var len = el.options.length;
        for (var i = 0; i < len; ++i) {
            this.options[el.options[i].value].selected = el.options[i].selected;
        }
        //Process rules
        codendi.trackerv3.rule_forest.getNode(this.id).process();
    }
});

codendi.trackerv3.fields = {
    fields: {},
    add: function(id, name, label) {
        this.fields[id] = new codendi.trackerv3.Field(id, name, label);
        return this.fields[id];
    },
    get: function(id) {
        return this.fields[id];
    }
};

document.observe("dom:loaded", function() {
    //Load rules definitions
    //Only if fields and values exist
    codendi.trackerv3.rules_definitions.each(function(rule_definition) {
        if (
            rule_definition.source_field != rule_definition.target_field &&
            codendi.trackerv3.fields.get(rule_definition.source_field) &&
            codendi.trackerv3.fields.get(rule_definition.target_field) &&
            codendi.trackerv3.fields.get(rule_definition.source_field).element() &&
            codendi.trackerv3.fields.get(rule_definition.target_field).element()
        ) {
            codendi.trackerv3.rule_forest
                .getNode(rule_definition.source_field)
                .addRule(
                    rule_definition.source_value,
                    rule_definition.target_field,
                    rule_definition.target_value
                );
        }
    });

    //Apply the initial rules
    $H(codendi.trackerv3.rule_forest.trees).each(function(rule_node) {
        rule_node.value.process();
    });

    var hexChars = "0123456789ABCDEF";
    function Dec2Hex(Dec) {
        var a = Dec % 16;
        var b = (Dec - a) / 16;
        var hex = "" + hexChars.charAt(b) + hexChars.charAt(a);
        return hex;
    }
    var re = new RegExp(/rgb\([^0-9]*([0-9]+)[^0-9]*([0-9]+)[^0-9]*([0-9]+)\)/); //Fx returns rgb(123, 64, 32) instead of hexa color
    if ((m = re.exec(HIGHLIGHT_STARTCOLOR))) {
        var r = m[1] || null;
        var g = m[2] || null;
        var b = m[3] || null;
        if (r && g && b) {
            HIGHLIGHT_STARTCOLOR = "#" + Dec2Hex(r) + Dec2Hex(g) + Dec2Hex(b);
        }
    }
    //}}}
});

//}}}

//==============================================================================
//==============================================================================

//{{{                         Admin part

//==============================================================================
//==============================================================================

/**
 * Assign handlers to inputs
 */
function buildAdminUI() {
    admin_feedback_field_dependencies = $$(".feedback_field_dependencies");

    $("edit_rule_form").onsubmit = function() {
        $("save").value = "save";
        $("direction_type").value = admin_selected_type;
        $("value").value = admin_selected_value;
        $("source_field_hidden").value = $F("source_field");
        $("target_field_hidden").value = $F("target_field");
        $("reset_btn").disabled = $("save_btn").disabled = "disabled";
        return true;
    };

    $("reset_btn").observe("click", function(evt) {
        admin_is_in_edit_mode = false;
        if (admin_selected_type == "target") {
            admin_forceTargetValue($F("source_field"), $F("target_field"), admin_selected_value);
        } else {
            admin_forceSourceValue($F("source_field"), $F("target_field"), admin_selected_value);
        }
        Element.hide("save_panel");
        $("source_field").disabled = "";
        $("target_field").disabled = "";

        Event.stop(evt);
        return false;
    });

    Element.hide("save_panel");
}

/**
 * Extract informations from an id :
 * ex: We want to retrieve informations from
 *     id "target_<source_field>_<target_field>_<value>_chk"
 *     We know that the user has previously selected
 *     the source value <selected_value>.
 *     The informations will be :
 *        {
 *            type:            'target',
 *            source_field_id: <source_field>,
 *            target_field_id: <target_field>,
 *            source_value_id: <selected_value>,
 *            target_value_id: <value>
 *        }
 */
function admin_getInfosFromId(id) {
    var p1 = id.indexOf("_");
    var p2 = id.substring(p1 + 1).indexOf("_");
    var p3 = id.substring(p1 + 1 + p2 + 1).indexOf("_");
    var p4 = id.substring(p1 + 1 + p2 + 1 + p3 + 1).indexOf("_");
    var ret = {
        type: id.substr(0, p1),
        source_field_id: id.substr(p1 + 1, p2),
        target_field_id: id.substr(p1 + 1 + p2 + 1, p3),
        source_value_id: admin_selected_value,
        target_value_id: admin_selected_value
    };
    ret[ret.type + "_value_id"] = id.substr(p1 + 1 + p2 + 1 + p3 + 1, p4 != -1 ? p4 : id.length);
    return ret;
}

var admin_is_in_edit_mode = false;
var admin_nb_diff;
var admin_selected_value;
var admin_selected_type;

/**
 * function to remove the feedback
 */
var admin_feedback_field_dependencies = [];
var admin_feedback_field_dependencies_effect_done = false;
function admin_removeFeedback() {
    if (admin_feedback_field_dependencies.length > 0) {
        admin_feedback_field_dependencies[0].parentNode.parentNode.parentNode.removeChild(
            admin_feedback_field_dependencies[0].parentNode.parentNode
        );
        admin_feedback_field_dependencies_effect_done = true;
        admin_feedback_field_dependencies = [];
    }
}

/**
 * Callback for (un)checked checkboxes
 */
function admin_checked(id) {
    admin_removeFeedback();
    var checkbox = admin_getInfosFromId(id);
    //We're going to edit mode if we are not yet
    if (!admin_is_in_edit_mode) {
        $("source_field").disabled = "disabled";
        $("target_field").disabled = "disabled";
        admin_is_in_edit_mode = true;
        admin_nb_diff = 0;
        Element.show("save_panel");
    }

    var checked = $F(id);
    //boxitem and arrow follow the state of the corresponding checkbox
    if (checked) {
        Element.addClassName(
            checkbox.type +
                "_" +
                checkbox.source_field_id +
                "_" +
                checkbox.target_field_id +
                "_" +
                checkbox[checkbox.type + "_value_id"],
            "boxhighlight"
        );
        Element.setStyle(
            checkbox.type +
                "_" +
                checkbox.source_field_id +
                "_" +
                checkbox.target_field_id +
                "_" +
                checkbox[checkbox.type + "_value_id"] +
                "_arrow",
            { visibility: "visible" }
        );
    } else {
        Element.removeClassName(
            checkbox.type +
                "_" +
                checkbox.source_field_id +
                "_" +
                checkbox.target_field_id +
                "_" +
                checkbox[checkbox.type + "_value_id"],
            "boxhighlight"
        );
        Element.setStyle(
            checkbox.type +
                "_" +
                checkbox.source_field_id +
                "_" +
                checkbox.target_field_id +
                "_" +
                checkbox[checkbox.type + "_value_id"] +
                "_arrow",
            { visibility: "hidden" }
        );
    }
    //Does a rule exist ?
    var rule_exists = codendi.trackerv3.rules_definitions.find(function(definition) {
        return (
            definition.source_field == checkbox.source_field_id &&
            definition.target_field == checkbox.target_field_id &&
            definition.target_value == checkbox.target_value_id &&
            definition.source_value == checkbox.source_value_id
        );
    });
    if ((rule_exists && checked) || (!rule_exists && !checked)) {
        //Bug here!
        // NTY 20060210: The initial behaviour was to be able to detect when a user
        // change rules to initial rules (click-declick) It doesn't work for now,
        // Therefore we don't do anything interesting here.
        admin_nb_diff--;
        //admin_nb_diff++;
    } else {
        admin_nb_diff++;
    }
    //The user is leaving the edit mode
    if (admin_nb_diff === 0) {
        can_leave_edit_mode = true;
        codendi.trackerv3.rules_definitions.each(function(rule_definition) {
            var checkbox_name =
                checkbox.type +
                "_" +
                rule_definition.source_field +
                "_" +
                rule_definition.target_field +
                "_" +
                rule_definition[checkbox.type + "_value"] +
                "_chk";
            if (
                rule_definition.source_field == checkbox.source_field_id &&
                rule_definition.target_field == checkbox.target_field_id &&
                rule_definition[admin_selected_type + "_value"] == admin_selected_value
            ) {
                if (!$F(checkbox_name)) {
                    can_leave_edit_mode = false;
                    throw $break;
                }
            }
        });
        if (can_leave_edit_mode) {
            $("source_field").disabled = "";
            $("target_field").disabled = "";
            admin_is_in_edit_mode = false;
            Element.hide("save_panel");
        }
    }
}

function admin_selectTargetEvent(element) {
    var link = admin_getInfosFromId($(element).up("tr").id);
    admin_selectTargetValue(
        link.source_field_id,
        link.target_field_id,
        link[link.type + "_value_id"]
    );
    return false;
}

function admin_selectTargetValue(source_field_id, target_field_id, target_value_id) {
    admin_removeFeedback();
    if (admin_is_in_edit_mode) {
        if (confirm("Save modifications ?")) {
            $("save_btn").click();
        }
    }
    if (!admin_is_in_edit_mode) {
        admin_forceTargetValue(source_field_id, target_field_id, target_value_id);
    }
}
function admin_forceTargetValue(source_field_id, target_field_id, target_value_id) {
    //Select the target
    admin_selected_value = target_value_id;
    admin_selected_type = "target";

    $H(codendi.trackerv3.fields.get(target_field_id).options).each(function(opt) {
        Element.setStyle(
            "target_" + source_field_id + "_" + target_field_id + "_" + opt.value.value + "_chk",
            { visibility: "hidden" }
        );
        Element.removeClassName(
            "target_" + source_field_id + "_" + target_field_id + "_" + opt.value.value,
            "boxhighlight"
        );
        Element.setStyle(
            "target_" + source_field_id + "_" + target_field_id + "_" + opt.value.value + "_arrow",
            { visibility: "hidden" }
        );
    });
    Element.addClassName(
        "target_" + source_field_id + "_" + target_field_id + "_" + target_value_id,
        "boxhighlight"
    );
    Element.setStyle(
        "target_" + source_field_id + "_" + target_field_id + "_" + target_value_id + "_arrow",
        { visibility: "visible" }
    );

    //Select sources
    $H(codendi.trackerv3.fields.get(source_field_id).options).each(function(opt) {
        Element.setStyle(
            "source_" + source_field_id + "_" + target_field_id + "_" + opt.value.value + "_chk",
            { visibility: "visible" }
        );
        //Does a rule exist ?
        if (
            codendi.trackerv3.rules_definitions.find(function(definition) {
                return (
                    definition.source_field == source_field_id &&
                    definition.target_field == target_field_id &&
                    definition.target_value == target_value_id &&
                    definition.source_value == opt.value.value
                );
            })
        ) {
            Element.addClassName(
                "source_" + source_field_id + "_" + target_field_id + "_" + opt.value.value,
                "boxhighlight"
            );
            $(
                "source_" + source_field_id + "_" + target_field_id + "_" + opt.value.value + "_chk"
            ).checked = "checked";
            Element.setStyle(
                "source_" +
                    source_field_id +
                    "_" +
                    target_field_id +
                    "_" +
                    opt.value.value +
                    "_arrow",
                { visibility: "visible" }
            );
        } else {
            Element.removeClassName(
                "source_" + source_field_id + "_" + target_field_id + "_" + opt.value.value,
                "boxhighlight"
            );
            $(
                "source_" + source_field_id + "_" + target_field_id + "_" + opt.value.value + "_chk"
            ).checked = "";
            Element.setStyle(
                "source_" +
                    source_field_id +
                    "_" +
                    target_field_id +
                    "_" +
                    opt.value.value +
                    "_arrow",
                { visibility: "hidden" }
            );
        }
    });
}

function admin_selectSourceEvent(element) {
    var link = admin_getInfosFromId($(element).up("tr").id);
    admin_selectSourceValue(
        link.source_field_id,
        link.target_field_id,
        link[link.type + "_value_id"]
    );
    return false;
}
function admin_selectSourceValue(source_field_id, target_field_id, source_value_id) {
    admin_removeFeedback();
    if (admin_is_in_edit_mode) {
        if (confirm("Save modifications ?")) {
            $("save_btn").click();
        }
    }
    if (!admin_is_in_edit_mode) {
        admin_forceSourceValue(source_field_id, target_field_id, source_value_id);
    }
}
function admin_forceSourceValue(source_field_id, target_field_id, source_value_id) {
    //Select the source
    admin_selected_value = source_value_id;
    admin_selected_type = "source";

    $H(codendi.trackerv3.fields.get(source_field_id).options).each(function(opt) {
        Element.setStyle(
            "source_" + source_field_id + "_" + target_field_id + "_" + opt.value.value + "_chk",
            { visibility: "hidden" }
        );
        Element.removeClassName(
            "source_" + source_field_id + "_" + target_field_id + "_" + opt.value.value,
            "boxhighlight"
        );
        Element.setStyle(
            "source_" + source_field_id + "_" + target_field_id + "_" + opt.value.value + "_arrow",
            { visibility: "hidden" }
        );
    });
    Element.addClassName(
        "source_" + source_field_id + "_" + target_field_id + "_" + source_value_id,
        "boxhighlight"
    );
    Element.setStyle(
        "source_" + source_field_id + "_" + target_field_id + "_" + source_value_id + "_arrow",
        { visibility: "visible" }
    );

    //Select targets
    $H(codendi.trackerv3.fields.get(target_field_id).options).each(function(opt) {
        Element.setStyle(
            "target_" + source_field_id + "_" + target_field_id + "_" + opt.value.value + "_chk",
            { visibility: "visible" }
        );
        //Does a rule exist ?
        if (
            codendi.trackerv3.rules_definitions.find(function(definition) {
                return (
                    definition.source_field == source_field_id &&
                    definition.target_field == target_field_id &&
                    definition.source_value == source_value_id &&
                    definition.target_value == opt.value.value
                );
            })
        ) {
            Element.addClassName(
                "target_" + source_field_id + "_" + target_field_id + "_" + opt.value.value,
                "boxhighlight"
            );
            $(
                "target_" + source_field_id + "_" + target_field_id + "_" + opt.value.value + "_chk"
            ).checked = "checked";
            Element.setStyle(
                "target_" +
                    source_field_id +
                    "_" +
                    target_field_id +
                    "_" +
                    opt.value.value +
                    "_arrow",
                { visibility: "visible" }
            );
        } else {
            Element.removeClassName(
                "target_" + source_field_id + "_" + target_field_id + "_" + opt.value.value,
                "boxhighlight"
            );
            $(
                "target_" + source_field_id + "_" + target_field_id + "_" + opt.value.value + "_chk"
            ).checked = "";
            Element.setStyle(
                "target_" +
                    source_field_id +
                    "_" +
                    target_field_id +
                    "_" +
                    opt.value.value +
                    "_arrow",
                { visibility: "hidden" }
            );
        }
    });
}

//}}}
