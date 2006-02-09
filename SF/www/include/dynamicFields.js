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
	highlight: function(as) {
		switch (as) {
			case 'previous':
			case 'next':
				Element.addClassName(this.id, as);
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
	unhighlight: function(as) {
		switch (as) {
			case 'previous':
			case 'next':
				Element.removeClassName(this.id, as);
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
					this.selectedOptions = this.selectedOptions.reject(function (element) { return element.value == el.options[i].value });
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
			changed = false
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
				})
			});
		}
		el = $(this.id);
		if (el.options.length > 0) {
			if (this.selectedOptions.length == 0) {
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
			fields[field].addDefaultOption(options[field][option]['option'], options[field][option]['selected'])
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
			}
			el.onmouseout = function() {
				for(i in fields) {
					fields[i].unhighlight('previous');
					fields[i].unhighlight('next');
				}
			}
		}
	}
}

function addRule(condition, effect) {
	if(condition.id != effect.id && $(effect.id) && $(condition.id) && fields[condition.id] && fields[effect.id]) {
		if (!selections[effect.id]) selections[effect.id] = {};
		if (!selections[effect.id][condition.id]) selections[effect.id][condition.id] = [];
		
		if (!dependencies[condition.id]) dependencies[condition.id] = [];
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
			new com.xerox.codex.tracker.Condition(
				fields[condition.id], 
				condition_options
			)
		));
	}
}



var dyn_fields = {};
function buildAdminUI() {

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
	
    query_params = location.href.toQueryParams();
    preselected_source_field = query_params['source_field'] ? query_params['source_field'] : '-1';
    preselected_target_field = query_params['target_field'] ? query_params['target_field'] : '-1';
    preselected_source_value = query_params['source_value'];
    preselected_target_value = query_params['target_value'];
    
	//{{{ build source selectbox
	select_source = document.createElement('select');
	select_source.id = select_source.name = 'source_field';
	select_source.appendChild(choose = document.createElement('option'));
	choose.value    = '-1';
    choose.selected = (preselected_source_field == choose.value);
	choose.appendChild(document.createTextNode(messages['choose_field']));
	$H(fields).values().each(function(source_field) {
			if (source_field.id != preselected_target_field) {
                so = document.createElement('option');
                so.value = source_field.id;
                so.selected = (preselected_source_field == so.value);
                so.appendChild(document.createTextNode(source_field.name));
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
	select_target.id = select_target.name = 'target_field';
	select_target.appendChild(choose = document.createElement('option'));
	choose.value = '-1';
	choose.selected = (preselected_target_field == choose.value);
    choose.appendChild(document.createTextNode(messages['choose_field']));
	$H(fields).values().each(function(target_field) {
			if (target_field.id != preselected_source_field) {
                to = document.createElement('option');
                to.value = target_field.id;
                to.selected = (preselected_target_field == to.value);
                to.appendChild(document.createTextNode(target_field.name));
                if ($H(rules_definitions).values().find(function (rule_definition) {
                            return rule_definition.target_field == target_field.id;
                })) {
                    to.className = 'boxitem';
                }
                select_target.appendChild(to);
            }
	});
	//}}}
	
	$H(fields).values().each(function(source_field) {
			$H(fields).values().each(function(target_field) {
					if (target_field != source_field) {
						tr = document.createElement('tr');
						tr.id        = 'fields_'+source_field.id+'_'+target_field.id;
						tr.className = 'boxitemalt';
						tr.style.verticalAlign = 'top';
						Element.hide(tr);
                        
						//{{{ Source
						tr.appendChild(td_source = document.createElement('td'));
						td_source.appendChild(inner_table = document.createElement('table'));
						Element.setStyle(inner_table, {width:'100%'});
						inner_table.cellPadding = 0;
						inner_table.cellSpacing = 0;
						inner_table.appendChild(inner_tbody = document.createElement('tbody'));
						$H(options[source_field.id]).values().each(function(opt) {
							txt = document.createTextNode(opt['option'].text+' ');
							inner_tr = document.createElement('tr');
							inner_tr.id = 'source_'+source_field.id+'_'+target_field.id+'_'+opt['option'].value;
							
							td_chk = document.createElement('td');
							Element.setStyle(td_chk, {width:'1%'});
							chk = document.createElement('input');
                            chk.type = 'checkbox';
                            chk.name = chk.id = 'source_'+source_field.id+'_'+target_field.id+'_'+opt['option'].value+'_chk';
							chk.style.visibility = 'hidden';
							chk.onclick = function(event) {
								admin_checked(this.id);
							}
							td_chk.appendChild(chk);
							inner_tr.appendChild(td_chk);
							
							td_txt = document.createElement('td');
                            td_txt.appendChild(espace_insecable = document.createElement('span'));
                            espace_insecable.innerHTML = '&nbsp;';
							td_txt.appendChild(label = document.createElement('label'));
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
							label.onclick = function() {
								link = admin_getInfosFromId(this.parentNode.parentNode.id);
								admin_selectSourceValue(link.source_field_id, link.target_field_id, link[link.type+'_value_id']);
								return false;
							}
                            Element.setStyle(label, {cursor:'pointer'});
							inner_tr.appendChild(td_txt);
							
                            inner_tr.appendChild(td_arrow = document.createElement('td'));
                            Element.setStyle(td_arrow, {textAlign:'right'})
                            td_arrow.appendChild(arrow = document.createElement('div'));
                            arrow.innerHTML = '&rarr;';
                            arrow.id = 'source_'+source_field.id+'_'+target_field.id+'_'+opt['option'].value+'_arrow';
                            Element.setStyle(arrow, {visibility:'hidden'});
                            
							inner_tbody.appendChild(inner_tr);
						});
						//}}}
						
						//{{{ Target
						td_target = document.createElement('td');
						tr.appendChild(td_target);
						td_target.appendChild(inner_table = document.createElement('table'));
						Element.setStyle(inner_table, {width:'100%'});
						inner_table.cellPadding = 0;
						inner_table.cellSpacing = 0;
						inner_table.appendChild(inner_tbody = document.createElement('tbody'));
						$H(options[target_field.id]).values().each(function(opt) {
							txt = document.createTextNode(opt['option'].text+' ');
							inner_tr = document.createElement('tr');
							inner_tr.id = 'target_'+source_field.id+'_'+target_field.id+'_'+opt['option'].value;
							
                            inner_tr.appendChild(td_arrow = document.createElement('td'));
                            Element.setStyle(td_arrow, {textAlign:'right', width:'1%'})
                            td_arrow.appendChild(arrow = document.createElement('div'));
                            arrow.innerHTML = '&rarr;';
                            arrow.id = 'target_'+source_field.id+'_'+target_field.id+'_'+opt['option'].value+'_arrow';
                            Element.setStyle(arrow, {visibility:'hidden'});
                            
							td_chk = document.createElement('td');
							Element.setStyle(td_chk, {width:'1%'});
							chk = document.createElement('input');
							chk.type = 'checkbox';
							chk.name = chk.id = 'target_'+source_field.id+'_'+target_field.id+'_'+opt['option'].value+'_chk';
							chk.style.visibility = 'hidden';
							chk.onclick = function(event) {
								admin_checked(this.id);
							}
							td_chk.appendChild(chk);
							inner_tr.appendChild(td_chk);
							
							td_txt = document.createElement('td');
                            td_txt.appendChild(espace_insecable = document.createElement('span'));
                            espace_insecable.innerHTML = '&nbsp;';
							td_txt.appendChild(label = document.createElement('label'));
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
							label.onclick = function() {
								link = admin_getInfosFromId(this.parentNode.parentNode.id);
								admin_selectTargetValue(link.source_field_id, link.target_field_id, link[link.type+'_value_id']);
								return false;
							}
                            Element.setStyle(label, {cursor:'pointer'});
							inner_tr.appendChild(td_txt);

							inner_tbody.appendChild(inner_tr);
						});
						//}}}
						tbody.appendChild(tr);
					}
			});
	});
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
	
	
	//{{{ Save panel
	tbody.appendChild(tr = document.createElement('tr'));
	tr.id        = 'save_panel';
	tr.className = 'boxitem';
	tr.appendChild(td = document.createElement('td'));
	td.colSpan = 2;
	Element.setStyle(td, {textAlign:'center'});
	
	td.appendChild(save_btn = document.createElement('button'));
	save_btn.appendChild(document.createTextNode(messages['btn_save_rule']));
	save_btn.name    = 'save';
	save_btn.onclick = function() {
		$('direction_type').value = admin_selected_type;
		$('value').value          = admin_selected_value;
        this.form.submit();
	}
	td.appendChild(reset_btn = document.createElement('button'));
	reset_btn.appendChild(document.createTextNode(messages['btn_reset']));
    reset_btn.onclick = function() {
        admin_is_in_edit_mode = false;
		if (admin_selected_type == 'target') {
            admin_forceTargetValue($F('source_field'), $F('target_field'), admin_selected_value);
        } else {
            admin_forceSourceValue($F('source_field'), $F('target_field'), admin_selected_value);
        }
        Element.hide('save_panel');
        return false;
	}
	Element.hide('save_panel');
	//}}} /**/
	
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
                if (source_field.id != $F('target_field')) {
                    so = document.createElement('option');
                    so.value    = source_field.id;
                    so.selected = source_field.id == previous_selected ? 'selected' : '';
                    so.appendChild(document.createTextNode(source_field.name));
                    
                    $('source_field').appendChild(so);
                }
        });
        //}}}
    }
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
                if (target_field.id != $F('source_field')) {
                    to = document.createElement('option');
                    to.value    = target_field.id;
                    to.selected = target_field.id == previous_selected ? 'selected' : '';
                    to.appendChild(document.createTextNode(target_field.name));
                    
                    $('target_field').appendChild(to);
                }
        });
        //}}}
	}
	admin_displayFields($F('source_field'), $F('target_field'));

    if (preselected_source_value && preselected_source_field != '-1' && preselected_target_field != '-1') {
        admin_selectSourceValue(preselected_source_field, preselected_target_field, preselected_source_value);
    } else {
        if (preselected_target_value && preselected_source_field != '-1' && preselected_target_field != '-1') {
            admin_selectTargetValue(preselected_source_field, preselected_target_field, preselected_target_value);
        }
    }
}

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

function admin_checked(id) {
	if (!admin_is_in_edit_mode) {
		admin_is_in_edit_mode = true;
		admin_nb_diff = 0;
		Element.show('save_panel');
	}
	checkbox = admin_getInfosFromId(id);
	checked = $F(id);
    //boxitem and arrow
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
		//Bug here
        //admin_nb_diff--;
        admin_nb_diff++;
	} else {
		admin_nb_diff++;
	}
	if (admin_nb_diff == 0) {
		admin_is_in_edit_mode = false;
		Element.hide('save_panel');
	}
}

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
		if (confirm('Discard changes ?')) {	
			//We are not anymore in edit mode
			admin_is_in_edit_mode = false;
			Element.hide('save_panel');
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
		if (confirm('Discard changes ?')) {	
			//We are not anymore in edit mode
			admin_is_in_edit_mode = false;
			Element.hide('save_panel');
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

function buildAdminUI1() {
	
	html = messages['if_then'];
	
	ms = document.createElement('select');
	ms.id = ms.name = 'source_field';
	o = document.createElement('option');
	o.value = '-1';
	o.innerHTML = messages['choose_field'];
	ms.appendChild(o);
	$H(fields).values().each(function(field) {
		o = document.createElement('option');
		o.value = field.id;
		o.innerHTML = field.name;
		ms.appendChild(o);
	});
	html = html.replace(/%1/, getOuterHTML(ms));
	
	
	msv = document.createElement('select');
	msv.multiple            = 'multiple';
	msv.style.verticalAlign = 'top';
	msv.id                  = 'source';
	msv.name                = 'source[]';
	msv.disabled            = 'disabled';
	html = html.replace(/%2/, getOuterHTML(msv));
	
	ss = ms.cloneNode(true);
	ss.id = ss.name = 'target_field';
	ss.disabled = 'disabled';
	html = html.replace(/%3/, getOuterHTML(ss));
	
	ssv = document.createElement('select');
	ssv.multiple            = 'multiple';
	ssv.style.verticalAlign = 'top';
	ssv.id                  = 'target';
	ssv.name                = 'target[]';
	ssv.disabled            = 'disabled';
	html = html.replace(/%4/, getOuterHTML(ssv));
	
	
	new Insertion.Top('edit_rule', html+'&nbsp;');
	submit = document.createElement('button');
	submit.id        = 'submit';
	submit.name      = 'save';
	submit.value     = 'save';
	submit.disabled  = 'disabled';
	submit.innerHTML = messages['btn_save_rule'];
	$('edit_rule').appendChild(submit);
	
	$('source_field').onchange = function() { admin_fieldHasChanged(this); }
	$('source').onchange       = function() { admin_fieldHasChanged(this); }
	$('target_field').onchange = function() { admin_fieldHasChanged(this); }
	$('target').onchange       = function() { admin_fieldHasChanged(this); }
	
	//Add behavior to edit and delete link
	$H(rules_definitions).values().each(function(rule_definition) {
		if (link = $('delete_link_'+rule_definition['id'])) {
			link.onclick = function() {
				return confirm(messages['delete_are_you_sure']);
			};
		}
		if (link = $('edit_link_'+rule_definition['id'])) {
			link.onclick = function() {
				//{{{ We pre-select source field
				len = $('source_field').options.length;
				for(var i = 0 ; i < len ; i++) {
					if ($('source_field').options[i].value == rule_definition['source_field']) {
						$('source_field').options[i].selected = 'selected';
					} else {
						$('source_field').options[i].selected = '';
					}
				}
				//}}}
				
				//{{{ We pre-select source values
				$('source').disabled = '';
				len = $('source').options.length;
				for(var i = len ; i >= 0 ; i--) {
					$('source').options[i] = null;
				}
				$('source').size = $H(options[rule_definition['source_field']]).values().length;
				$H(options[rule_definition['source_field']]).values().each(function(opt) {
						o = new Option(opt['option'].text, opt['option'].value);
						if (rule_definition['source_values'].find(function(value) {
									return value == opt['option'].value;
						})) {
							o.selected = 'selected';
						} else {
							o.selected = '';
						}
						$('source').appendChild(o);
				});
				//}}}
				
				//{{{ We pre-select target field
				$('target_field').disabled = '';
				len = $('target_field').options.length;
				for(var i = 0 ; i < len ; i++) {
					if ($('target_field').options[i].value == rule_definition['target_field']) {
						$('target_field').options[i].selected = 'selected';
					} else {
						$('target_field').options[i].selected = '';
					}
				}
				//}}}
				
				//{{{ We pre-select target values
				$('target').disabled = '';
				len = $('target').options.length;
				for(var i = len ; i >= 0 ; i--) {
					$('target').options[i] = null;
				}
				$('target').size = $H(options[rule_definition['target_field']]).values().length;
				$H(options[rule_definition['target_field']]).values().each(function(opt) {
						o = new Option(opt['option'].text, opt['option'].value);
						if (rule_definition['target_values'].find(function(value) {
									return value == opt['option'].value;
						})) {
							o.selected = 'selected';
						} else {
							o.selected = '';
						}
						$('target').appendChild(o);
				});
				//}}}
				
				//{{{ Redirection pour pointer vers le formulaire d'édition
				var re = new RegExp('#.*', "g");
				loc           = location.href.replace(re, '');
				location.href = loc + '#edit_rule';
				//}}}
				
				//new Effect.Highlight('edit_rule');
				
				return false;
			};
		}
	});
}

function getOuterHTML (node) {
	var html = '';
	switch (node.nodeType) {
		case 1:
			html += '<';
			html += node.nodeName;
			for (var a = 0 ; a < node.attributes.length; a++) {
				html += ' ' + node.attributes[a].nodeName.toUpperCase() +
				'="' + node.attributes[a].nodeValue + '"';
			}
			html += '>';
			html += node.innerHTML;
			html += '<\/' + node.nodeName + '>';
			break;
		case node.TEXT_NODE:
			html += node.nodeValue;
			break;
		case node.COMMENT_NODE:
			html += '<!' + '--' + node.nodeValue + '--' + '>';
			break;
	}
	return html;
}


function admin_fieldHasChanged(field) {
	switch (field.id) {
	case 'source_field':
		//{{{ We remove options for target
		reset = $('target_field', 'target', 'source');
		reset.each(function (el) {
			for(var i = el.options.length ; i >= 0 ; i--) {
				el.options[i] = null;
			}
			el.size     = 0;
			el.disabled = 'disabled';
		});
		//}}}
		if ($F(field.id) != '-1') {
			$('source').size = $H(options[$F(field.id)]).values().length;
			$H(options[$F(field.id)]).values().each(function(opt) {
					o = new Option(opt['option'].text, opt['option'].value);
					o.selected = '';
					$('source').appendChild(o);
			});
			//{{{ We remove source field from target field
			$('target_field').appendChild(new Option(messages['choose_field']), '-1');
			$H(fields).values().each(function(target_field) {
					if (target_field.id != $F(field.id)) {
						$('target_field').appendChild(new Option(target_field.name, target_field.id));
					}
			});
			//}}}
			$('source').disabled      = '';
			$('target_field').disabled = '';
		}
		break;
	case 'target_field':
		//{{{ We remove target field from source field
		el = $('source_field');
		old = $F('source_field');
		for(var i = el.options.length ; i >= 0 ; i--) {
			el.options[i] = null;
		}
		el.appendChild(new Option(messages['choose_field']), '-1');
		$H(fields).values().each(function(source_field) {
			if (source_field.id != $F(field.id)) {
				o = new Option(source_field.name, source_field.id);
				if (old == o.value) {
					o.selected = 'selected';
				}
				el.appendChild(o);
			}
		});
		//}}}
		//{{{ We remove options for target
		el = $('target');
		for(var i = el.options.length ; i >= 0 ; i--) {
			el.options[i] = null;
		}
		//}}}
		if ($F(field.id) != '-1') {
			$('target').size = $H(options[$F(field.id)]).values().length;
			$H(options[$F(field.id)]).values().each(function(opt) {
					o = document.createElement('option');
					o.value    = opt['option'].value;
					o.text     = opt['option'].text;
					o.selected = '';
					$('target').appendChild(o);
			});
			$('target').disabled = '';
		}
		$('submit').disabled = 'disabled';
		break;
	case 'target':
		//{{{ We disable submit field if needed
		disabled = 'disabled';
		len = field.options.length;
		nb  = 0;
		i   = 0;
		while (i < len && !field.options[i].selected) {
			i++;
		}
		if (i < len) {
			len = $('source').options.length;
			nb  = 0;
			i   = 0;
			while (i < len && !$('source').options[i].selected) {
				i++;
			}
			if (i < len) {
				disabled = '';
			}
		}
		$('submit').disabled = disabled;
		//}}}
		break;
	case 'source':
		//{{{ We disable submit field if needed
		disabled = 'disabled';
		len = field.options.length;
		nb  = 0;
		i   = 0;
		while (i < len && !field.options[i].selected) {
			i++;
		}
		if (i < len) {
			len = $('target').options.length;
			nb  = 0;
			i   = 0;
			while (i < len && !$('target').options[i].selected) {
				i++;
			}
			if (i < len) {
				disabled = '';
			}
		}
		$('submit').disabled = disabled;
		//}}}
	default:
		break;
	}
}
