
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
var fields       = {};
var options      = {};
var rules        = [];
var dependencies = {};
var selections   = {};
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


function buildAdminUI() {
	
	html = messages['if_then'];
	
	ms = document.createElement('select');
	ms.id = ms.name = 'master_field';
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
	msv.id                  = 'master';
	msv.name                = 'master[]';
	msv.disabled            = 'disabled';
	html = html.replace(/%2/, getOuterHTML(msv));
	
	ss = ms.cloneNode(true);
	ss.id = ss.name = 'slave_field';
	ss.disabled = 'disabled';
	html = html.replace(/%3/, getOuterHTML(ss));
	
	ssv = document.createElement('select');
	ssv.multiple            = 'multiple';
	ssv.style.verticalAlign = 'top';
	ssv.id                  = 'slave';
	ssv.name                = 'slave[]';
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
	
	$('master_field').onchange = function() { admin_fieldHasChanged(this); }
	$('master').onchange       = function() { admin_fieldHasChanged(this); }
	$('slave_field').onchange  = function() { admin_fieldHasChanged(this); }
	$('slave').onchange        = function() { admin_fieldHasChanged(this); }
	
}

function getOuterHTML (node) {
	var html = '';
	switch (node.nodeType) {
		case Node.ELEMENT_NODE:
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
		case Node.TEXT_NODE:
			html += node.nodeValue;
			break;
		case Node.COMMENT_NODE:
			html += '<!' + '--' + node.nodeValue + '--' + '>';
			break;
	}
	return html;
}


function admin_fieldHasChanged(field) {
	switch (field.id) {
	case 'master_field':
		//{{{ We remove options for slave
		reset = $('slave_field', 'slave', 'master');
		reset.each(function (el) {
			for(var i = el.options.length ; i >= 0 ; i--) {
				el.options[i] = null;
			}
			el.size     = 0;
			el.disabled = 'disabled';
		});
		//}}}
		if ($F(field.id) != '-1') {
			$('master').size = $H(options[$F(field.id)]).values().length;
			$H(options[$F(field.id)]).values().each(function(opt) {
					o = new Option(opt['option'].text, opt['option'].value);
					o.selected = '';
					$('master').appendChild(o);
			});
			//{{{ We remove master field from slave field
			$('slave_field').appendChild(new Option(messages['choose_field']), '-1');
			$H(fields).values().each(function(slave_field) {
					if (slave_field.id != $F(field.id)) {
						$('slave_field').appendChild(new Option(slave_field.name, slave_field.id));
					}
			});
			//}}}
			$('master').disabled      = '';
			$('slave_field').disabled = '';
		}
		break;
	case 'slave_field':
		//{{{ We remove slave field from master field
		el = $('master_field');
		old = $F('master_field');
		for(var i = el.options.length ; i >= 0 ; i--) {
			el.options[i] = null;
		}
		el.appendChild(new Option(messages['choose_field']), '-1');
		$H(fields).values().each(function(master_field) {
			if (master_field.id != $F(field.id)) {
				o = new Option(master_field.name, master_field.id);
				if (old == o.value) {
					o.selected = 'selected';
				}
				el.appendChild(o);
			}
		});
		//}}}
		//{{{ We remove options for slave
		el = $('slave');
		for(var i = el.options.length ; i >= 0 ; i--) {
			el.options[i] = null;
		}
		//}}}
		if ($F(field.id) != '-1') {
			$('slave').size = $H(options[$F(field.id)]).values().length;
			$H(options[$F(field.id)]).values().each(function(opt) {
					o = document.createElement('option');
					o.value    = opt['option'].value;
					o.text     = opt['option'].text;
					o.selected = '';
					$('slave').appendChild(o);
			});
			$('slave').disabled = '';
		}
		$('submit').disabled = 'disabled';
		break;
	case 'slave':
		//{{{ We disable submit field if needed
		disabled = 'disabled';
		len = field.options.length;
		nb  = 0;
		i   = 0;
		while (i < len && !field.options[i].selected) {
			i++;
		}
		if (i < len) {
			len = $('master').options.length;
			nb  = 0;
			i   = 0;
			while (i < len && !$('master').options[i].selected) {
				i++;
			}
			if (i < len) {
				disabled = '';
			}
		}
		$('submit').disabled = disabled;
		//}}}
		break;
	case 'master':
		//{{{ We disable submit field if needed
		disabled = 'disabled';
		len = field.options.length;
		nb  = 0;
		i   = 0;
		while (i < len && !field.options[i].selected) {
			i++;
		}
		if (i < len) {
			len = $('slave').options.length;
			nb  = 0;
			i   = 0;
			while (i < len && !$('slave').options[i].selected) {
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
