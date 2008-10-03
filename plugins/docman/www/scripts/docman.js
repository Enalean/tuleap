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
        
        this.itemHighlight = {};
        
        // ShowOptions
        this.actionsForItem = {};
        this.initShowOptions_already_done = false;
        this.initShowOptionsEvent    = this.initShowOptions.bindAsEventListener(this);
        if (this.options.action == 'browse') document.observe('dom:loaded', this.initShowOptionsEvent);
        
        // NewItem
        this.parentFoldersForNewItem = {};
        this.initNewItemEvent        = this.initNewItem.bindAsEventListener(this);
        if (this.options.action == 'browse') document.observe('dom:loaded', this.initNewItemEvent);

        // Expand/Collapse
        this.initExpandCollapseEvent = this.initExpandCollapse.bindAsEventListener(this);
        if (this.options.action == 'browse') document.observe('dom:loaded', this.initExpandCollapseEvent);

        // Approval table
        this.approvalTableCreateDetailsHidden = false;

        // Table Report
        if (this.options.action == 'browse') {
            this.initTableReportEvent = this.initTableReport.bindAsEventListener(this);
            document.observe('dom:loaded', this.initTableReportEvent);
        }

        //Focus
        this.focusEvent = this.focus.bindAsEventListener(this);
        document.observe('dom:loaded', this.focusEvent, true);
    },
    dispose: function() {
        // ShowOptions
        document.stopObserving('dom:loaded', this.initShowOptionsEvent);
        // NewItem
        document.stopObserving('dom:loaded', this.initNewItemEvent);
        $H(this.newItem.specificProperties).values().each(function (properties) {
            properties.checkbox.stopObserving('click', this.onNewItemCheckboxChangeEvent);
        });
        // Expand/Collapse
        document.stopObserving('dom:loaded', this.initExpandCollapseEvent);
        // Expand/Collapse
        document.stopObserving('dom:loaded', this.focusEvent);
        // Table Report
        if(this.initTableReportEvent) {
            document.stopObserving('dom:loaded', this.initTableReportEvent);
        }
        //itemHighlight
        $H(this.itemHighlight).keys().each(function (item_id) {
            var node = $('item_'+item_id);
            node.stopObserving('mouseover', this.itemHighlight[item_id].mouseover);
            node.stopObserving('mouseout', this.itemHighlight[item_id].mouseout);
        });
    },
    //{{{------------------------------ Focus
    focus: function() {
        if ($('docman_new_form')) {
            Form.focusFirstElement('docman_new_form');
        }
    },
    //}}}
    //{{{------------------------------ Actions
    addActionForItem: function(item_id, action) {
        this.actionsForItem[item_id] = action;
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
            // Without "dom:loaded" IE may crash when attempt to append
            // the iframe to the document. See:
            // http://www.garyharan.com/index.php/2008/04/22/internet-explorer-cannot-open-the-internet-site-operation-aborted/
            document.observe("dom:loaded", function() {
                document.body.appendChild(invisible_iframe);
            });
        }
        //}}}
        if (!this.showOptions_Menus) {
            this.showOptions_Menus = {};
        }
        if (!this.itemHighlight) {
            
        }
        $H(this.actionsForItem).keys().each((function (item_id) {
            if (!this.showOptions_Menus[item_id]) {
                this.showOptions_Menus[item_id] = new com.xerox.codex.Menu(item_id, this, {close:this.options.language.btn_close});
            }
            
            //ItemHighlight
            if (!Prototype.Browser.IE && !this.itemHighlight[item_id]) {
                var node = $('item_'+item_id);
                if (node) {
                    this.itemHighlight[item_id] = {
                        mouseover: (function(event) {
                            node.addClassName('docman_item_highlight');
                            event.stop();
                        }).bindAsEventListener(this),
                        mouseout: (function(event) {
                            node.removeClassName('docman_item_highlight');
                            event.stop();
                        }).bindAsEventListener(this)
                    };
                    node.observe('mouseover', this.itemHighlight[item_id].mouseover);
                    node.observe('mouseout', this.itemHighlight[item_id].mouseout);
                }
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
        var checkboxes = [6, 3, 5, 2, 4,].inject([], function (checkboxes, type) {
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
                    Element.show(properties.panel);
                } else {
                    if (Element.visible(properties.panel)) {
                        Element.hide(properties.panel);
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
                    var icon = element.select('.docman_item_icon').first();
                    icon.src = icon.src.replace('folder.png', 'folder-open.png');
                    var subitems = $('subitems_'+node.id.split('_')[1]);
                    if (subitems) {
                        subitems.show();
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
                                Element.setStyle(document.body, {cursor:'default'});
                                outer.show();
                                icon.src = old_icon_src;
                            }).bind(this)
                        });
                    }
                } else {           //expand --> collapse
                    Element.removeClassName(element, 'docman_item_type_folder_open');
                    Element.addClassName(element, 'docman_item_type_folder');
                    var icon = element.select('.docman_item_icon').first();
                    icon.src = icon.src.replace('folder-open.png', 'folder.png');
                    var subitems = $('subitems_'+node.id.split('_')[1]);
                    if (subitems) {
                        subitems.hide();
                    }
                    new Ajax.Request('?group_id='+ this.group_id +'&action=collapseFolder&view=none&id='+node.id.split('_')[1], {
                        asynchronous:true
                    });
                }
                Event.stop(event);
                return false;
            }).bind(this));
        }).bind(this));
    },
    //}}}

    //{{{----------------------------- Table report
    initTableReport: function() {
        if($('docman_filters_fieldset')) {
            // Setup event observe
            var icon = $('docman_toggle_filters');
            icon.observe('click', this.toggleReport);
            $('plugin_docman_select_saved_report').observe('change', this.reportSelectSavedReport);
            $('plugin_docman_report_add_filter').observe('change',   this.reportSelectAddFilter);
            $('plugin_docman_report_save').observe('change',         this.reportSelectSave.bindAsEventListener(this));

            var url = location.href.parseQuery();
            if(url['del_filter'] || url['add_filter']) {
                icon.src = icon.src.replace('toggle_plus.png', 'toggle_minus.png');
                Element.show('docman_filters_fieldset');
            } else {
                icon.src = icon.src.replace('toggle_minus.png', 'toggle_plus.png');
                Element.hide('docman_filters_fieldset');
            }
        }
    },
    toggleReport: function() {
        // Toggle display
        Element.toggle('docman_filters_fieldset');
        // Change +/- image
        var icon = $('docman_toggle_filters');
        if (icon.src.indexOf('toggle_plus.png') != -1) {
            icon.src = icon.src.replace('toggle_plus.png', 'toggle_minus.png');
        } else {
            icon.src = icon.src.replace('toggle_minus.png', 'toggle_plus.png');
        }
    },
    reportSelectSavedReport: function(event) {
        var form = $('plugin_docman_select_report_id');
        var select = form['report_id'];
        if(select[select.selectedIndex].value != '-1') {
            form.submit();
        }
        Event.stop(event);
        return false;
    },
    reportSelectAddFilter: function(event) {
        var form = $('plugin_docman_report_form');
        var select = form['plugin_docman_report_add_filter'];
        if(select[select.selectedIndex].value != '-1') {
            form.submit();
        }
        Event.stop(event);
        return false;
    },
    // Warning: The 2 "Insersion after" should have their values (name) escaped to avoid XSS.
    // But I think this kind of attack cannot be used against codex.
    reportSelectSave: function(event) {
        var form = $('plugin_docman_report_form');
        var select = form['plugin_docman_report_save'];
        var value = select[select.selectedIndex].value;
        var nameField = Builder.node('input', {type: 'hidden', name: 'report_name'});
        if((value == 'newp') || (value == 'newi')) {
            // Create new report
            var name = window.prompt(this.options.language.report_name_new, '');
            if(name != null && name.strip() != '') {
                nameField.value = name.escapeHTML().replace(/\"/, '&quot;');
                form.appendChild(nameField);
                form.submit();
            }
        } else {
            // Update existing report
            var selectedValue = parseInt(value)
            if(selectedValue > 0) {
                var name = window.prompt(this.options.language.report_name_upd, select.options[select.selectedIndex].innerHTML.unescapeHTML());
                if(name != null && name.strip() != '') {
                    nameField.value = name.escapeHTML().replace(/\"/, '&quot;');
                    form.appendChild(nameField);
                    form.submit();
                }
            }
        }
        Event.stop(event);
        return false;
    },
    //}}}
    //{{{----------------------------- Approval table create
    approvalTableCreate: function(form) {
        var selected;
        for(var i = 0; i < form['app_table_import'].length; i++) {
            if(form['app_table_import'][i].checked) {
                selected = form['app_table_import'][i].value;
            }
        }
        switch(selected) {
        case "copy":
        case "reset":
        case "empty":
            if(!this.approvalTableCreateDetailsHidden) {
                this.approvalTableCreateDetailsHidden = true;
                Element.hide($('docman_approval_table_create_settings'));
                Element.hide($('docman_approval_table_create_notification'));
                Element.hide($('docman_approval_table_create_table'));
                Element.hide($('docman_approval_table_create_add_reviewers'));
            }
            break;
        default:
            if(this.approvalTableCreateDetailsHidden) {
                this.approvalTableCreateDetailsHidden = false;
                Element.show($('docman_approval_table_create_settings'));
                Element.show($('docman_approval_table_create_notification'));
                Element.show($('docman_approval_table_create_table'));
                Element.show($('docman_approval_table_create_add_reviewers'));
            }
            break;
        }
    }
});

com.xerox.codex.openedMenu = null;
com.xerox.codex.Menu = Class.create();
Object.extend(com.xerox.codex.Menu.prototype, {
    initialize:function(item_id, docman, options) {
        this.item_id = item_id;
        this.docman = docman;
        this.close = options.close;
        this.defaultUrl = this.docman.options.pluginPath+'/index.php?group_id='+this.docman.group_id+'&id='+item_id;
        Event.observe($('docman_item_show_menu_'+item_id), 'click', this.show.bind(this));
    },
    _createLi: function(element) {
        var li = Builder.node('li');
        li.appendChild(element);
        return li;
    },
    _createQuickMoveIcon: function (icon) {
        var im = Builder.node('img', {
            'src':   this.docman.options.themePath+"/images/ic/"+icon+'.png',
            'title': icon
        });
        
        Event.observe(im, 'click', function (evt) {
            if(icon) {
                new Ajax.Request(this.defaultUrl+'&action=move&quick_move='+icon, {
                    'onComplete': function() {
                        window.location.href = window.location.href;
                    }
                });
                Event.stop(evt);
                return false;
            }
        }.bindAsEventListener(this));
        return im;
    },
    _appendQuickMoveIcon: function (element, action) {
        var sep = Builder.node('span');
        sep.innerHTML = '&nbsp;&nbsp;';
        element.appendChild(sep);
        element.appendChild(this._createQuickMoveIcon(action));
    },
    _getNewFolder: function () {
        var a = Builder.node('a', {
            'href': this.defaultUrl+'&action=newFolder',
            'class': 'docman_item_option_newfolder',
            'title': this.docman.options.language.action_newfolder});
        var title_txt = document.createTextNode(this.docman.options.language.action_newfolder);
        a.appendChild(title_txt);
        return this._createLi(a);
    },
    _getNewDocument: function() {
        var a = Builder.node('a', {
            'href': this.defaultUrl+'&action=newDocument',
            'class': 'docman_item_option_newdocument',
            'title': this.docman.options.language.action_newdocument});
        var title_txt = document.createTextNode(this.docman.options.language.action_newdocument);
        a.appendChild(title_txt);
        return this._createLi(a);  
    },
    _getProperties: function () {
        var a = Builder.node('a', {
            'href': this.defaultUrl+'&action=details',
            'class': 'docman_item_option_details',
            'title': this.docman.options.language.action_details});
        var title_txt = document.createTextNode(this.docman.options.language.action_details);
        a.appendChild(title_txt);
        return this._createLi(a);
    },
    _getNewVersion: function () {
        var a = Builder.node('a', {
            'href': this.defaultUrl+'&action=action_new_version',
            'class': 'docman_item_option_newversion',
            'title': this.docman.options.language.action_newversion});
        var title_txt = document.createTextNode(this.docman.options.language.action_newversion);
        a.appendChild(title_txt);
        return this._createLi(a);
    },
    _getMove: function () {
        var a = Builder.node('a', {
            'href': this.defaultUrl+'&action=move',
            'class': 'docman_item_option_move',
            'title': this.docman.options.language.action_move});
        var title_txt = document.createTextNode(this.docman.options.language.action_move);
        a.appendChild(title_txt);
        this._appendQuickMoveIcon(a, 'move-up');
        this._appendQuickMoveIcon(a, 'move-down');
        this._appendQuickMoveIcon(a, 'move-beginning');
        this._appendQuickMoveIcon(a, 'move-end');
        return this._createLi(a);
    },
    _getPermissions: function () {
        var a = Builder.node('a', {
            'href': this.defaultUrl+'&action=details&section=permissions',
            'class': 'docman_item_option_permissions',
            'title': this.docman.options.language.action_permissions});
        var title_txt = document.createTextNode(this.docman.options.language.action_permissions);
        a.appendChild(title_txt);
        return this._createLi(a);
    },
    _getHistory: function () {
        var a = Builder.node('a', {
            'href': this.defaultUrl+'&action=details&section=history',
            'class': 'docman_item_option_history',
            'title': this.docman.options.language.action_history});
        var title_txt = document.createTextNode(this.docman.options.language.action_history);
        a.appendChild(title_txt);
        return this._createLi(a);
    },
    _getNotification: function () {
        var a = Builder.node('a', {
            'href': this.defaultUrl+'&action=details&section=notifications',
            'class': 'docman_item_option_notifications',
            'title': this.docman.options.language.action_notifications});
        var title_txt = document.createTextNode(this.docman.options.language.action_notifications);
        a.appendChild(title_txt);
        return this._createLi(a);
    },
    _getDelete: function () {
        var a = Builder.node('a', {
            'href': this.defaultUrl+'&action=confirmDelete',
            'class': 'docman_item_option_delete',
            'title': this.docman.options.language.action_delete});
        var title_txt = document.createTextNode(this.docman.options.language.action_delete);
        a.appendChild(title_txt);
        return this._createLi(a);
    },
    _getUpdate: function () {
        var a = Builder.node('a', {
            'href': this.defaultUrl+'&action=action_update',
            'class': 'docman_item_option_update',
            'title': this.docman.options.language.action_update});
        var title_txt = document.createTextNode(this.docman.options.language.action_update);
        a.appendChild(title_txt);
        return this._createLi(a);
    },
    _getCopy: function () {
        var a = Builder.node('a', {
            'href': this.defaultUrl+'&action=action_copy',
            'class': 'docman_item_option_copy',
            'title': this.docman.options.language.action_copy});
        var title_txt = document.createTextNode(this.docman.options.language.action_copy);
        a.appendChild(title_txt);
        Event.observe(a, 'click', function (evt) {
            new Ajax.Request(this.defaultUrl+'&action=action_copy&ajax_copy=true', {
                'onComplete': function() {
                    // Hide menu
                    this.hide();

                    // Display feedback message
                    var li = Builder.node('li');

                    // Search item title
                    var title = $('docman_item_title_link_'+this.item_id).firstChild.nodeValue;
                    li.innerHTML = '"'+title+'" '+this.docman.options.language.feedback_copy;

                    if($('item_copied')) {
                        $('item_copied').remove();
                    }                    
                    var ul = Builder.node('ul', {'class': 'feedback_info', 'id': 'item_copied'});
                    ul.appendChild(li);
                    var feedback = $('feedback');
                    feedback.appendChild(ul);
                    feedback.show();
                    
                    // There is sth to paste.
                    this.docman.canPaste = true;
                }.bindAsEventListener(this)
            });
            Event.stop(evt);
            return false;
        }.bindAsEventListener(this));
        return this._createLi(a);
    },
    _getPaste: function () {
        var a = Builder.node('a', {
            'href': this.defaultUrl+'&action=action_paste',
            'class': 'docman_item_option_paste',
            'title': this.docman.options.language.action_paste});
        var title_txt = document.createTextNode(this.docman.options.language.action_paste);
        a.appendChild(title_txt);
        return this._createLi(a);
    },
    _getApproval: function () {
        var a = Builder.node('a', {
            'href': this.defaultUrl+'&action=details&section=approval',
            'class': 'docman_item_option_approval',
            'title': this.docman.options.language.action_approval});
        var title_txt = document.createTextNode(this.docman.options.language.action_approval);
        a.appendChild(title_txt);
        return this._createLi(a);
    },
    show:function(evt) {
        var menu = 'docman_item_menu_'+this.item_id;
        // In the previous version of the menu, once a menu was built for an
        // item, it was cached and re-used as is if user close and then re-open
        // it later. As now copy/paste is done via an Ajax request, we must
        // rebuild the menu to take this toogle into account.
         
        //if (!$(menu)) {
            //Save the offset
            Position.prepare();
            this.offset = Position.cumulativeOffset($('docman_item_show_menu_'+this.item_id));
            
            //Build the menu
            var actions_panel = Builder.node('div', {
                'style': 'display:none;top:0px;left:0px;z-index:1001',
                'id': menu,
                'class':'docman_item_menu'
            });
            
            document.body.appendChild(actions_panel);
            var ul = Builder.node('ul', {
                'id':'docman_item_menu_ul_'+this.item_id
            });
            var li = Builder.node('li', {
                'class':'docman_item_menu_close'
            });
            var close = Builder.node('a', {
                'href':'#close-menu'
            });
            var close_txt = document.createTextNode('Id: '+this.item_id+' ['+this.close+']');
            close.appendChild(close_txt);
            li.appendChild(close);
            ul.appendChild(li);
            this.hideEvent = this.hide.bindAsEventListener(this);
            Event.observe(close, 'click', this.hideEvent);
            
            //
            // All the supported actions
            //
            
            // New folder
            if(this.docman.actionsForItem[this.item_id].canNewFolder)
                ul.appendChild(this._getNewFolder(this.item_id));
            // New document
            if(this.docman.actionsForItem[this.item_id].canNewDocument)
                ul.appendChild(this._getNewDocument(this.item_id));
            // Properties
            ul.appendChild(this._getProperties());
            // New version (files)
            if(this.docman.actionsForItem[this.item_id].canNewVersion)
                ul.appendChild(this._getNewVersion());
            // Update (empty, wiki, link)
            if(this.docman.actionsForItem[this.item_id].canUpdate)
               ul.appendChild(this._getUpdate());
            // Notification
            ul.appendChild(this._getNotification());
            // History
            ul.appendChild(this._getHistory());
            // Permissions
            if(this.docman.actionsForItem[this.item_id].canPermissions)
                ul.appendChild(this._getPermissions());
            // Approval table
            if(this.docman.actionsForItem[this.item_id].canApproval)
                ul.appendChild(this._getApproval());
            // Move
            if(this.docman.actionsForItem[this.item_id].canMove)
                ul.appendChild(this._getMove());
            // Copy
            ul.appendChild(this._getCopy());
            // Paste
            if(this.docman.actionsForItem[this.item_id].canPaste)
                ul.appendChild(this._getPaste());
            // Delete
            if(this.docman.actionsForItem[this.item_id].canDelete)
                ul.appendChild(this._getDelete());
                
            actions_panel.appendChild(ul);
            
            //dimensions
            this.dimensions = Element.getDimensions(actions_panel);
            
        //}
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
