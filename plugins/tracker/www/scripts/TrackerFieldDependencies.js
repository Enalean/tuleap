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
var codendi = codendi || { };
codendi.tracker = codendi.tracker || { };
//==============================================================================
//==============================================================================

//{{{                         Client part

//==============================================================================
//==============================================================================


// The highlight color for the Yellow Fade Technique
var HIGHLIGHT_STARTCOLOR = '#ffff99';

codendi.tracker.rules_definitions = Array();

codendi.tracker.rule_forest = {
    nodes: {},
    trees: {},
    getNode: function(field, is_child) {
        if (!this.nodes[field]) {
            this.nodes[field] = new codendi.tracker.RuleNode(field);
            if (!is_child) {
                this.trees[field] = this.nodes[field];
            }
        }
        return this.nodes[field];
    },
    isTree: function(field) {
        return this.trees[field] ? true : false;
    },
    removeNodeFromTrees: function (field) {
        if (this.trees[field]) {
            delete this.trees[field];
        }
    },
    reset: function() {
        this.nodes = {};
        this.trees = {};
    }
};

/**
 * A Rule
 */
codendi.tracker.RuleNode = Class.create({
    initialize:function(field) {
        this.field = field;
        this.targets = {};
        
        //Register event on the field
        var f = codendi.tracker.fields.get(this.field);
        this.onchangeEvent = f.onchange.bind(f);
        f.element().observe('change', this.onchangeEvent);
    },
    addRule: function(source_value, target_field, target_value) {
        this.chainSourceAndTargetNodes(target_field);
        this.appendTargetValue(source_value, target_field, target_value);
    },
    chainSourceAndTargetNodes: function(target_field) {
        if (!this.targets[target_field]) {
            this.targets[target_field] = {
                field: codendi.tracker.rule_forest.getNode(target_field, true),
                values: {}
            }
        }
        // Once target is connected to source, it's no longer a tree root
        codendi.tracker.rule_forest.removeNodeFromTrees(target_field);
    },
    appendTargetValue: function (source_value, target_field, target_value) {
        if (!this.targets[target_field].values[source_value]) {
            this.targets[target_field].values[source_value] = [];
        }
        this.targets[target_field].values[source_value].push(target_value);
    },
    process: function() {
        //retrieve selected source values
        var selected_sources = codendi.tracker.fields.get(this.field).element().getValue();

        //var selected_sources = codendi.tracker.fields.get(this.field)./*element().getValue()*/label;
        if (!Object.isArray(selected_sources)) {
            selected_sources = [selected_sources];
        }
        
        //Store only if we are root (else already stored before we reach this field)
        if (codendi.tracker.rule_forest.isTree(this.field)) {
            codendi.tracker.fields.get(this.field).updateSelectedState(selected_sources);
        }
        
        //for each targets of this field
        $H(this.targets).each(function (target) {
            var target_field_id = target.key;
            var transitions = target.value;
            
            //retrieve options of the target
            var target_options = codendi.tracker.fields.get(target_field_id).options;
            
            //Build the new options accordingly to the rules
            var new_target_options = {};
            selected_sources.each(function (selected_value) {
                if (transitions.values[selected_value]) {
                    transitions.values[selected_value].each(function (target_value) {
                        new_target_options[target_value] = Object.clone(target_options[target_value]);
                    });
                }
            });
            //Force field to new options
            codendi.tracker.fields.get(target_field_id).force(new_target_options);
            
            //Chain the process
            codendi.tracker.rule_forest.getNode(target_field_id).process();
        });
    }
});

/**
 * Codendi field
 */
codendi.tracker.Field = Class.create({
    initialize: function (id, name, label) {
        this.id              = id;
        this.name            = name;
        this.label           = label;
        this._highlight      = null;
        this.options         = {}
    },
    addOption: function(text, value, selected) {
        this.options[value] = {
                value:value,
                text:text,
                selected:selected
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
        $H(this.options).values().each((function (option) {
            if ($H(new_options).keys().find(function(value) { return value == option.value; })) {
                var opt = new Option(option.text, option.value);
                if (new_options[option.value].selected) {
                    opt.selected = true;
                    //Store the selected state for this option
                    this.options[option.value].selected = true;
                }
                el.options[el.options.length] = opt;
            }
        }).bind(this));
        
        //We've finished. Highlight the field to indicate to the user that it has changed (or not)
        this.highlight();
    },
    highlight: function() {
        //We store the actual effect to not load a lot of new objects
        //and also to prevent locks of the background color
        if (!this._highlight) {
            this._highlight = new Effect.Highlight(this.element(), {startcolor:HIGHLIGHT_STARTCOLOR});
        } else {
            this._highlight.start(this._highlight.options);
        }
    },
    updateSelectedState: function(selected_values) {
        //Revert selected state for all options
        $H(this.options).keys().each((function (value) {
                this.options[value].selected = selected_values && selected_values[value] ? true : false;
        }).bind(this));
    },
    element: function() {
        var id_sb = 'tracker_field_'+ this.id;
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
        codendi.tracker.rule_forest.getNode(this.id).process();
    }
});

codendi.tracker.fields = {
    fields: {},
    add: function(id, name, label) {
        this.fields[id] = new codendi.tracker.Field(id, name, label);
        return this.fields[id];
    },
    get: function(id) {
        return this.fields[id]
    }
};

codendi.tracker.runTrackerFieldDependencies = function() {
        //try {
    //Load rules definitions
    //Only if fields and values exist
    codendi.tracker.rule_forest.reset();
    codendi.tracker.rules_definitions.each(function (rule_definition) {
        if (rule_definition.source_field != rule_definition.target_field 
            && codendi.tracker.fields.get(rule_definition.source_field) 
            && codendi.tracker.fields.get(rule_definition.target_field)
            && codendi.tracker.fields.get(rule_definition.source_field).element()
            && codendi.tracker.fields.get(rule_definition.target_field).element()
        ) {

            codendi.tracker.rule_forest.getNode(rule_definition.source_field)
                                       .addRule(rule_definition.source_value,
                                                rule_definition.target_field,
                                                rule_definition.target_value
                                                );
        }
    });
    
    //Apply the initial rules
    $H(codendi.tracker.rule_forest.trees).each(function (rule_node) {      
        rule_node.value.process();
    });

    var hexChars = "0123456789ABCDEF";
    function Dec2Hex (Dec) { 
        var a = Dec % 16; 
        var b = (Dec - a)/16; 
        var hex = "" + hexChars.charAt(b) + hexChars.charAt(a); 
        return hex;
    }
    var re = new RegExp(/rgb\([^0-9]*([0-9]+)[^0-9]*([0-9]+)[^0-9]*([0-9]+)\)/); //Fx returns rgb(123, 64, 32) instead of hexa color
    if (m = re.exec(HIGHLIGHT_STARTCOLOR)) {
        var r = m[1] || null;
        var g = m[2] || null;
        var b = m[3] || null;
        if (r && g && b) {
            HIGHLIGHT_STARTCOLOR = '#'+Dec2Hex(r)+Dec2Hex(g)+Dec2Hex(b);
        }
    }
    //}}}
        //} catch (e){ console.log(e);}

}

document.observe('dom:loaded', codendi.tracker.runTrackerFieldDependencies);

//}}}

