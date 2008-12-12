/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
*
* Originally written by Nicolas Terray, 2006
*
* This file is a part of CodeX.
*
* CodeX is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* CodeX is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with CodeX; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*
* 
*/

// Define namespace to prevent clashes
if (!com) var com = {};
if (!com.xerox) com.xerox = {};
if (!com.xerox.codex) com.xerox.codex = {};
if (!com.xerox.codex.tracker) com.xerox.codex.tracker = {};

// Define global (berk) variables
var fields            = {};
var fields_by_name    = {};
var options           = {};
var rules             = [];
var rules_definitions = {};
var dependencies      = {};
var selections        = {};


/**
 * Debug function.
 * Just give a msg as arguments, 
 * and it will be displayed inside 
 * a textarea at the end of the page.
 * Other js loggers was a little bit
 * too heavy for a simple feature.
 */
function xgs_debug(msg) {
    var d = $('debug_console');
    if (!d) {
        d = document.createElement('textarea');
        d.rows = 50;
        d.cols = 160;
        d.id = 'debug_console';
        document.body.appendChild(d);
    }
    var now = new Date();
    var h = now.getHours();
    var m = now.getMinutes();
    var s = now.getSeconds();
    var ms = now.getMilliseconds();
    d.value += '['+h+':'+m+':'+s+'.'+ms+']\t'+msg+'\n';
}
//==============================================================================
//==============================================================================

//{{{                         Client part

//==============================================================================
//==============================================================================


// The highlight color for the Yellow Fade Technique
var HIGHLIGHT_STARTCOLOR = '#ffff99';

// Search for a class in loaded stylesheets
function getStyleClass (className) {
    var re = new RegExp("\\." + className + "$", "gi");
    if (document.all) {
        for (var s = 0; s < document.styleSheets.length; s++) {
            for (var r = 0; r < document.styleSheets[s].rules.length; r++) {
                if (document.styleSheets[s].rules[r].selectorText && document.styleSheets[s].rules[r].selectorText.search(re) != -1) {
                    return document.styleSheets[s].rules[r].style;
                }
            }
        }
    } else if (document.getElementById) {
        for (var s = 0; s < document.styleSheets.length; s++) {
            for (var r = 0; r < document.styleSheets[s].cssRules.length; r++) {
                if (document.styleSheets[s].cssRules[r].selectorText && document.styleSheets[s].cssRules[r].selectorText.search(re) != -1) {
                    document.styleSheets[s].cssRules[r].sheetIndex = s;
                    document.styleSheets[s].cssRules[r].ruleIndex = s;
                    return document.styleSheets[s].cssRules[r].style;
                }
            }
        }
    } else if (document.layers) {
        return document.classes[className].all;
    }
    return null;
}
// Search for a property for a class in loaded stylesheets
function getStyleClassProperty (className, propertyName) {
  var styleClass = getStyleClass(className);
  if (styleClass)
    return styleClass[propertyName];
  else 
    return null;
}



/**
* Internal representation of a field
* 
* A field is identified by an id and has a name and a label
* The id is used only internally as widget elements id is the name of the field.
* The fields can propose some options
*  - defaultOptions are all options the field can propose
*  - actualOptions are the options which are proposed at a given time
*  - selectedOptions are the options which are selected on corresponding widgets
*/
com.xerox.codex.tracker.Field = Class.create();
Object.extend(com.xerox.codex.tracker.Field.prototype, {
    initialize: function (id, name, label) {
        this.id              = id;
        this.name            = name;
        this.label           = label;
        this._highlight      = null;
        this.defaultOptions  = [];
        this.selectedOptions = [];
        this.actualOptions   = [];
    },
    highlight: function() {
        //We store the actual effect to not load a lot of new objects
        //and also to prevent locks of the background color
        if (!this._highlight) {
            this._highlight = new Effect.Highlight($(this.name), {startcolor:HIGHLIGHT_STARTCOLOR});
        } else {
            this._highlight.start(this._highlight.options);
        }
    },
    addDefaultOption: function(option_id, selected) {
        this.defaultOptions.push(option_id);
        this.actualOptions.push(option_id);
        if (selected) {
            this.selectedOptions.push(option_id);
        }
    },
    /**
    * update maps the navigator state of the field to the js representation (this class) of the field.
    * If an option was registered as selected and it is not (or vice versa), we update the representation.
    *
    */
    update: function() {
        var has_changed = false;
        var el = $(this.name);
        for(i = 0 ; i < el.options.length ; i++) {
            //search if the option i is a selectedOption
            var j = 0;
            var found = false;
            while(j < this.selectedOptions.length && !found) {
                if (this.selectedOptions[j] == el.options[i].value) {
                    found = this.selectedOptions[j];
                }
                j++;
            }
            
            if (found) { //The option was previously selected
                if (!(el.options[i].selected)) { //The option is not anymore selected
                    //We remove it
                    this.selectedOptions = this.selectedOptions.reject(function (element) { return element == el.options[i].value; });
                    has_changed = true;
                }
            } else { //The option was not selected...
                if (el.options[i].selected) { //...but is now selected
                    //We add it
                    this.selectedOptions.push(el.options[i].value);
                    has_changed = true;
                }
            }
        }
        return has_changed;
    },
    /**
    * add an actualOption
    */
    add: function(new_option_id) {
        //We search first if we have already added this option
        var len = $(this.name).options.length;
        var i = 0;
        while (i < len && $(this.name).options[i].value != new_option_id) {
            i++;
        }
        if (i >= len) {
            opt = new Option(options[this.id][new_option_id].option.text, options[this.id][new_option_id].option.value);
            $(this.name).options[$(this.name).options.length] = opt;
            $(this.name).options[$(this.name).options.length - 1].innerHTML = opt.text;
            this.actualOptions.push(new_option_id);
        }
    },
    /**
    * remove all actual options
    */
    clear: function() {
        var el = $(this.name);
        
        //clear actual options
        this.actualOptions = [];
        var len = el.options.length; 
        for (i = len; i >= 0; i--) {
            el.options[i] = null;
        }
    },
    /**
    * reset field (map widget to defaultOptions)
    */
    reset: function() {
        var changed = true;
        var el = $(this.name);
        if (el.options.length == this.defaultOptions.length) {
            changed = false;
        } else {
            //clear actual options
            this.actualOptions = [];
            var len = el.options.length; 
            for (i = len; i >= 0; i--) {
                el.options[i] = null;
            }
            
            //fill new options
            for (i = 0 ; i < this.defaultOptions.length ; i++) {
                var opt = new Option(options[this.id][this.defaultOptions[i]].option.text, options[this.id][this.defaultOptions[i]].option.value);
                el.options[el.options.length] = opt;
                el.options[el.options.length - 1] = opt.text; //html entities (cannot be done before in IE)
                this.actualOptions.push(this.defaultOptions[i]);
            }
            
            //select options
            this.selectedOptions.each(function (option) {
                var i     = 0;
                var found = false;
                var len   = el.options.length;
                while(i < len && !found) {
                    if (el.options[i].value == option) {
                        el.options[i].selected = true;
                        found = true;
                    }
                    i++;
                }
            });
        }
        return changed;
    },
    /**
    * preselect options
    */
    select: function() {
        if (arguments[0]) {
            this.selectedOptions = arguments[0];
        } else {
            var selectedOptions = this.selectedOptions;
            this.selectedOptions = this.actualOptions.findAll(function(element) {
                return selectedOptions.find(function (option) {
                    return element == option;
                });
            });
        }
        var el = $(this.name);
        if (el.options.length > 0) {
            if (this.selectedOptions.length < 1) {
                this.selectedOptions.push(this.actualOptions[0]);
            }
            var len = el.options.length;
            for(var k = 0 ; k < len ; k++) {
                el.options[k].selected = this.selectedOptions.find(function (element) {
                        return element == el.options[k].value;
                }) ? 'selected' : '';
            }
        }
    },
    /**
    * if the user select an option, we have to store the changes
    */
    updateSelected: function() {
        this.selectedOptions = [];
        var el = $(this.name);
        if (el) {
            var len = el.options.length;
            for(var k = 0 ; k < len ; k++) {
                if (el.options[k].selected) {
                    this.selectedOptions.push(this.defaultOptions.find(function (element) {
                        return el.options[k].value == element;
                    }));
                }
            }
        }
    }
});

/**
* A rule (representation of a php RuleValue)
* 
*/
com.xerox.codex.tracker.Rule = Class.create();
Object.extend(com.xerox.codex.tracker.Rule.prototype, {
    initialize: function (rule_definition) {
        this.source_field_id = rule_definition.source_field;
        this.source_value_id = rule_definition.source_value;
        this.target_field_id = rule_definition.target_field;
        this.target_value_id = rule_definition.target_value;
        this.selected        = [];
    },
    process: function(source_field) {
        var applied = false;
        if (this.source_field_id == source_field.id) {
            fields[this.source_field_id].update();
            if (this.can_apply()) {
                fields[this.target_field_id].add(this.target_value_id);
                fields[this.target_field_id].select(this.selected);
                applied = fields[this.target_field_id];
            }
        }
        return applied;
    },
    /**
    * we store internally the state of the target selection to not loose it when user switches between rules
    */
    updateSelected: function(source_field, target_field) {
        if (this.target_field_id == target_field.id) {
            if (this.source_field_id == source_field.id) {
                if (this.can_apply()) {
                    var len = $(fields[this.target_field_id].name).options.length;
                    var i = 0;
                    this.selected = [];
                    while (i < len) {
                        if ($(fields[this.target_field_id].name).options[i].selected) {
                            this.selected.push($(fields[this.target_field_id].name).options[i].value);
                        }
                        i++;
                    }
                }
            }
        }
    },
    /**
    * @return true if the current rule can be applied (rule.source_value is selected on "real" field)
    */
    can_apply: function() {

        var can_apply = false; 
        var len = $(fields[this.source_field_id].name).options.length;
        for (var i = 0 ; i < len && !can_apply ; i++) {
            can_apply = $(fields[this.source_field_id].name).options[i].value == this.source_value_id 
                        && $(fields[this.source_field_id].name).options[i].selected;
        }

        return can_apply;
    }
});

function addOptionsToFields() {
    $H(fields).each(function(f) {
        $H(options[f.key]).values().each(function (opt) {
            f.value.addDefaultOption(opt['option'].value, opt['selected']);
        });
        f.value.updateSelected();
    });
}
function getFieldByName(name) {
    if (!fields_by_name[name]) {
        fields_by_name[name] = $H(fields).values().find(function (field) { return field.name == name; });
    }
    return fields_by_name[name];
}
function applyRules(evt, name) {

    if (name) { this.name = name; }
    //We apply rules starting from the field which has name == id
    source_field = getFieldByName(this.name.replace('[]', ''));
    //Source field has been changed. We have to store those changes.
    source_field.updateSelected();
    //We keep history of selections to not lose them
    if(selections[source_field.id]) {
        $H(selections[source_field.id]).keys().each(function(key) {
            rules.each(function(rule) {
                rule.updateSelected(fields[key], source_field);
            });
        });
    }
    //Does the selected field has dependencies (targets) ?
    if (dependencies[source_field.id]) {
        //We add the current field to the queue
        //This queue manage the bubble : 
        // if A => B and B => C, 
        // then C may be changed depending on the selected value of A
        var queue = [source_field];
        
        //We process the queue, until it is empty
        var j = 0;
        while(j < queue.length) {
            //Highlight queue is an array of element to highlight (those who are modified)
            var highlight_queue = [];
            //originals is a hash of target fields (to save their current state)
            var originals = {};
            
            //For each field that are target of the current field...
            dependencies[queue[j].id].each(function (field) {
                    //...clear it (empty options),
                    field.clear();
                    
                    //...push it to the queue if it as dependencies, (if we don't have already add it)
                    if (dependencies[field.id] && !queue.find(function (element) { return element == field;})) {
                            queue.push(field);
                    }
                    
                    //...and save its actual state.
                    originals[field.id] = {field:field, options:[]};
                    var el = $(field.name);
                    for(var k = 0 ; k < el.options.length ; k++) {
                            originals[field.id].options.push(options[field.id][el.options[k].value]);
                    }
            });
            
            //We process all rules which can match the current source field
            rules.each(function (rule) {
                    rule.process(queue[j]);
            });
            
            //Now we look at original states of targets to see if there has been a change
            $H(originals).values().each(function(target) {
                var el = $(target.field.name);
                var found = false;
                for (var k = 0 ; k < el.options.length && !found ; k++) {
                    found = target.options.find(function (element) {
                        return element.value == el.options[i].value && el.options[i].selected == element.selected;
                    });
                }
                //There has benn a change: we highlight the field
                if (!found) {
                    highlight_queue.push(target.field);
                }
            });
            
            //for each target...
            dependencies[queue[j].id].each(function(field) {
                    //... select the target accordingly to previous selection and current options
                    field.select();
            });
            
            //Highlight fields which need
            highlight_queue.each(function(field) {
                field.highlight();
            });
            
            //Go one step further into the queue
            j++;
        }
    }

    
}

function registerFieldsEvents() {
    for(id in fields) {
        var el = document.getElementById(fields[id].name);
        if (el) {
            el.onchange = applyRules;
        }
    }

}

function addRule(rule_definition) {

    if (rule_definition.source_field != rule_definition.target_field 
        && fields[rule_definition.source_field] 
        && fields[rule_definition.target_field]
        && $(fields[rule_definition.source_field].name) 
        && $(fields[rule_definition.target_field].name) 
    ) {
        if (!selections[rule_definition.target_field]) { 
            selections[rule_definition.target_field] = {}; 
        }
        if (!selections[rule_definition.target_field][rule_definition.source_field]) { 
            selections[rule_definition.target_field][rule_definition.source_field] = []; 
        }
        
        if (!dependencies[rule_definition.source_field]) {
            dependencies[rule_definition.source_field] = [];
        }
        dependencies[rule_definition.source_field].push(fields[rule_definition.target_field]);
        
        rules.push(new com.xerox.codex.tracker.Rule(rule_definition));

    }

}

function initFieldDependencies() {
    addOptionsToFields();
    registerFieldsEvents();
    $H(rules_definitions).values().each(function(rule_definition) {
            addRule(rule_definition);
    });
    //Once rules have been loaded, we applied them on fields
    $H(fields).values().each(function (field) {
            applyRules(null, field.name);
    });
    //Once rules have been applied, we preselect values
    $H(fields).keys().each(function (field_id) {
            if (options[field_id]) {
                    options_that_should_be_selected = $H(options[field_id]).keys().findAll(function (option_id) {
                                return options[field_id][option_id]['selected'];
                    });
                    fields[field_id].select(options_that_should_be_selected);
            }
    });
    //Once fields have been selected, we store curent selection in rules
    $H(fields).keys().each(function (target_id) {
        if(selections[target_id]) {
            $H(selections[target_id]).keys().each(function(source_id) {
                rules.each(function(rule) {
                    rule.updateSelected(fields[source_id], fields[target_id] );
                });
            });
        }
    });
    //{{{ Look for HIGHLIGHT_STARTCOLOR in current css
    var codex_field_dependencies_highlight_change = getStyleClassProperty('codex_field_dependencies_highlight_change', 'backgroundColor');
    if (codex_field_dependencies_highlight_change && codex_field_dependencies_highlight_change != '') {
        HIGHLIGHT_STARTCOLOR = codex_field_dependencies_highlight_change;
    }
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
}
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
    admin_feedback_field_dependencies = $$('.feedback_field_dependencies');
    
    
        $('edit_rule_form').onsubmit = function() {
            $('save').value = 'save';
            $('direction_type').value      = admin_selected_type;
            $('value').value               = admin_selected_value;
            $('source_field_hidden').value = $F('source_field');
            $('target_field_hidden').value = $F('target_field');
            $('reset_btn').disabled        = $('save_btn').disabled = 'disabled';
            return true;
        };

        $('reset_btn').observe('click', function(evt) {
            admin_is_in_edit_mode = false;
            if (admin_selected_type == 'target') {
                admin_forceTargetValue($F('source_field'), $F('target_field'), admin_selected_value);
            } else {
                admin_forceSourceValue($F('source_field'), $F('target_field'), admin_selected_value);
            }
            Element.hide('save_panel');
            $('source_field').disabled = '';
            $('target_field').disabled = '';
            
            Event.stop(evt);
            return false;
        });
        
        Element.hide('save_panel');

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
    var p1 = id.indexOf('_');
    var p2 = id.substring(p1+1).indexOf('_');
    var p3 = id.substring(p1+1+p2+1).indexOf('_');
    var p4 = id.substring(p1+1+p2+1+p3+1).indexOf('_');
    var ret = {
        type:            id.substr(0, p1),
        source_field_id: id.substr(p1+1, p2),
        target_field_id: id.substr(p1+1+p2+1, p3),
        source_value_id: admin_selected_value,
        target_value_id: admin_selected_value
    };
    ret[ret.type+'_value_id'] = id.substr(p1+1+p2+1+p3+1, (p4 != -1?p4:id.length));
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
        admin_feedback_field_dependencies[0].parentNode.parentNode.parentNode.removeChild(admin_feedback_field_dependencies[0].parentNode.parentNode);
        admin_feedback_field_dependencies_effect_done = true;
        admin_feedback_field_dependencies             = [];
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
        $('source_field').disabled = 'disabled';
        $('target_field').disabled = 'disabled';
        admin_is_in_edit_mode = true;
        admin_nb_diff = 0;
        Element.show('save_panel');
    }
    
    var checked = $F(id);
    //boxitem and arrow follow the state of the corresponding checkbox
    if (checked) {
        Element.addClassName(checkbox.type+'_'+checkbox.source_field_id+'_'+checkbox.target_field_id+'_'+checkbox[checkbox.type+'_value_id'], 'boxhighlight');
        Element.setStyle(checkbox.type+'_'+checkbox.source_field_id+'_'+checkbox.target_field_id+'_'+checkbox[checkbox.type+'_value_id']+'_arrow', {visibility:'visible'});        
    } else {
        Element.removeClassName(checkbox.type+'_'+checkbox.source_field_id+'_'+checkbox.target_field_id+'_'+checkbox[checkbox.type+'_value_id'], 'boxhighlight');
        Element.setStyle(checkbox.type+'_'+checkbox.source_field_id+'_'+checkbox.target_field_id+'_'+checkbox[checkbox.type+'_value_id']+'_arrow', {visibility:'hidden'});        
    }
    //Does a rule exist ?
    var rule_exists = $H(rules_definitions).values().find(function (definition) {
        return definition.source_field == checkbox.source_field_id &&
                definition.target_field == checkbox.target_field_id &&
                definition.source_value == checkbox.source_value_id &&
                definition.target_values == checkbox.target_value_id;
    });
    if (rule_exists && checked || !rule_exists && !checked) {
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
        $H(rules_definitions).values().each(function (rule_definition) {
                    var checkbox_name = checkbox.type+'_'
                                        +rule_definition.source_field+'_'
                                        +rule_definition.target_field+'_'
                                        +rule_definition[checkbox.type+'_value']+'_chk';
                    if (rule_definition.source_field                     == checkbox.source_field_id 
                        && rule_definition.target_field                  == checkbox.target_field_id 
                        && rule_definition[admin_selected_type+'_value'] == admin_selected_value) {
                        if (!$F(checkbox_name)) {
                            can_leave_edit_mode = false;
                            throw $break;
                        }
                    }
        });
        if (can_leave_edit_mode) {
            $('source_field').disabled = '';
            $('target_field').disabled = '';
            admin_is_in_edit_mode = false;
            Element.hide('save_panel');
        }
    }
}

function admin_selectTargetEvent(element) {
    var link = admin_getInfosFromId(element.up('tr').id);
    admin_selectTargetValue(link.source_field_id, link.target_field_id, link[link.type+'_value_id']);
    return false;
};

function admin_selectTargetValue(source_field_id, target_field_id, target_value_id) {
    admin_removeFeedback();
    if (admin_is_in_edit_mode) {
        if (confirm('Save modifications ?')) {    
            $('save_btn').click();
        }
    }
    if (!admin_is_in_edit_mode) {
        admin_forceTargetValue(source_field_id, target_field_id, target_value_id);
    }
}
function admin_forceTargetValue(source_field_id, target_field_id, target_value_id) {
    //Select the target
    admin_selected_value = target_value_id;
    admin_selected_type  = 'target';
    
    $H(options[target_field_id]).each(function (opt) {
        Element.setStyle('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_chk', {visibility:'hidden'});
    });
    $H(options[target_field_id]).each(function (opt) {
        Element.removeClassName('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value, 'boxhighlight');
        Element.setStyle('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_arrow', {visibility:'hidden'});
    });
    Element.addClassName('target_'+source_field_id+'_'+target_field_id+'_'+target_value_id, 'boxhighlight');
    Element.setStyle('target_'+source_field_id+'_'+target_field_id+'_'+target_value_id+'_arrow', {visibility:'visible'});
    
    //Select sources
    $H(options[source_field_id]).each(function (opt) {
        Element.setStyle('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_chk', {visibility:'visible'});
        //Does a rule exist ?
        if ($H(rules_definitions).values().find(function (definition) {
            return definition.source_field == source_field_id &&
                    definition.target_field == target_field_id &&
                    definition.target_value == target_value_id &&
                    definition.source_value == opt.value['option'].value;
        })) {
            Element.addClassName('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value, 'boxhighlight');
            $('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_chk').checked = 'checked';
            Element.setStyle('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_arrow', {visibility:'visible'});
        } else {
            Element.removeClassName('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value, 'boxhighlight');
            $('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_chk').checked = '';
            Element.setStyle('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_arrow', {visibility:'hidden'});
        }
    });
    
}

function admin_selectSourceEvent(element) {
    var link = admin_getInfosFromId(element.up('tr').id);
    admin_selectSourceValue(link.source_field_id, link.target_field_id, link[link.type+'_value_id']);
    return false;
};
function admin_selectSourceValue(source_field_id, target_field_id, source_value_id) {
    admin_removeFeedback();
    if (admin_is_in_edit_mode) {
        if (confirm('Save modifications ?')) {    
            $('save_btn').click();
        }
    }
    if (!admin_is_in_edit_mode) {
        admin_forceSourceValue(source_field_id, target_field_id, source_value_id);
    }
}
function admin_forceSourceValue(source_field_id, target_field_id, source_value_id) {
    //Select the source
    admin_selected_value = source_value_id;
    admin_selected_type  = 'source';
    
    $H(options[source_field_id]).each(function (opt) {
        Element.setStyle('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_chk', {visibility:'hidden'});
    });
    $H(options[source_field_id]).each(function (opt) {
        Element.removeClassName('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value, 'boxhighlight');
        Element.setStyle('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_arrow', {visibility:'hidden'});
    });
    Element.addClassName('source_'+source_field_id+'_'+target_field_id+'_'+source_value_id, 'boxhighlight');
    Element.setStyle('source_'+source_field_id+'_'+target_field_id+'_'+source_value_id+'_arrow', {visibility:'visible'});
    
    //Select targets
    $H(options[target_field_id]).each(function (opt) {
        Element.setStyle('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_chk', {visibility:'visible'});
        //Does a rule exist ?
        if ($H(rules_definitions).values().find(function (definition) {
            return definition.source_field == source_field_id &&
                    definition.target_field == target_field_id &&
                    definition.source_value == source_value_id &&
                    definition.target_value == opt.value['option'].value;
        })) {
            Element.addClassName('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value, 'boxhighlight');
            $('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_chk').checked = 'checked';
            Element.setStyle('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_arrow', {visibility:'visible'});
        } else {
            Element.removeClassName('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value, 'boxhighlight');
            $('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_chk').checked = '';
            Element.setStyle('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_arrow', {visibility:'hidden'});
        }
    });
}

//}}}
