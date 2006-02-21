/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
 * $Id$
 */

if (!com) var com = {};
if (!com.xerox) com.xerox = {};
if (!com.xerox.codex) com.xerox.codex = {};
if (!com.xerox.codex.tracker) com.xerox.codex.tracker = {};

var fields            = {};
var fields_by_name    = {};
var options           = {};
var rules             = [];
var rules_definitions = {};
var dependencies      = {};
var selections        = {};


//==============================================================================
//==============================================================================

//{{{                         Client part

//==============================================================================
//==============================================================================


var HIGHLIGHT_STARTCOLOR = '#ffff99';

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
function getStyleClassProperty (className, propertyName) {
  var styleClass = getStyleClass(className);
  if (styleClass)
    return styleClass[propertyName];
  else 
    return null;
}




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
	highlight: function(mode) {
        switch (mode) {
            //BUG Fx 1.0.7: we lose scroll for multiple selectbox    
            /*
            case 'current':
                break; 
			case 'source':
			case 'target':
				Element.addClassName(this.name.replace('[]', ''), 'codex_dynamic_fields_highlight_'+mode);
				break;
            */
			default:
				if (!this._highlight) {
					this._highlight = new Effect.Highlight($(this.name), {startcolor:HIGHLIGHT_STARTCOLOR});
				} else {
					this._highlight.start(this._highlight.options);
				}
				break;
		}
	},
	unhighlight: function(mode) {
		switch (mode) {
			//BUG Fx 1.0.7: we lose scroll for multiple selectbox    
            /*
            case 'current':
                break; //BUG Fx 1.0.7: we lose scroll for multiple selectbox    
			case 'source':
			case 'target':
                Element.removeClassName(this.name, 'codex_dynamic_fields_highlight_'+mode);
				break;
            */
			default:
				break;
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
		has_changed = false;
		el = $(this.name);
		for(i = 0 ; i < el.options.length ; i++) {
            //search if the option i is a selectedOption
			j = 0;
			found = false;
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
	add: function(new_option_id) {
        //We search first if we have already added this option
        var len = $(this.name).options.length;
        var i = 0;
        while (i < len && $(this.name).options[i].value != new_option_id) {
            i++;
        }
        if (i >= len) {
            opt = document.createElement('option');
            opt.value = options[this.id][new_option_id].option.value;
            opt.appendChild(document.createTextNode(options[this.id][new_option_id].option.text));
            $(this.name).appendChild(opt);
            this.actualOptions.push(new_option_id);
        }
	},
	clear: function() {
		el = $(this.name);
		
		//clear actual options
		this.actualOptions = [];
		len = el.options.length; 
		for (i = len; i >= 0; i--) {
			el.options[i] = null;
		}
	},
	reset: function() {
		var changed = true;
		el = $(this.name);
		if (el.options.length == this.defaultOptions.length) {
			changed = false;
		} else {
			//clear actual options
			this.actualOptions = [];
			len = el.options.length; 
			for (i = len; i >= 0; i--) {
				el.options[i] = null;
			}
			
			//fill new options
			for (i = 0 ; i < this.defaultOptions.length ; i++) {
                opt = document.createElement('option');
                opt.appendChild(document.createTextNode(options[this.id][this.defaultOptions[i]].option.text));
                opt.value = options[this.id][this.defaultOptions[i]].option.value;
				el.options[el.options.length].appendChild(opt);
				this.actualOptions.push(this.defaultOptions[i]);
			}
			
			//select options
			this.selectedOptions.each(function (option) {
				i     = 0;
				found = false;
				len   = el.options.length;
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
	select: function() {
		if (arguments[0]) {
			this.selectedOptions = [arguments[0]];
		} else {
			selectedOptions = this.selectedOptions;
			this.selectedOptions = this.actualOptions.findAll(function(element) {
				return selectedOptions.find(function (option) {
					return element == option;
				});
			});
		}
		el = $(this.name);
		if (el.options.length > 0) {
			if (this.selectedOptions.length < 1) {
				this.selectedOptions.push(this.actualOptions[0]);
			}
			for(var k = 0 ; k < el.options.length ; k++) {
				el.options[k].selected = this.selectedOptions.find(function (element) {
					return element == el.options[k].value;
				});
			}
		}
	},
	updateSelected: function() {
		this.selectedOptions = [];
		el = $(this.name);
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

com.xerox.codex.tracker.Rule = Class.create();
Object.extend(com.xerox.codex.tracker.Rule.prototype, {
	initialize: function (rule_definition) {
		this.source_field_id = rule_definition.source_field;
		this.source_value_id = rule_definition.source_value;
		this.target_field_id = rule_definition.target_field;
		this.target_value_id = rule_definition.target_value;
		this.selected        = false;
	},
	process: function(source_field) {


		applied = false;
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
	//BUG Fx 1.0.7: we lose scroll for multiple selectbox    
    /*
    highlight: function(field) {
		if (this.target_field_id == field.id) {
			field.highlight('current');
			fields[this.source_field_id].highlight('source');
		}
		if (this.source_field_id == field.id) {
			field.highlight('current');
			fields[this.target_field_id].highlight('target');
		}
	},
    */
	updateSelected: function(target_field, source_field) {
		if (this.target_field_id == target_field.id) {
			if (this.source_field_id == source_field.id) {
				if (this.can_apply()) {
                    var len = $(fields[this.target_field_id].name).options.length;
                    var i = 0;
                    while (i < len && $(fields[this.target_field_id].name).options[i].value != this.target_value_id) {
                        i++;
                    }
                    if (i < len && $(fields[this.target_field_id].name).options[i].selected) {
                        this.selected = this.target_value_id;
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
                rule.updateSelected(source_field, fields[key]);
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
                    
                    //...push it to the queue if it as dependencies,
                    if (dependencies[field.id]) {
                            queue.push(field);
                    }
                    
                    //...and save its actual state.
                    originals[field.id] = {field:field, options:[]};
                    el = $(field.name);
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
                el = $(target.field.name);
                found = false;
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

		el = document.getElementById(fields[id].name);
		if (el) {
			el.onchange = applyRules;
			/* BUG NTY 20060220: 
               Highlighting a field resets it and therefore the viewport (of a multiselectbox) changes
               As it is not really user friendly, we do not highlight dynamicfields
               
            el.onmouseover = function() {
				for(i = 0 ; i < rules.length ; i++) {
					rules[i].highlight(getFieldByName(this.id));
				}
			};
			el.onmouseout = function() {
                $H(fields).values().each(function (field) {
					field.unhighlight('current');
					field.unhighlight('source');
					field.unhighlight('target');
				});
			};
            */
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

function initDynamicFields() {
    addOptionsToFields();
    registerFieldsEvents();
    $H(rules_definitions).values().each(function(rule_definition) {
            addRule(rule_definition);
    });
    //Once rules have been loaded, we applied them an fields
    $H(fields).values().each(function (field) {
            applyRules(null, field.name);
    });
    //{{{ Look for HIGHLIGHT_STARTCOLOR in current css
    codex_dynamic_fields_highlight_change = getStyleClassProperty('codex_dynamic_fields_highlight_change', 'backgroundColor');
    if (codex_dynamic_fields_highlight_change && codex_dynamic_fields_highlight_change != '') {
        HIGHLIGHT_STARTCOLOR = codex_dynamic_fields_highlight_change;
    }
    var hexChars = "0123456789ABCDEF";
    function Dec2Hex (Dec) { 
        var a = Dec % 16; 
        var b = (Dec - a)/16; 
        hex = "" + hexChars.charAt(b) + hexChars.charAt(a); 
        return hex;
    }
    var re = new RegExp(/rgb\([^0-9]*([0-9]+)[^0-9]*([0-9]+)[^0-9]*([0-9]+)\)/); //Fx returns rgb(123, 64, 32) instead of hexa color
    if (m = re.exec(HIGHLIGHT_STARTCOLOR)) {
        r = m[1] || null;
        g = m[2] || null;
        b = m[3] || null;
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


var forbidden_sources = {};
var forbidden_targets = {};


/**
* Walks through a graph and marks nodes
* To prevent infinite loop for cyclic graphs, you have to correctly mark nodes
* 
* @param start is the first node to mark
* @param cb_getChildren(node) is a callback which returns children of a node
* @param cb_mark(node) is a callback used to mark a node
* @param cb_is_marked(node) is a callback wich must return if a node is marked
*/
function breadthFirstWalk(start, cb_getChildren, cb_mark, cb_is_marked) {
     var pile = [];
     pile.push(start);
     while (pile.length > 0) {
             var x = pile.pop();
             if (!cb_is_marked(x)) {
                     cb_mark(x);
                     cb_getChildren(x).each(function (children) {
                             pile.push(children);
                     });
             }
     }
}

/**
* Build complete admin interface, based on rules_definitions, fields and options
* hashs.
* Assign handlers to inputs and build forbidden_sources and forbidden_targets hashs.
*/
function buildAdminUI() {

    //{{{ build forbidden_sources and forbidden_targets hashs
    $H(fields).values().each(function (field) {
            forbidden_sources[field.id] = [];
            forbidden_targets[field.id] = [];
    });
    $H(forbidden_sources).keys().each(function (id) {
            breadthFirstWalk(id, 
                function(node){ 
                    rules = $H(rules_definitions).values().findAll(
                            function (rule_definition) { 
                                return rule_definition.source_field == node;
                            });
                    children = [];
                    rules.each(function (rule) {
                            if (!children.find(function(element) { return element == rule.target_field; })) {
                                children.push(rule.target_field);
                            }
                    });
                    return children;
                },
                function(node){ forbidden_sources[id].push(node); }, 
                function(node){ return forbidden_sources[id].find(function(element) { return element == node; }); });
    });
    $H(forbidden_targets).keys().each(function (id) {
            breadthFirstWalk(id, 
                function(node){ 
                    rules = $H(rules_definitions).values().findAll(
                            function (rule_definition) { 
                                return rule_definition.target_field == node;
                            });
                    children = [];
                    rules.each(function (rule) {
                            if (!children.find(function(element) { return element == rule.source_field; })) {
                                children.push(rule.source_field);
                            }
                    });
                    return children;
                },
                function(node){ forbidden_targets[id].push(node); }, 
                function(node){ return forbidden_targets[id].find(function(element) { return element == node; });});
    });
    //}}}
    
    //{{{ Some inline help
    $('edit_rule').appendChild(help = document.createElement('div'));
    help.id = 'dynamicFields_admin_help';
    //}}}
    
    //{{{ Build Table
	table = document.createElement('table');
	table.border      = 0;
	table.cellpadding = 2;
	table.cellspacing = 1;
	
	header = document.createElement('thead');
	header_row = document.createElement('tr');
	header_row.className = 'boxtable';

	header_source = document.createElement('td');
	header_source.className = 'boxtitle';

	header_target = document.createElement('td');
	header_target.className = 'boxtitle';

	header_help = document.createElement('td');
	header_help.className = 'boxtitle';

	header_row.appendChild(header_source);
	header_row.appendChild(header_target);
	header_row.appendChild(header_help);
	header.appendChild(header_row);
	table.appendChild(header);
	
	tbody = document.createElement('tbody');
	table.appendChild(tbody);
	
	$('edit_rule').appendChild(table);
    //}}}
    
	//{{{ build source selectbox
	select_source = document.createElement('select');
	select_source.id = 'source_field';
	select_source.appendChild(choose = document.createElement('option'));
	choose.value    = '-1';
    choose.selected = (preselected_source_field == choose.value);
	choose.appendChild(document.createTextNode(messages['choose_field']));
	$H(fields).values().each(function(source_field) {
			//Don't add field if it is forbidden
            if (forbidden_targets[source_field.id].length != $H(fields).keys().length 
                    && (
                        preselected_target_field == '-1' 
                        || !forbidden_sources[preselected_target_field].find(function (forbidden_source) {
                                return source_field.id == forbidden_source;
                        }) 
                    )
                ) {
                so = document.createElement('option');
                so.value = source_field.id;
                so.selected = (preselected_source_field == so.value);
                so.appendChild(document.createTextNode(source_field.label));
                //If a rule exist for this field, highlight it
                if ($H(rules_definitions).values().find(function (rule_definition) {
                            return rule_definition.source_field == source_field.id;
                })) {
                    so.className = 'boxitem';
                }
                select_source.appendChild(so);
			}
	});
	//}}}
	
	//{{{ build target selectbox
	select_target = document.createElement('select');
	select_target.id = 'target_field';
	select_target.appendChild(choose = document.createElement('option'));
	choose.value = '-1';
	choose.selected = (preselected_target_field == choose.value);
    choose.appendChild(document.createTextNode(messages['choose_field']));
	$H(fields).values().each(function(target_field) {
			//Don't add field if it is forbidden
            if (forbidden_sources[target_field.id].length != $H(fields).keys().length
                && (
                    preselected_source_field == '-1' 
                    || !forbidden_targets[preselected_source_field].find(function (forbidden_target) {
                            return target_field.id == forbidden_target;
                    }) 
                )
            ) {
                to = document.createElement('option');
                to.value = target_field.id;
                to.selected = (preselected_target_field == to.value);
                to.appendChild(document.createTextNode(target_field.label));
                //If a rule exist for this field, highlight it
                if ($H(rules_definitions).values().find(function (rule_definition) {
                            return rule_definition.target_field == target_field.id;
                })) {
                    to.className = 'boxitem';
                }
                select_target.appendChild(to);
            }
	});
	//}}}
	
    //{{{ Build rows of the table
    $H(fields).values().each(function(source_field) {
			$H(fields).values().each(function(target_field) {
                    //One row foreach pair (source_field, target_field)
					if (target_field != source_field) {
						tr = document.createElement('tr');
						tr.id        = 'fields_'+source_field.id+'_'+target_field.id;
						tr.className = 'boxitemalt';
						tr.style.verticalAlign = 'top';
						Element.hide(tr);
                        
						//{{{ Build source cell
						tr.appendChild(td_source = document.createElement('td'));
						td_source.appendChild(inner_table = document.createElement('table'));
						Element.setStyle(inner_table, {width:'100%'});
						inner_table.cellPadding = 0;
						inner_table.cellSpacing = 0;
						inner_table.appendChild(inner_tbody = document.createElement('tbody'));
                        //Foreach option build an inner row
						$H(options[source_field.id]).values().each(function(opt) {
							txt = document.createTextNode(opt['option'].text+' ');
							inner_tr = document.createElement('tr');
							inner_tr.id = 'source_'+source_field.id+'_'+target_field.id+'_'+opt['option'].value;
							
							//{{{ The checkbox
                            td_chk = document.createElement('td');
							Element.setStyle(td_chk, {width:'1%'});
							chk = document.createElement('input');
                            chk.type = 'checkbox';
                            chk.name = chk.id = 'source_'+source_field.id+'_'+target_field.id+'_'+opt['option'].value+'_chk';
							chk.style.visibility = 'hidden';
							chk.onclick = function(event) {
								admin_checked(this.id);
							};
							td_chk.appendChild(chk);
							inner_tr.appendChild(td_chk);
                            //}}}
                            
							//{{{ The label of the option
                            td_txt = document.createElement('td');
                            td_txt.appendChild(espace_insecable = document.createElement('span'));
                            espace_insecable.innerHTML = '&nbsp;';
							td_txt.appendChild(label = document.createElement('label'));
							td_txt.onclick = function() {
								link = admin_getInfosFromId(this.parentNode.id);
								admin_selectSourceValue(link.source_field_id, link.target_field_id, link[link.type+'_value_id']);
								return false;
							};
                            Element.setStyle(td_txt, {cursor:'pointer'});
                            //Does a rule exist ?
							if ($H(rules_definitions).values().find(function (definition) {
								return definition.source_field == source_field.id &&
										definition.target_field == target_field.id &&
										definition.source_value == opt['option'].value;
							})) {
								label.appendChild(strong = document.createElement('strong'));
								strong.appendChild(txt);
							} else {
								label.appendChild(txt);
							}
                            Element.setStyle(label, {cursor:'pointer'});
                            inner_tr.appendChild(td_txt);
                            //}}}
                            
                            //{{{ The very beautiful arrow 
                            inner_tr.appendChild(td_arrow = document.createElement('td'));
                            Element.setStyle(td_arrow, {textAlign:'right'});
                            td_arrow.appendChild(arrow = document.createElement('div'));
                            arrow.innerHTML = '&rarr;';
                            arrow.id = 'source_'+source_field.id+'_'+target_field.id+'_'+opt['option'].value+'_arrow';
                            Element.setStyle(arrow, {visibility:'hidden'});
                            //}}}
                            
							inner_tbody.appendChild(inner_tr);
						});
						//}}}
						
						//{{{ Build target cell
						td_target = document.createElement('td');
						tr.appendChild(td_target);
						td_target.appendChild(inner_table = document.createElement('table'));
						Element.setStyle(inner_table, {width:'100%'});
						inner_table.cellPadding = 0;
						inner_table.cellSpacing = 0;
						inner_table.appendChild(inner_tbody = document.createElement('tbody'));
						//Foreach option build an inner row
						$H(options[target_field.id]).values().each(function(opt) {
							txt = document.createTextNode(opt['option'].text+' ');
							inner_tr = document.createElement('tr');
							inner_tr.id = 'target_'+source_field.id+'_'+target_field.id+'_'+opt['option'].value;
							
                            //{{{ The very beautiful arrow 
                            inner_tr.appendChild(td_arrow = document.createElement('td'));
                            Element.setStyle(td_arrow, {textAlign:'right', width:'1%'});
                            td_arrow.appendChild(arrow = document.createElement('div'));
                            arrow.innerHTML = '&rarr;';
                            arrow.id = 'target_'+source_field.id+'_'+target_field.id+'_'+opt['option'].value+'_arrow';
                            Element.setStyle(arrow, {visibility:'hidden'});
                            //}}}
                            
                            //{{{ The checkbox
							td_chk = document.createElement('td');
							Element.setStyle(td_chk, {width:'1%'});
							chk = document.createElement('input');
							chk.type = 'checkbox';
							chk.name = chk.id = 'target_'+source_field.id+'_'+target_field.id+'_'+opt['option'].value+'_chk';
							chk.style.visibility = 'hidden';
							chk.onclick = function(event) {
								admin_checked(this.id);
							};
							td_chk.appendChild(chk);
							inner_tr.appendChild(td_chk);
                            //}}}
                            
                            //{{{ The label of the option
							td_txt = document.createElement('td');
                            td_txt.appendChild(espace_insecable = document.createElement('span'));
                            espace_insecable.innerHTML = '&nbsp;';
							td_txt.appendChild(label = document.createElement('label'));
                            td_txt.onclick = function() {
								link = admin_getInfosFromId(this.parentNode.id);
								admin_selectTargetValue(link.source_field_id, link.target_field_id, link[link.type+'_value_id']);
								return false;
							};
							Element.setStyle(td_txt, {cursor:'pointer'});
							//Does a rule exist ?
							if ($H(rules_definitions).values().find(function (definition) {
								return definition.source_field == source_field.id &&
										definition.target_field == target_field.id &&
										definition.target_value == opt['option'].value;
							})) {
								label.appendChild(strong = document.createElement('strong'));
								strong.appendChild(txt);
							} else {
								label.appendChild(txt);
							}
                            Element.setStyle(label, {cursor:'pointer'});
                            inner_tr.appendChild(td_txt);
                            //}}}
                            
                            inner_tbody.appendChild(inner_tr);
						});
						//}}}
                        
						tbody.appendChild(tr);
					}
			});
	});
    //}}}
    
    //{{{ Some nice text in the header
    // Expected sentence: If field %1 is selected to %2 then field %3 will propose %4
    // As of 20060210, the sentence is Source: %1 %2 Target: %3 %4
	if_then = messages['if_then'];
	p1 = if_then.indexOf('%1');
	p2 = if_then.indexOf('%2');
	p3 = if_then.indexOf('%3');
	p4 = if_then.indexOf('%4');
	header_source.appendChild(document.createTextNode(if_then.substring(0, p1)));
	header_source.appendChild(select_source);
	header_source.appendChild(document.createTextNode(if_then.substring(p1+2, p2)));

	header_target.appendChild(document.createTextNode(if_then.substring(p2+2, p3)));
	header_target.appendChild(select_target);
	header_target.appendChild(document.createTextNode(if_then.substring(p3+2, p4)));
    
    //}}}
	
	//{{{ Save panel
	tbody.appendChild(tr = document.createElement('tr'));
	tr.id        = 'save_panel';
	tr.className = 'boxitem';
	tr.appendChild(td = document.createElement('td'));
	td.colSpan = 2;
	Element.setStyle(td, {textAlign:'center'});
	
    //Save button
	td.appendChild(save_btn = document.createElement('button'));
	save_btn.appendChild(document.createTextNode(messages['btn_save_rule']));
	save_btn.id      = 'save_btn';
	save_btn.onclick = function() {
        updateInlineHelp({action: 'save'});
        $('save').value = 'save';
        $('reset_btn').disabled    = this.disabled = 'disabled';
		$('direction_type').value  = admin_selected_type;
		$('value').value           = admin_selected_value;
        $('source_field_hidden').value = $F('source_field');
        $('target_field_hidden').value = $F('target_field');
        this.form.submit();
	};
    //Reset button
	td.appendChild(reset_btn = document.createElement('button'));
	reset_btn.appendChild(document.createTextNode(messages['btn_reset']));
    reset_btn.id = 'reset_btn';
    reset_btn.onclick = function() {
        admin_is_in_edit_mode = false;
        if (admin_selected_type == 'target') {
            admin_forceTargetValue($F('source_field'), $F('target_field'), admin_selected_value);
        } else {
            admin_forceSourceValue($F('source_field'), $F('target_field'), admin_selected_value);
        }
        Element.hide('save_panel');
        $('source_field').disabled = '';
        $('target_field').disabled = '';
        return false;
	};
	Element.hide('save_panel');
	//}}}
	
    //{{{ Handlers on select boxes (source and target fields)
	select_target.onchange = function() {
        admin_displayFields($F('source_field'), $F('target_field'));
        //{{{ re-build source selectbox
        var previous_selected = $F('source_field');
        var len = $('source_field').options.length;
        for(var i = len ; i >= 0 ; i--) {
            $('source_field').options[i] = null;
        }
        $('source_field').appendChild(choose = document.createElement('option'));
        choose.value = '-1';
        choose.appendChild(document.createTextNode(messages['choose_field']));
        $H(fields).values().each(function(source_field) {
                //Don't add field if it is forbidden
                if (forbidden_targets[source_field.id].length != $H(fields).keys().length
                    && (
                        $F('target_field') == '-1' 
                        || !forbidden_sources[$F('target_field')].find(function (forbidden_source) {
                                return source_field.id == forbidden_source;
                        }) 
                    )
                ) {
                    so = document.createElement('option');
                    so.value    = source_field.id;
                    so.selected = source_field.id == previous_selected ? 'selected' : '';
                    so.appendChild(document.createTextNode(source_field.label));
                    //If a rule exist for this field, highlight it
                    if ($H(rules_definitions).values().find(function (rule_definition) {
                                return rule_definition.source_field == source_field.id;
                    })) {
                        so.className = 'boxitem';
                    }

                    $('source_field').appendChild(so);
                }
        });
        //}}}
    };
    select_source.onchange = function() {
        admin_displayFields($F('source_field'), $F('target_field'));
        //{{{ re-build target selectbox
        var previous_selected = $F('target_field');
        var len = $('target_field').options.length;
        for(var i = len ; i >= 0 ; i--) {
            $('target_field').options[i] = null;
        }
        $('target_field').appendChild(choose = document.createElement('option'));
        choose.value = '-1';
        choose.appendChild(document.createTextNode(messages['choose_field']));
        $H(fields).values().each(function(target_field) {
                //Don't add field if it is forbidden
                if (forbidden_sources[target_field.id].length != $H(fields).keys().length
                    && (
                        $F('source_field') == '-1' 
                        || !forbidden_targets[$F('source_field')].find(function (forbidden_target) {
                                return target_field.id == forbidden_target;
                        }) 
                    )
                ) {
                    to = document.createElement('option');
                    to.value    = target_field.id;
                    to.selected = target_field.id == previous_selected ? 'selected' : '';
                    to.appendChild(document.createTextNode(target_field.label));
                    //If a rule exist for this field, highlight it
                    if ($H(rules_definitions).values().find(function (rule_definition) {
                                return rule_definition.target_field == target_field.id;
                    })) {
                        to.className = 'boxitem';
                    }

                    $('target_field').appendChild(to);
                }
        });
        //}}}
	};
    //}}}
    
    //Display the initial row
	admin_displayFields($F('source_field'), $F('target_field'));

    //Pre-select value if needed
    if (preselected_source_value && preselected_source_field != '-1' && preselected_target_field != '-1') {
        admin_forceSourceValue(preselected_source_field, preselected_target_field, preselected_source_value);
        currentstate = helpstates.un_un;
        currentstate.state.source = preselected_source_field;
        currentstate.state.target = preselected_target_field;
    } else {
        //Pre-select target if needed
        if (preselected_target_value && preselected_source_field != '-1' && preselected_target_field != '-1') {
            admin_forceTargetValue(preselected_source_field, preselected_target_field, preselected_target_value);
            currentstate = helpstates.un_un;
            currentstate.state.source = preselected_source_field;
            currentstate.state.target = preselected_target_field;
        }
    }
    updateInlineHelp();
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
	p1 = id.indexOf('_');
	p2 = id.substring(p1+1).indexOf('_');
	p3 = id.substring(p1+1+p2+1).indexOf('_');
	p4 = id.substring(p1+1+p2+1+p3+1).indexOf('_');
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
* Callback for (un)checked checkboxes
*/
function admin_checked(id) {
    checkbox = admin_getInfosFromId(id);
	//We're going to edit mode if we are not yet
	if (!admin_is_in_edit_mode) {
        $('source_field').disabled = 'disabled';
        $('target_field').disabled = 'disabled';
        admin_is_in_edit_mode = true;
		admin_nb_diff = 0;
		Element.show('save_panel');
	}
    
	checked = $F(id);
    //boxitem and arrow follow the state of the corresponding checkbox
    if (checked) {
        Element.addClassName(checkbox.type+'_'+checkbox.source_field_id+'_'+checkbox.target_field_id+'_'+checkbox[checkbox.type+'_value_id'], 'boxitem');
        Element.setStyle(checkbox.type+'_'+checkbox.source_field_id+'_'+checkbox.target_field_id+'_'+checkbox[checkbox.type+'_value_id']+'_arrow', {visibility:'visible'});        
    } else {
        Element.removeClassName(checkbox.type+'_'+checkbox.source_field_id+'_'+checkbox.target_field_id+'_'+checkbox[checkbox.type+'_value_id'], 'boxitem');
        Element.setStyle(checkbox.type+'_'+checkbox.source_field_id+'_'+checkbox.target_field_id+'_'+checkbox[checkbox.type+'_value_id']+'_arrow', {visibility:'hidden'});        
    }
	//Does a rule exist ?
	rule_exists = $H(rules_definitions).values().find(function (definition) {
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
    updateInlineHelp();
}

/**
* Displays the row corresponding to the selection
*/
function admin_displayFields(source, target) {
	$H(fields).each(function(source_field) {
		$H(fields).each(function(target_field) {
			if (source_field.key != target_field.key) {
                Element.hide('fields_'+source_field.key+'_'+target_field.key);
			}
		});
	});
	if ($('fields_'+source+'_'+target)) {
        admin_selected_value = false;
        admin_selected_type  = false;
		$H(options[target]).each(function (opt) {
            Element.setStyle('target_'+source+'_'+target+'_'+opt.value['option'].value+'_chk', {visibility:'hidden'});
        });
        $H(options[target]).each(function (opt) {
            Element.removeClassName('target_'+source+'_'+target+'_'+opt.value['option'].value, 'boxitem');
            Element.setStyle('target_'+source+'_'+target+'_'+opt.value['option'].value+'_arrow', {visibility:'hidden'});
        });
        $H(options[source]).each(function (opt) {
            Element.setStyle('source_'+source+'_'+target+'_'+opt.value['option'].value+'_chk', {visibility:'hidden'});
        });
        $H(options[source]).each(function (opt) {
            Element.removeClassName('source_'+source+'_'+target+'_'+opt.value['option'].value, 'boxitem');
            Element.setStyle('source_'+source+'_'+target+'_'+opt.value['option'].value+'_arrow', {visibility:'hidden'});
        });
        Element.show('fields_'+source+'_'+target);
    }
    updateInlineHelp();
}


function admin_selectTargetValue(source_field_id, target_field_id, target_value_id) {
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
        Element.removeClassName('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value, 'boxitem');
        Element.setStyle('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_arrow', {visibility:'hidden'});
    });
    Element.addClassName('target_'+source_field_id+'_'+target_field_id+'_'+target_value_id, 'boxitem');
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
            Element.addClassName('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value, 'boxitem');
            $('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_chk').checked = 'checked';
            Element.setStyle('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_arrow', {visibility:'visible'});
        } else {
            Element.removeClassName('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value, 'boxitem');
            $('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_chk').checked = '';
            Element.setStyle('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_arrow', {visibility:'hidden'});
        }
    });
    
    updateInlineHelp();
}
function admin_selectSourceValue(source_field_id, target_field_id, source_value_id) {
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
        Element.removeClassName('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value, 'boxitem');
        Element.setStyle('source_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_arrow', {visibility:'hidden'});
    });
    Element.addClassName('source_'+source_field_id+'_'+target_field_id+'_'+source_value_id, 'boxitem');
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
            Element.addClassName('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value, 'boxitem');
            $('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_chk').checked = 'checked';
            Element.setStyle('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_arrow', {visibility:'visible'});
        } else {
            Element.removeClassName('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value, 'boxitem');
            $('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_chk').checked = '';
            Element.setStyle('target_'+source_field_id+'_'+target_field_id+'_'+opt.value['option'].value+'_arrow', {visibility:'hidden'});
        }
    });
    
    updateInlineHelp();
}


/*
var inline_help_previous_selection = { source: false, target: false, direction:false};

function updateInlineHelp() {
    help = $('dynamicFields_admin_help');
    while (help.firstChild) {
        help.removeChild(help.firstChild);
    }
    help.appendChild(title = document.createElement('h3'));
    title.appendChild(document.createTextNode(messages.inline_help_title));
    help.appendChild(dl = document.createElement('dl'));
    
    source_field_id = $F('source_field'); source_field_id = source_field_id == -1 ? false : source_field_id;
    target_field_id = $F('target_field'); target_field_id = target_field_id == -1 ? false : target_field_id;
    
    dl.appendChild(q = document.createElement('dt'));
    q.appendChild(document.createTextNode(messages.inline_help_q1));
    if (source_field_id || target_field_id) {
        if (source_field_id != inline_help_previous_selection.source) {
            inline_help_previous_selection.source = source_field_id;
            if (target_field_id) {
                dl.appendChild(a = document.createElement('dd'));
                a.appendChild(document.createTextNode(messages.inline_help_a_t.replace('%s', fields[target_field_id].label)));
                dl.appendChild(q = document.createElement('dt'));
                q.appendChild(document.createTextNode(messages.inline_help_q2_s.replace('%s', fields[target_field_id].label)));
                if (source_field_id) {
                    dl.appendChild(a = document.createElement('dd'));
                    a.appendChild(document.createTextNode(messages.inline_help_a_s.replace('%s', fields[source_field_id].label)));
                    dl.appendChild(q = document.createElement('dt'));
                    q.appendChild(document.createTextNode(messages.inline_help_q3));
                }
            } else {
                if (source_field_id) {
                    dl.appendChild(a = document.createElement('dd'));
                    a.appendChild(document.createTextNode(messages.inline_help_a_s.replace('%s', fields[source_field_id].label)));
                    dl.appendChild(q = document.createElement('dt'));
                    q.appendChild(document.createTextNode(messages.inline_help_q2_t.replace('%s', fields[source_field_id].label)));
                }
            }
        } else {
            inline_help_previous_selection.target = target_field_id;
            if (source_field_id) {
                dl.appendChild(a = document.createElement('dd'));
                a.appendChild(document.createTextNode(messages.inline_help_a_s.replace('%s', fields[source_field_id].label)));
                dl.appendChild(q = document.createElement('dt'));
                q.appendChild(document.createTextNode(messages.inline_help_q2_t.replace('%s', fields[source_field_id].label)));
                if (target_field_id) {
                    dl.appendChild(a = document.createElement('dd'));
                    a.appendChild(document.createTextNode(messages.inline_help_a_t.replace('%s', fields[target_field_id].label)));
                    dl.appendChild(q = document.createElement('dt'));
                    q.appendChild(document.createTextNode(messages.inline_help_q3));
                }
            } else {
                if (target_field_id) {
                    dl.appendChild(a = document.createElement('dd'));
                    a.appendChild(document.createTextNode(messages.inline_help_a_t.replace('%s', fields[target_field_id].label)));
                    dl.appendChild(q = document.createElement('dt'));
                    q.appendChild(document.createTextNode(messages.inline_help_q2_s.replace('%s', fields[target_field_id].label)));
                }
            }
        }
    }
}
*/

com.xerox.codex.tracker.HelpState = Class.create();
Object.extend(com.xerox.codex.tracker.HelpState.prototype, {
	initialize: function (name) {
        this.name        = name;
        this.transitions = {};
        this.state       = {source:false, target:false, selected_value:false };
    },
    add: function(evt, st) {
        this.transitions[evt] = st;
    },
    get: function(evt) {
        return this.transitions[evt];
    },
    process: function() {
        return this;
    },
    display: function() {
    }
});

com.xerox.codex.tracker.HelpStateFull = Class.create();
Object.extend(Object.extend(com.xerox.codex.tracker.HelpStateFull.prototype, com.xerox.codex.tracker.HelpState.prototype), {
    process: function() {
        next = this;
        source_field_id = $F('source_field'); source_field_id = source_field_id == -1 ? false : source_field_id;
        if (!source_field_id) {
            next = this.get('sf-1');
            next.state.source = source_field_id;
            next.state.target = this.state.target;
        } else {
            if (source_field_id != this.state.source) {
                next = this.get('sf');
                next.state.source = source_field_id;
                next.state.target = this.state.target;
            } else {
                target_field_id = $F('target_field'); target_field_id = target_field_id == -1 ? false : target_field_id;
                if (!target_field_id) {
                    next = this.get('tf-1');
                    next.state.target = target_field_id;
                    next.state.source = this.state.source;
                } else {
                    if (target_field_id != this.state.target) {
                        next = this.get('tf');
                        next.state.target = target_field_id;
                        next.state.source = this.state.source;
                    } else {
                        if (admin_selected_type && admin_selected_value) {
                            if (admin_selected_type == 'source') {
                                next = this.get('sv');
                                next.state.source = this.state.source;
                                next.state.target = this.state.target;
                                next.state.selected_value = admin_selected_value;
                            } else if (admin_selected_type == 'target') {
                                next = this.get('tv');
                                next.state.source = this.state.source;
                                next.state.target = this.state.target;
                                next.state.selected_value = admin_selected_value;
                            }
                        }
                    }
                }
            }
        }
        return next;
    }
});

helpstates = {
    zero:         new com.xerox.codex.tracker.HelpState('0'),
    un:           new com.xerox.codex.tracker.HelpState('1'),
    un_un:        new com.xerox.codex.tracker.HelpStateFull('1.1'),
    un_un_un:     new com.xerox.codex.tracker.HelpStateFull('1.1.1'),
    un_un_deux:   new com.xerox.codex.tracker.HelpStateFull('1.1.2'),
    deux:         new com.xerox.codex.tracker.HelpState('2'),
    deux_un:      new com.xerox.codex.tracker.HelpStateFull('2.1'),
    deux_un_un:   new com.xerox.codex.tracker.HelpStateFull('2.1.1'),
    deux_un_deux: new com.xerox.codex.tracker.HelpStateFull('2.1.2'),
    end:          new com.xerox.codex.tracker.HelpState('end')
};

helpstates.zero.add('sf', helpstates.un);
helpstates.zero.add('tf', helpstates.deux);
helpstates.zero.display = function() {
    help = $('dynamicFields_admin_help');
    while (help.firstChild) {
        help.removeChild(help.firstChild);
    }
    help.appendChild(title = document.createElement('h3'));
    title.appendChild(document.createTextNode(messages.inline_help_title));
    help.appendChild(dl = document.createElement('dl'));
    dl.appendChild(q = document.createElement('dt'));
    q.appendChild(document.createTextNode(messages.inline_help_q1));
    return dl;
};
helpstates.zero.process = function() {
    next = this;
    source_field_id = $F('source_field'); source_field_id = source_field_id == -1 ? false : source_field_id;
    if (source_field_id) {
        next = this.get('sf');
        next.state.source = source_field_id;
    } else { 
        target_field_id = $F('target_field'); target_field_id = target_field_id == -1 ? false : target_field_id;
        if (target_field_id) {
            next = this.get('tf');
            next.state.target = target_field_id;
        }
    }
    return next;
};

helpstates.un.add('sf-1', helpstates.zero);
helpstates.un.add('sf',   helpstates.un);
helpstates.un.add('tf',   helpstates.un_un);
helpstates.un.display = function() {
    dl = helpstates.zero.display();
    dl.appendChild(a = document.createElement('dd'));
    a.appendChild(document.createTextNode(messages.inline_help_a_s.replace('%', fields[this.state.source].label)));
    dl.appendChild(q = document.createElement('dt'));
    q.appendChild(document.createTextNode(messages.inline_help_q2_t.replace('%', fields[this.state.source].label)));
    return dl;
};
helpstates.un.process = function() {
    next = this;
    source_field_id = $F('source_field'); source_field_id = source_field_id == -1 ? false : source_field_id;
    if (!source_field_id) {
        this.state.source = false;
        next = this.get('sf-1');
    } else {
        if (this.state.source != source_field_id) {
            next = this.get('sf');
            next.state.source = source_field_id;
        }
        target_field_id = $F('target_field'); target_field_id = target_field_id == -1 ? false : target_field_id;
        if (target_field_id) {
            next = this.get('tf');
            next.state.target = target_field_id;
            next.state.source = this.state.source;
        }
    }
    return next;
};

helpstates.un_un.add('tf-1', helpstates.un);
helpstates.un_un.add('sf-1', helpstates.deux);
helpstates.un_un.add('sf',   helpstates.un_un);
helpstates.un_un.add('tf',   helpstates.un_un);
helpstates.un_un.add('sv',   helpstates.un_un_un);
helpstates.un_un.add('tv',   helpstates.un_un_deux);
helpstates.un_un.display = function() {
    dl = helpstates.un.display();
    dl.appendChild(a = document.createElement('dd'));
    a.appendChild(document.createTextNode(messages.inline_help_a_t.replace('%', fields[this.state.target].label)));
    dl.appendChild(q = document.createElement('dt'));
    q.appendChild(document.createTextNode(messages.inline_help_q3));
    return dl;
};

helpstates.un_un_un.add('tf-1', helpstates.un);
helpstates.un_un_un.add('sf-1', helpstates.deux);
helpstates.un_un_un.add('tf',   helpstates.un_un);
helpstates.un_un_un.add('sf',   helpstates.un_un)
helpstates.un_un_un.add('tv',   helpstates.un_un_deux);
helpstates.un_un_un.add('sv',   helpstates.un_un_un);
helpstates.un_un_un.add('chk',  helpstates.un_un_un);
helpstates.un_un_un.add('save', helpstates.end);
helpstates.un_un_un.display = function() {
    dl = helpstates.un_un.display();
    value = admin_selected_type == 'target' ? options[this.state.target][this.state.selected_value].option.text : options[this.state.source][this.state.selected_value].option.text;
    dl.appendChild(a = document.createElement('dd'));
    a.appendChild(document.createTextNode(messages.inline_help_a3_s.replace('%', value)));
    dl.appendChild(q = document.createElement('dt'));
    q.appendChild(document.createTextNode(messages.inline_help_q4_t.replace('%1', fields[this.state.target].label).replace('%2', fields[this.state.source].label).replace('%3', value)));
    dl.appendChild(a = document.createElement('dd'));
    values   = [];
    field_id = admin_selected_type == 'target' ? this.state.source : this.state.target;
    $H(options[field_id]).values().each((function (opt) {
            chk = (admin_selected_type == 'target' ? 'source' : 'target')+'_'+this.state.source+'_'+this.state.target+'_'+opt.option.value+'_chk';
            if ($F(chk)) {
                values.push(opt.option.text);
            }
    }).bind(this));
    a.appendChild(document.createTextNode(messages.inline_help_a4.replace('%', values.join(', '))));
    return dl;
};

helpstates.un_un_deux.add('tf-1', helpstates.un);
helpstates.un_un_deux.add('sf-1', helpstates.deux);
helpstates.un_un_deux.add('tf',   helpstates.un_un);
helpstates.un_un_deux.add('sf',   helpstates.un_un)
helpstates.un_un_deux.add('sv',   helpstates.un_un_un);
helpstates.un_un_deux.add('tv',   helpstates.un_un_deux);
helpstates.un_un_deux.add('chk',  helpstates.un_un_deux);
helpstates.un_un_deux.add('save', helpstates.end);
helpstates.un_un_deux.display = function() {
    dl = helpstates.un_un.display();
    value = admin_selected_type == 'target' ? options[this.state.target][this.state.selected_value].option.text : options[this.state.source][this.state.selected_value].option.text;
    dl.appendChild(a = document.createElement('dd'));
    a.appendChild(document.createTextNode(messages.inline_help_a3_t.replace('%', fields[this.state.target].label)));
    dl.appendChild(q = document.createElement('dt'));
    q.appendChild(document.createTextNode(messages.inline_help_q4_s.replace('%1', fields[this.state.source].label).replace('%2', fields[this.state.target].label).replace('%3', value)));
    dl.appendChild(a = document.createElement('dd'));
    values   = [];
    field_id = admin_selected_type == 'target' ? this.state.source : this.state.target;
    $H(options[field_id]).values().each((function (opt) {
            chk = (admin_selected_type == 'target' ? 'source' : 'target')+'_'+this.state.source+'_'+this.state.target+'_'+opt.option.value+'_chk';
            if ($F(chk)) {
                values.push(opt.option.text);
            }
    }).bind(this));
    a.appendChild(document.createTextNode(messages.inline_help_a4.replace('%', values.join(', '))));
    return dl;
};

helpstates.deux.add('tf-1', helpstates.zero);
helpstates.deux.add('tf',   helpstates.deux);
helpstates.deux.add('sf',   helpstates.deux_un);
helpstates.deux.display = function() {
    dl = helpstates.zero.display();
    dl.appendChild(a = document.createElement('dd'));
    a.appendChild(document.createTextNode(messages.inline_help_a_t.replace('%', fields[this.state.target].label)));
    dl.appendChild(q = document.createElement('dt'));
    q.appendChild(document.createTextNode(messages.inline_help_q2_s.replace('%', fields[this.state.target].label)));
    return dl;
};
helpstates.deux.process = function() {
    next = this;
    target_field_id = $F('target_field'); target_field_id = target_field_id == -1 ? false : target_field_id;
    if (!target_field_id) {
        this.state.target = false;
        next = this.get('tf-1');
    } else {
        if (this.state.target != target_field_id) {
            next = this.get('tf');
            next.state.target = target_field_id;
        }
        source_field_id = $F('source_field'); source_field_id = source_field_id == -1 ? false : source_field_id;
        if (source_field_id) {
            next = this.get('sf');
            next.state.source = source_field_id;
            next.state.target = this.state.target;
        }
    }
    return next;
};

helpstates.deux_un.add('sf-1', helpstates.deux);
helpstates.deux_un.add('tf-1', helpstates.un);
helpstates.deux_un.add('sf',   helpstates.deux_un);
helpstates.deux_un.add('tf',   helpstates.deux_un);
helpstates.deux_un.add('sv',   helpstates.deux_un_un);
helpstates.deux_un.add('tv',   helpstates.deux_un_deux);
helpstates.deux_un.display = function() {
    dl = helpstates.deux.display();
    dl.appendChild(a = document.createElement('dd'));
    a.appendChild(document.createTextNode(messages.inline_help_a_s.replace('%', fields[this.state.source].label)));
    dl.appendChild(q = document.createElement('dt'));
    q.appendChild(document.createTextNode(messages.inline_help_q3));
    return dl;
};

helpstates.deux_un_un.add('tf-1', helpstates.un);
helpstates.deux_un_un.add('sf-1', helpstates.deux);
helpstates.deux_un_un.add('tf',   helpstates.deux_un);
helpstates.deux_un_un.add('sf',   helpstates.deux_un)
helpstates.deux_un_un.add('tv',   helpstates.deux_un_deux);
helpstates.deux_un_un.add('sv',   helpstates.deux_un_un);
helpstates.deux_un_un.add('chk',  helpstates.deux_un_un);
helpstates.deux_un_un.add('save', helpstates.end);
helpstates.deux_un_un.display = function() {
    dl = helpstates.deux_un.display();
    value = admin_selected_type == 'target' ? options[this.state.target][this.state.selected_value].option.text : options[this.state.source][this.state.selected_value].option.text;
    dl.appendChild(a = document.createElement('dd'));
    a.appendChild(document.createTextNode(messages.inline_help_a3_s.replace('%', fields[this.state.source].label)));
    dl.appendChild(q = document.createElement('dt'));
    q.appendChild(document.createTextNode(messages.inline_help_q4_t.replace('%1', fields[this.state.target].label).replace('%2', fields[this.state.source].label).replace('%3', value)));
    dl.appendChild(a = document.createElement('dd'));
    values   = [];
    field_id = admin_selected_type == 'target' ? this.state.source : this.state.target;
    $H(options[field_id]).values().each((function (opt) {
            chk = (admin_selected_type == 'target' ? 'source' : 'target')+'_'+this.state.source+'_'+this.state.target+'_'+opt.option.value+'_chk';
            if ($F(chk)) {
                values.push(opt.option.text);
            }
    }).bind(this));
    a.appendChild(document.createTextNode(messages.inline_help_a4.replace('%', values.join(', '))));
    return dl;
};

helpstates.deux_un_deux.add('tf-1', helpstates.un);
helpstates.deux_un_deux.add('sf-1', helpstates.deux);
helpstates.deux_un_deux.add('tf',   helpstates.deux_un);
helpstates.deux_un_deux.add('sf',   helpstates.deux_un)
helpstates.deux_un_deux.add('sv',   helpstates.deux_un_un);
helpstates.deux_un_deux.add('tv',   helpstates.deux_un_deux);
helpstates.deux_un_deux.add('chk',  helpstates.deux_un_deux);
helpstates.deux_un_deux.add('save', helpstates.end);
helpstates.deux_un_deux.display = function() {
    dl = helpstates.deux_un.display();
    value = admin_selected_type == 'target' ? options[this.state.target][this.state.selected_value].option.text : options[this.state.source][this.state.selected_value].option.text;
    dl.appendChild(a = document.createElement('dd'));
    a.appendChild(document.createTextNode(messages.inline_help_a3_t.replace('%', value)));
    dl.appendChild(q = document.createElement('dt'));
    q.appendChild(document.createTextNode(messages.inline_help_q4_s.replace('%1', fields[this.state.source].label).replace('%2', fields[this.state.target].label).replace('%3', value)));
    dl.appendChild(a = document.createElement('dd'));
    values   = [];
    field_id = admin_selected_type == 'target' ? this.state.source : this.state.target;
    $H(options[field_id]).values().each((function (opt) {
            chk = (admin_selected_type == 'target' ? 'source' : 'target')+'_'+this.state.source+'_'+this.state.target+'_'+opt.option.value+'_chk';
            if ($F(chk)) {
                values.push(opt.option.text);
            }
    }).bind(this));
    a.appendChild(document.createTextNode(messages.inline_help_a4.replace('%', values.join(', '))));
    return dl;
};

var currentstate = helpstates.zero;

function updateInlineHelp(params) {
    if (params && params.action && params.action == 'save') {
        new Insertion.Bottom('dynamicFields_admin_help', messages.inline_help_save);
    } else {
        currentstate = currentstate.process();
        currentstate.display();
    }
}

//}}}
