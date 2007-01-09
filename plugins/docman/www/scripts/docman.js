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
            spinner:false,
            folderSpinner:false,
            action:'browse'
        }, options || {});
        if (options) {
            this.options.newItem = Object.extend({
                update_permissions_on_init:true,
                hide_permissions:true,
                hide_news:true,
                default_position:false
            }, options.newItem || {});
            this.options.move = Object.extend({
            }, options.move || {});
            this.options.language = Object.extend({
            }, options.language || {});
        }
        
        //Preload spinners
        if (this.options.folderSpinner) {
            img = new Image();
            img.src = this.options.folderSpinner;
        }
        if (this.options.spinner) {
            img = new Image();
            img.src = this.options.spinner;
        }
        
        // ShowOptions
        this.actionsForItem = {};
        this.initShowOptions_already_done = false;
        this.initShowOptionsEvent    = this.initShowOptions.bindAsEventListener(this);
        if (this.options.action == 'browse') Event.observe(window, 'load', this.initShowOptionsEvent, true);
        
        // NewItem
        this.parentFoldersForNewItem = {};
        this.initNewItemEvent        = this.initNewItem.bindAsEventListener(this);
        if (this.options.action == 'browse') Event.observe(window, 'load', this.initNewItemEvent, true);

        // Expand/Collapse
        this.initExpandCollapseEvent = this.initExpandCollapse.bindAsEventListener(this);
        if (this.options.action == 'browse') Event.observe(window, 'load', this.initExpandCollapseEvent, true);
        
        // ItemHighlight
        this.initItemHighlightEvent = this.initItemHighlight.bindAsEventListener(this);
        if (this.options.action == 'browse') Event.observe(window, 'load', this.initItemHighlightEvent, true);
        
        //Focus
        this.focusEvent = this.focus.bindAsEventListener(this);
        Event.observe(window, 'load', this.focusEvent, true);
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
    focus: function() {
        if ($('docman_new_form')) {
            Form.focusFirstElement('docman_new_form');
        }
    },
    //}}}
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
                this.showOptions_Menus[item_id] = new com.xerox.codex.Menu(item_id, this, {close:this.options.language.btn_close});
            }
        }).bind(this));
    },
    //}}}
    //{{{------------------------------ NewItem
    addParentFoldersForNewItem: function (id, parent_id, title) {
        this.parentFoldersForNewItem[id] = {
            id:        id,
            parent_id: parent_id,
            title:    title
        };
    },
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
        
        //{{{Location
        if ($H(this.parentFoldersForNewItem).keys().length) {
            //1. search for the preselected parent
            var folder_id = $H(this.parentFoldersForNewItem).keys().find(
                function (folder_id) {
                    return $F('item_parent_id_'+folder_id);
                }
            );
            
            //2. Construct path
            var folders = [];
            var parent_id = folder_id;
            while(parent_id != 0) {
                folders.push(this.parentFoldersForNewItem[parent_id].title);
                parent_id = this.parentFoldersForNewItem[parent_id].parent_id;
            }
            folders = folders.reverse().join(' / ');
            new Insertion.Top('docman_new_item_location_current_folder', this.options.language.new_in +folders+'&nbsp;');
            
            //3. Hide other folders
            Element.hide('docman_new_item_location_other_folders');
            
            //4. Allow user to be able to change folder
            var a = Builder.node('a', {href:''},'['+this.options.language.new_other_folders+']');
            $('docman_new_item_location_current_folder').appendChild(a);
            Event.observe(a, 'click', function(evt) {
                Element.hide('docman_new_item_location_current_folder');
                Element.show('docman_new_item_location_other_folders');
                
                //{{{Scroll parents to see the selected parent
                Element.scrollTo('item_parent_id_'+folder_id);
                //}}}
                
                Event.stop(evt);
                return false;
            });
            
            //5. Add spinner
            new Insertion.After('docman_new_item_location_position', '<img src="' + this.options.spinner + '" id="docman_new_item_location_spinner" style="display:none" />');
            
            //6. listen for changes => need Ajax call
            $H(this.parentFoldersForNewItem).keys().each(
                (function (folder_id) {
                    Event.observe($('item_parent_id_'+folder_id), 'change', (function (evt) {
                        return this.onNewItemParentChange(folder_id);
                    }).bind(this));
                }).bind(this)
            );
            
            //7. Do manually the first ajax call for the preselected parent
            this.newItem_update_position(folder_id, this.options.newItem.default_position);
            if (this.options.newItem.update_permissions_on_init) {
                this.newItem_update_permissions(folder_id);
            }
        }
        //}}}
        
        //{{{ Permissions
        if ($('docman_new_permissions_panel')) {
            if (this.options.newItem.hide_permissions) {
                new Insertion.Before('docman_new_permissions_panel', '<div id="docman_new_permissions_text">'+this.options.language.new_same_perms_as_parent+' <a href="" onclick="'+
                    'Element.show(\'docman_new_permissions_panel\'); '+
                    'Element.hide(\'docman_new_permissions_text\'); '+
                    'new Insertion.Before(\'docman_new_permissions_panel\', \'<input type=hidden name=user_has_displayed_permissions value=1 />\'); '+
                    'return false;">['+this.options.language.new_view_change+']</a></div>');
                Element.hide('docman_new_permissions_panel');
            } else {
                new Insertion.Before('docman_new_permissions_panel', '<input type=hidden name=user_has_displayed_permissions value=1 />');
            }
        }
        //}}}
        
        //{{{ News
        if ($('docman_new_news_panel')) {
            if (this.options.newItem.hide_news) {
                new Insertion.Before('docman_new_news_panel', '<div id="docman_new_news_text">'+this.options.language.new_news_explaination+' <a href="" onclick="'+
                    'Element.show(\'docman_new_news_panel\'); '+
                    'Element.hide(\'docman_new_news_text\'); '+
                    'new Insertion.Before(\'docman_new_news_panel\', \'<input type=hidden name=user_has_displayed_news value=1 />\'); '+
                    'return false;">['+this.options.language.new_news_displayform+']</a></div>');
                Element.hide('docman_new_news_panel');
            } else {
                new Insertion.Before('docman_new_news_panel', '<input type=hidden name=user_has_displayed_news value=1 />');
            }
        }
        //}}}
    },
    onNewItemParentChange: function (folder_id) {
        this.newItem_update_permissions(folder_id);
        this.newItem_update_position(folder_id);
    },
    newItem_update_position: function (folder_id, default_position) {
        var parameters = '';
        if (default_position) {
            parameters += '&default_position='+default_position;
        }
        if (this.options.move.item_id) {
            parameters += '&exclude='+this.options.move.item_id;
        }
        new Ajax.Updater('docman_new_item_location_position', 
            '?group_id='+ this.group_id +'&action=positionWithinFolder&id=' + folder_id + parameters,
            {
                onComplete:(function(){
                    Element.hide('docman_new_item_location_spinner'); 
                }).bind(this),
                onLoading:function() { 
                    Element.show('docman_new_item_location_spinner'); 
                }
            }
        );
     },
    newItem_update_permissions: function (folder_id) {
        new Ajax.Updater('docman_new_permissions_panel', '?group_id='+ this.group_id +'&action=permissionsForItem&id=' + folder_id);
    },
    _highlight: function(element_name) {
        if (!this['_highlight_'+element_name]) {
            this['_highlight_'+element_name] = new Effect.Highlight(element_name);
        } else {
            this['_highlight_'+element_name].start(this['_highlight_'+element_name].options);
        }
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
        this.close = options.close;
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
            var close_txt = document.createTextNode('['+this.close+']');
            close.appendChild(close_txt);
            li.appendChild(close);
            ul.appendChild(li);
            this.hideEvent = this.hide.bindAsEventListener(this);
            Event.observe(close, 'click', this.hideEvent, true);
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
                    if (action.action.other_icons.length) {
                        var sep = Builder.node('span');
                        sep.innerHTML = '&nbsp;&nbsp;';
                        a.appendChild(sep);
                        var ims = [];
                        action.action.other_icons.each(function (ic) {
                                var im = Builder.node('img');
                                im.src = ic.src;
                                im.title = ic.classe;
                                var sep = Builder.node('span');
                                sep.innerHTML = '&nbsp;&nbsp;';
                                a.appendChild(sep);
                                a.appendChild(im);
                                ims.push({
                                    classe:ic.classe,
                                    url: ic.url,
                                    img: im
                                });
                        });
                        Event.observe(a, 'click', function (evt) {
                            icon = ims.find(function (element) {
                                return element.img == Event.element(evt);
                            });
                            if (icon) {
                                new Ajax.Request(icon.url+'&quick_move='+icon.classe, {
                                    onComplete: function() {
                                        window.location.href = window.location.href;
                                    }
                                });
                                Event.stop(evt);
                                return false;
                            }
                        });
                    }
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
        if (evt) {
            Event.stop(evt);
        }
        return false;
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

  var input = form.elements['item[obsolescence_date]'];

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
    init_obsolescence_date = input.value;
  }

  // Write new date  
  input.value = newdatestr;
}
