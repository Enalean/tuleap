if (!com) var com = {};
if (!com.xerox) com.xerox = {};
if (!com.xerox.codex) com.xerox.codex = {};
if (!com.xerox.codex.tracker) com.xerox.codex.tracker = {};

com.xerox.codex.tracker.Field = Class.create();
Object.extend(com.xerox.codex.tracker.Field.prototype, {
	initialize: function (id, name) {
		this.id              = id;
		this.name            = name;
		this._highlight      = null;
		this.defaultOptions  = [];
		this.selectedOptions = [];
		this.actualOptions   = [];
	},
	highlight: function(mode) {
		switch (mode) {
			case 'previous':
			case 'next':
				Element.addClassName(this.id, mode);
				break;
			default:
				if (!this._highlight) {
					this._highlight = new Effect.Highlight(this.id);
				} else {
					this._highlight.start(this._highlight.options);
				}
				break;
		}
	},
	unhighlight: function(mode) {
		switch (mode) {
			case 'previous':
			case 'next':
				Element.removeClassName(this.id, mode);
				break;
			default:
				break;
		}
	},
	addDefaultOption: function(option, selected) {
		this.defaultOptions.push(option);
		this.actualOptions.push(option);
		if (selected) {
			this.selectedOptions.push(option);
		}
	},
	update: function() {
		has_changed = false;
		el = $(this.id);
		for(i = 0 ; i < el.options.length ; i++) {
			j = 0;
			found = false;
			while(j < this.selectedOptions.length && !found) {
				if (this.selectedOptions[j].value == el.options[i].value) {
					found = this.selectedOptions[j];
				}
				j++;
			}
			
			if (found) { //The option was previously selected
				if (!(el.options[i].selected)) { //The option is not anymore selected
					//We remove it
					this.selectedOptions = this.selectedOptions.reject(function (element) { return element.value == el.options[i].value; });
					has_changed = true;
				}
			} else { //The option was not selected...
				if (el.options[i].selected) { //...but is now selected
					//We add it
					this.selectedOptions.push(el.options[i]);
					has_changed = true;
				}
			}
		}
		return has_changed;
	},
	add: function(options) {
		el = $(this.id);
		
		//fill new options
		for (i = 0 ; i < options.length ; i++) {
			el.options[el.options.length] = options[i];
			this.actualOptions.push(options[i]);
		}
	},
	clear: function() {
		el = $(this.id);
		
		//clear actual options
		this.actualOptions = [];
		len = el.options.length; 
		for (i = len; i >= 0; i--) {
			el.options[i] = null;
		}
	},
	reset: function() {
		var changed = true;
		el = $(this.id);
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
				el.options[el.options.length] = this.defaultOptions[i];
				this.actualOptions.push(this.defaultOptions[i]);
			}
			
			//select options
			this.selectedOptions.each(function (option) {
				i     = 0;
				found = false;
				len   = el.options.length;
				while(i < len && !found) {
					if (el.options[i].value == option.value) {
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
			this.selectedOptions = arguments[0];
		} else {
			selectedOptions = this.selectedOptions;
			this.selectedOptions = this.actualOptions.findAll(function(element) {
				return selectedOptions.find(function (option) {
					return element == option;
				});
			});
		}
		el = $(this.id);
		if (el.options.length > 0) {
			if (this.selectedOptions.length < 1) {
				this.selectedOptions.push(this.actualOptions[0]);
			}
			for(var k = 0 ; k < el.options.length ; k++) {
				if (this.selectedOptions.find(function (element) {
					return element.value == el.options[k].value;
				})) {
					el.options[k].selected = true;
				}
			}
		}
	},
	updateSelected: function() {
		this.selectedOptions = [];
		el = $(this.id);
		if (el) {
			for(var k = 0 ; k < el.options.length ; k++) {
				if (el.options[k].selected) {
					this.selectedOptions.push(this.defaultOptions.find(function (element) {
						return el.options[k].value == element.value;
					}));
				}
			}
		}
	}
});

com.xerox.codex.tracker.Rule = Class.create();
Object.extend(com.xerox.codex.tracker.Rule.prototype, {
	initialize: function (field, proposedOptions, condition) {
		this.field           = field;
		this.proposedOptions = proposedOptions;
		this.condition       = condition;
		this.selectedOptions = [];
	},
	check: function(field) {
		applied = false;
		if (this.condition.isConcerned(field)) {
			if (this.condition.eval()) {
				this.field.add(this.proposedOptions);
				this.field.select(this.selectedOptions);
				applied = this.field;
			}
		}
		return applied;
	},
	highlight: function(field) {
		if (this.condition.isConcerned(field)) {
			this.field.highlight('next');
		}
		if (this.field == field) {
			this.condition.field.highlight('previous');
		}
	},
	updateSelected: function(field, condition_field) {
		if (this.field == field) {
			if (this.condition.isConcerned(condition_field)) {
				if (this.condition.eval()) {
					this.selectedOptions = field.selectedOptions;
				}
			}
		}
	}
});

com.xerox.codex.tracker.Condition = Class.create();
Object.extend(com.xerox.codex.tracker.Condition.prototype, {
	initialize: function (field, expectedOptions) {
		this.field           = field;
		this.expectedOptions = expectedOptions;
		this.selected        = [];
	},
	isConcerned: function(field) {
		return this.field == field;
	},
	eval: function() {
		if (this.field.update() || true) {
			one_do_not_match = false;
			for (i = 0 ; i < this.expectedOptions.length && !one_do_not_match ; i++) {
				var found = false;
				search = this.expectedOptions[i].value;
				this.field.selectedOptions.each(function(value) {
					if (value.value == search) {
						found = true;
						throw $break;
					}
				});
				one_do_not_match = !found;
			}
			return !one_do_not_match;
		} else {
			return false;
		}
	}
});
var fields            = {};
var options           = {};
var rules             = [];
var rules_definitions = {};
var dependencies      = {};
var selections        = {};
function addOptionsToFields() {
	for (field in fields) {
		for(option in options[field]) {
			fields[field].addDefaultOption(options[field][option]['option'], options[field][option]['selected']);
		}
		fields[field].updateSelected();
	}
}

function applyRules(evt, id) {
	if (id) { this.id = id; }
				source_field = fields[this.id];
				source_field.updateSelected();
				//We keep history of selection to not lose them
				if(selections[source_field.id]) {
					$H(selections[source_field.id]).keys().each(function(key) {
						rules.each(function(rule) {
							rule.updateSelected(source_field, fields[key]);
						});
					});
				}
				if (dependencies[this.id]) {
					
					var queue = [fields[this.id]];
					
					var j = 0;
					while(j < queue.length) {
						var highlight_queue = [];
						var original = {};
						
						dependencies[queue[j].id].each(function (field) {
							field.clear();
							if (dependencies[field.id]) {
								queue.push(field);
							}
							original[field.id] = {field:field, options:[]};
							el = $(field.id);
							for(var k = 0 ; k < el.options.length ; k++) {
								original[field.id].options.push(options[field.id][el.options[k].value]);
							}
						});
						
						var applied = [];
						for(var i = 0 ; i < rules.length ; i++) {
							applied.push(rules[i].check(queue[j]));
						}
						
						
						$H(original).keys().each(function(id) {
							
							el = $(id);
							found = false;
							for (var k = 0 ; k < el.options.length && !found ; k++) {
								found = original[id].options.find(function (element) {
									return element.value == el.options[i].value && el.options[i].selected == element.selected;
								});
							}
							if (!found) {
								highlight_queue.push(original[id].field);
							}
						});
						
						dependencies[queue[j].id].each(function(field) {
							field.select();
						});
						
						highlight_queue.each(function(field) {
							field.highlight();
						});
						
						j++;
					}
				}
}

function registerFieldsEvents() {
	for(id in fields) {
		el = document.getElementById(id);
		if (el) {
			el.onchange = applyRules;
			el.onmouseover = function() {
				for(i = 0 ; i < rules.length ; i++) {
					rules[i].highlight(fields[this.id]);
				}
			};
			el.onmouseout = function() {
				for(i in fields) {
					fields[i].unhighlight('previous');
					fields[i].unhighlight('next');
				}
			};
		}
	}
}

function addRule(condition, effect) {
	if(condition.id != effect.id && $(effect.id) && $(condition.id) && fields[condition.id] && fields[effect.id]) {
		if (!selections[effect.id]) { 
            selections[effect.id] = {}; 
        }
		if (!selections[effect.id][condition.id]) { 
            selections[effect.id][condition.id] = []; 
        }
		
		if (!dependencies[condition.id]) {
            dependencies[condition.id] = [];
        }
		dependencies[condition.id].push(fields[effect.id]);
		
		condition_options = [];
		condition.options.each(function(element) {
			condition_options.push(options[condition.id][element].option);
		});

		effect_options = [];
		effect.options.each(function(element) {
			effect_options.push(options[effect.id][element].option);
		});
		rules.push(new com.xerox.codex.tracker.Rule(
			fields[effect.id], 
			effect_options,
			new com.xerox.codex.tracker.Condition(fields[condition.id], condition_options)));
	}
}



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

	header_row.appendChild(header_source);
	header_row.appendChild(header_target);
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
                so.appendChild(document.createTextNode(source_field.name));
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
                to.appendChild(document.createTextNode(target_field.name));
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
                    so.appendChild(document.createTextNode(source_field.name));
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
                    to.appendChild(document.createTextNode(target_field.name));
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
        admin_selectSourceValue(preselected_source_field, preselected_target_field, preselected_source_value);
    } else {
        //Pre-select target if needed
        if (preselected_target_value && preselected_source_field != '-1' && preselected_target_field != '-1') {
            admin_selectTargetValue(preselected_source_field, preselected_target_field, preselected_target_value);
        }
    }
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
		Element.show('fields_'+source+'_'+target);
	}
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
}

