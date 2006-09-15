if (!com) var com = {};
if (!com.xerox) com.xerox = {};
if (!com.xerox.codex) com.xerox.codex = {};
/**
 * Debug function.
 * Just give a msg as arguments, 
 * and it will be displayed inside 
 * a textarea at the end of the page.
 * Other js loggers was a little bit
 * too heavy for a simple feature.
 */
function xgs_debug(msg) {
    d = $('debug_console');
    if (!d) {
        d = document.createElement('textarea');
        d.rows = 50;
        d.cols = 160;
        d.id = 'debug_console';
        document.body.appendChild(d);
    }
    now = new Date();
    h = now.getHours();
    m = now.getMinutes();
    s = now.getSeconds();
    ms = now.getMilliseconds();
    d.value = '['+h+':'+m+':'+s+'.'+ms+']\t'+(msg || '')+'\n' + d.value;
}

if (!document.getElementsByClassNames) {
    document.getElementsByClassNames = function(classNames, parentElement) {
        var children = ($(parentElement) || document.body).getElementsByTagName('*');
        return $A(children).inject([], function(elements, child) {
            if (classNames.find(function (className) {
                return child.className.match(new RegExp("(^|\\s)" + className + "(\\s|$)"));
            })) {
                elements.push(child);
            }
            return elements;
        });
    }
}

com.xerox.codex.Docman = Class.create();
Object.extend(com.xerox.codex.Docman.prototype, {
    initialize: function(group_id, options) {
        if (!group_id) {
            throw 'group_id is mandatory!';
        }
        this.group_id = group_id;
        this.options = Object.extend({
            folderSpinner:false,
            action:'browse'
        }, options || {});
        
        //Preload spinner
        if (this.options.folderSpinner) {
            img = new Image();
            img.src = this.options.folderSpinner;
        }
        
        // ShowOptions
        this.actionsForItem = {};
        this.initShowOptions_already_done = false;
        this.initShowOptionsEvent    = this.initShowOptions.bindAsEventListener(this);
        if (this.options.action == 'browse') Event.observe(window, 'load', this.initShowOptionsEvent, true);
        
        // NewItem
        this.initNewItemEvent        = this.initNewItem.bindAsEventListener(this);
        if (this.options.action == 'browse') Event.observe(window, 'load', this.initNewItemEvent, true);

        // Expand/Collapse
        this.initExpandCollapseEvent = this.initExpandCollapse.bindAsEventListener(this);
        if (this.options.action == 'browse') Event.observe(window, 'load', this.initExpandCollapseEvent, true);
        
        // ItemHighlight
        this.initItemHighlightEvent = this.initItemHighlight.bindAsEventListener(this);
        if (this.options.action == 'browse') Event.observe(window, 'load', this.initItemHighlightEvent, true);
        
    },
    dispose: function() {
        // ShowOptions
        Event.stopObserving(window, 'load', this.initShowOptionsEvent, true);
        // NewItem
        Event.stopObserving(window, 'load', this.initNewItemEvent, true);
        $H(this.newItem.specificProperties).values().each(function (properties) {
            Event.stopObserving(properties.checkbox, 'change', this.onNewItemCheckboxChangeEvent);
        });
        // Expand/Collapse
        Event.stopObserving(window, 'load', this.initExpandCollapseEvent, true);
        // ItemHighlight
        Event.stopObserving(window, 'load', this.initItemHighlightEvent, true);
    },
    //{{{------------------------------ ItemHighlight
    initItemHighlight: function() {
        this._initItemHighlight(document.body);
    },
    _initItemHighlight:function(parent_element) {
        document.getElementsByClassName('docman_item_title', parent_element).each(function (element) {
            var item_ = new RegExp("^item_.*");
            //We search the first parent which has id == "item_%"
            var node = element.parentNode;
            while (!node.id.match(item_)) {
                node = node.parentNode;
            }
            Event.observe(node, 'mouseover', function(event) {
                Element.addClassName(node, 'docman_item_highlight');
                Event.stop(event);
            });
            Event.observe(node, 'mouseout', function(event) {
                Element.removeClassName(node, 'docman_item_highlight');
                Event.stop(event);
            });
        });
    },
    //}}}
    //{{{------------------------------ Actions
    addActionForItem: function(item_id, action) {
        if (!this.actionsForItem[item_id]) {
            this.actionsForItem[item_id] = {
                actions:[],
                interval:null,
                effect:null
            };
        }
        this.actionsForItem[item_id].actions.push({action:action,created:false});
        if (this.initShowOptions_already_done) {
            this.initShowOptions();
        }
    },
    initShowOptions: function() {
        this.initShowOptions_already_done = true;
        //{{{ IE Hack
        // Microsoft said:
        //   All windowless elements are rendered on the same MSHTML plane, 
        //   and windowed elements draw on a separate MSHTML plane. 
        //   You can use z-index to manipulate elements on the same plane 
        //   but not to mix and match with elements in different planes. 
        //   You can rearrange the z-indexing of the elements on each plane, 
        //   but the windowed plane always draws on the top of 
        //   the windowless plane.
        // Selectboxes are windowed therefore we need to put an invisible iframe 
        // on top of them to be able to display menus.
        var invisible_iframe = $('docman_item_menu_invisible_iframe');
        if (!invisible_iframe) {
            invisible_iframe = Builder.node('iframe', {
                    id:'docman_item_menu_invisible_iframe',
                    style:'position:absolute;display:none;z-index:1000;width:200px;height:100px;',
                    frameborder:0,
                    scrolling:'no',
                    marginwidth:0,
                    marginheight:0,
                    src:'/plugins/docman/blank.htm'
            });
            document.body.appendChild(invisible_iframe);
        }
        //}}}
        $H(this.actionsForItem).keys().each((function (item_id) {
            if (!this.showOptions_Menus) {
                this.showOptions_Menus = {};
            }
            if (!this.showOptions_Menus[item_id]) {
                this.showOptions_Menus[item_id] = new com.xerox.codex.Menu(item_id, this);
            }
        }).bind(this));
    },
    //}}}
    //{{{------------------------------ NewItem
    initNewItem: function() {
        var checkboxes = [3, 5, 2, 4,].inject([], function (checkboxes, type) {
            el = $('item_item_type_'+type);
            if (el) {
                checkboxes.push(el);
            }
            return checkboxes;
        });
        this.newItem = {
            specificProperties: {}
        };
        checkboxes.each((function (checkbox) {
            panel = $(checkbox.id+'_specific_properties');
            this.newItem.specificProperties[checkbox.id] = {
                checkbox: checkbox,
                panel: panel
            };
            if (panel && !checkbox.checked) {
                Element.hide(panel);
            }
            this.onNewItemCheckboxChangeEvent = this.onNewItemCheckboxChange.bindAsEventListener(this);
            Event.observe(checkbox, 'click', this.onNewItemCheckboxChangeEvent);
        }).bind(this));
    },
    onNewItemCheckboxChange: function(event) {
        var selected_checkbox = Event.element(event);
        if (selected_checkbox.htmlFor) { //The user has click on the label
            selected_checkbox = $(selected_checkbox.htmlFor);
        }
        $H(this.newItem.specificProperties).values().each(function (properties) {
            if (properties.panel) {
                if (properties.checkbox.id == selected_checkbox.id) {
                    //Element.show(properties.panel);
                    new Effect.SlideDown(properties.panel, {
                        duration:0.25
                    });
                } else {
                    //Element.hide(properties.panel);
                    if (Element.visible(properties.panel)) {
                        new Effect.SlideUp(properties.panel, {
                            duration:0.25
                        });
                    }
                }
            }
        });
    },
    //}}}
    //{{{----------------------------- Expand/Collapse
    initExpandCollapse: function() {
        this._expandCollapse(document.body);
    },
    _expandCollapse:function (parent_element) {
        var docman_item_type_folder = new RegExp("(^|\\s)" + 'docman_item_type_folder' + "(\\s|$)");
        var item_ = new RegExp("^item_.*");
        document.getElementsByClassNames(['docman_item_type_folder', 'docman_item_type_folder_open'], parent_element).each((function (element) {
            Event.observe(element, 'click', (function (event) {
                var element = Event.element(event).parentNode; //element == image, element.parentNode == link
                //We search the first parent which has id == "item_%"
                var node = element.parentNode.parentNode;
                while (!node.id.match(item_)) {
                    node = node.parentNode;
                }
                if (element.className.match(docman_item_type_folder)) {         //collapse --> expand
                    Element.removeClassName(element, 'docman_item_type_folder');
                    Element.addClassName(element, 'docman_item_type_folder_open');
                    var icon = document.getElementsByClassName('docman_item_icon', element)[0];
                    icon.src = icon.src.replace('folder.png', 'folder-open.png');
                    var subitems = $('subitems_'+node.id.split('_')[1]);
                    if (subitems) {
                        Element.show(subitems);
                        /*
                        Effect.toggle(subitems, 'slide', {
                            duration:0.25
                        });
                        /**/
                        new Ajax.Request('?group_id='+ this.group_id +'&action=expandFolder&view=none&id='+node.id.split('_')[1], {
                            asynchronous:true
                        });
                    } else {
                        old_icon_src = icon.src;
                        if (this.options.folderSpinner) {
                            icon.src = this.options.folderSpinner;
                        }
                        var target = Builder.node('div');
                        var outer = Builder.node('div');
                        outer.appendChild(target);
                        node.appendChild(outer);
                        Element.hide(outer);
                        
                        Element.setStyle(document.body, {cursor:'wait'});
                        expandUrl = '?group_id='+ this.group_id +'&view=ulsubfolder&action=expandFolder&id='+node.id.split('_')[1];
                        new Ajax.Updater(target, expandUrl, {
                            asynchronous:true,
                            evalScripts:true,
                            onComplete: (function(transport) {
                                if (!transport.responseText.length) {
                                    fake = Builder.node('div', {id:'subitems_'+node.id.split('_')[1]});
                                    target.appendChild(fake);
                                }
                                this._expandCollapse(target);    //
                                this.initShowOptions();          //register events for new loaded items
                                this._initItemHighlight(target); //
                                Element.setStyle(document.body, {cursor:'default'});
                                new Effect.SlideDown(outer, {
                                    duration:0.25
                                });
                                icon.src = old_icon_src;
                            }).bind(this)
                        });
                    }
                } else {           //expand --> collapse
                    Element.removeClassName(element, 'docman_item_type_folder_open');
                    Element.addClassName(element, 'docman_item_type_folder');
                    var icon = document.getElementsByClassName('docman_item_icon', element)[0];
                    icon.src = icon.src.replace('folder-open.png', 'folder.png');
                    var subitems = $('subitems_'+node.id.split('_')[1]);
                    if (subitems) {
                        Element.hide(subitems);
                        /*
                        Effect.toggle(subitems, 'slide', {
                            duration:0.25
                        });
                        /**/
                    }
                    new Ajax.Request('?group_id='+ this.group_id +'&action=collapseFolder&view=none&id='+node.id.split('_')[1], {
                        asynchronous:true
                    });
                }
                Event.stop(event);
                return false;
            }).bind(this));
        }).bind(this));
    }
    //}}}
});

com.xerox.codex.openedMenu = null;
com.xerox.codex.Menu = Class.create();
Object.extend(com.xerox.codex.Menu.prototype, {
    initialize:function(item_id, docman, options) {
        this.item_id = item_id;
        this.docman = docman;
        Event.observe($('docman_item_show_menu_'+item_id), 'click', this.show.bind(this));
    },
    show:function(evt) {
        var menu = 'docman_item_menu_'+this.item_id;
        if (!$(menu)) {
            //Save the offset
            Position.prepare();
            this.offset = Position.cumulativeOffset($('docman_item_show_menu_'+this.item_id));
            
            //Build the menu
            var actions_panel = Builder.node('div', {
                style:'display:none;top:0px;left:0px;z-index:1001',
                id:menu,
                'class':'docman_item_menu'
            });
            
            document.body.appendChild(actions_panel);
            var ul = Builder.node('ul', {
                id:'docman_item_menu_ul_'+this.item_id
            });
            var li = Builder.node('li', {
                'class':'docman_item_menu_close'
            });
            var close = Builder.node('a', {
                href:'#close-menu'
            });
            var close_txt = document.createTextNode('[close]');
            close.appendChild(close_txt);
            li.appendChild(close);
            ul.appendChild(li);
            this.hideEvent = this.hide.bindAsEventListener(this);
            Event.observe(close, 'click', this.hideEvent);
            docman.actionsForItem[this.item_id].actions.each((function (action) {
                if (!action.created) {
                    var li = Builder.node('li');
                    var a = Builder.node('a', {
                        href:action.action.href,
                        'class':action.action.classes,
                        title:action.action.title
                    });
                    var title_txt = document.createTextNode(action.action.title);
                    a.appendChild(title_txt);
                    li.appendChild(a);
                    ul.appendChild(li);
                    action.created = true;
                }
            }).bind(this));
            actions_panel.appendChild(ul);
            
            //dimensions
            this.dimensions = Element.getDimensions(actions_panel);
            
        }
        if (!com.xerox.codex.openedMenu || com.xerox.codex.openedMenu != menu) {
            this.hide();
            com.xerox.codex.openedMenu = menu;
            Element.setStyle('docman_item_menu_invisible_iframe', {
                width:this.dimensions.width+'px',
                height:this.dimensions.height+'px'
            });
            var pos = {
                left:Event.pointerX(evt)+'px',
                top:Event.pointerY(evt)+'px'
            };
            ['docman_item_menu_invisible_iframe', menu].each(function (element) { 
                Element.setStyle(element, pos); 
                Element.show(element); 
            });
        }
        Event.stop(evt);
        return false;
    },
    hide:function(evt) {
        if (com.xerox.codex.openedMenu) {
            ['docman_item_menu_invisible_iframe', com.xerox.codex.openedMenu].each(function (element) { Element.hide(element); });
            com.xerox.codex.openedMenu = null;
        }
    }
});

if (!init_obsolescence_date) var init_obsolescence_date = -1;
function change_obsolescence_date(form) {  
  // Find selected value
  var element = form.validity;  
  var selected;
  for(var i = 0; i < element.options.length; i++) {
    if(element.options[i].selected) {
      selected = element.options[i].value;
    }
  }

  var input = form.obsolescence_date;

  // Compute new date  
  var newdatestr = "";
  switch(selected) {
    case "0":

      break;
  
    case "100":
      if(init_obsolescence_date == -1) {
	input.focus();
      }
      else {
        newdatestr = init_obsolescence_date;
      }
      break;

    case "200":
      var today = new Date();
      var newDate = new Date(today.getFullYear(), today.getMonth(), today.getDate(), 0, 0, 0, 0);
      newdatestr =  newDate.getFullYear()+"-"+(newDate.getMonth()+1)+"-"+newDate.getDate();
      break;

    default:
      var today = new Date();
      var newDateMonth = parseInt(selected) + today.getMonth();
      var newDate = new Date(today.getFullYear(), newDateMonth, today.getDate(), 0, 0, 0, 0);
      newdatestr = newDate.getFullYear()+"-"+(newDate.getMonth()+1)+"-"+newDate.getDate();
  }

  if(init_obsolescence_date == -1) {
    init_obsolescence_date = form.obsolescence_date.value;
  }

  // Write new date  
  input.value = newdatestr;
}
