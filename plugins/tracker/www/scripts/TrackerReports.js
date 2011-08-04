/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
* Originally written by Nicolas Terray, 2009
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

var codendi = codendi || { };
codendi.tracker = codendi.tracker || { };
codendi.tracker.report = codendi.tracker.report || { };

codendi.tracker.report.setHasChanged = function () {
    if (!$('tracker_report_selection').hasClassName('tracker_report_haschanged') && !$('tracker_report_selection').hasClassName('tracker_report_haschanged_and_isobsolete')) {
        if ($('tracker_report_selection').hasClassName('tracker_report_isobsolete')) {
            $('tracker_report_selection').removeClassName('tracker_report_isobsolete')
            $('tracker_report_selection').addClassName('tracker_report_haschanged_and_isobsolete');
        } else {
            $('tracker_report_selection').addClassName('tracker_report_haschanged');
        }
    }
};
Ajax.Responders.register({
    onComplete: function (response) {
        if (response.getHeader('X-Codendi-Tracker-Report-IsObsolete')) {
            if ($('tracker_report_selection')) {
                $$('.tracker_report_updated_by').invoke('update', response.getHeader('X-Codendi-Tracker-Report-IsObsolete'));
                if (!$('tracker_report_selection').hasClassName('tracker_report_isobsolete') && !$('tracker_report_selection').hasClassName('tracker_report_haschanged_and_isobsolete')) {
                    if ($('tracker_report_selection').hasClassName('tracker_report_haschanged')) {
                        $('tracker_report_selection').removeClassName('tracker_report_haschanged')
                        $('tracker_report_selection').addClassName('tracker_report_haschanged_and_isobsolete');
                    } else {
                        $('tracker_report_selection').addClassName('tracker_report_isobsolete');
                    }
                }
            }
        }
    }
    //TODO: REMOVE THIS DEBUG FEATURE ---v
    ,onException: function (request, e) {
        if (console && console.debug) {
            console.debug(e);
        } else {
            alert(e.message); 
        }
    }
});

codendi.tracker.report.table = codendi.tracker.report.table || { };

codendi.tracker.report.table.saveColumnsWidth = function (table, onComplete) {
    var total = table.offsetWidth - 16;
    var parameters = {
        func:     'renderer',
        renderer: $('tracker_report_table_addcolumn_form').renderer.value
    };
    var cells = table.rows[0].cells;
    var n = cells.length;
    var id;
    for (var i = 1 ; i < n ; i++) {
        if (id = cells[i].id) {
            if (id = id.match(/_(\d+)$/)[1]) {
                parameters['renderer_table[resize-column][' + id + ']'] = Math.round(cells[i].offsetWidth * 100 / total);
            }
        }
    }
    var onComplete = onComplete || Prototype.emptyFunction;
    var req = new Ajax.Request(location.href, {
        parameters: parameters,
        onComplete: function (transport) {
            onComplete();
            codendi.tracker.report.setHasChanged();
        }
    });
};

codendi.tracker.report.table.AddRemoveColumn = Class.create({
    /**
     * Constructor
     */
    initialize: function (selectbox) {
        this.toggle_event = this.toggle.bindAsEventListener(this);
        this.prefix       = 'tracker_report_table_add_column_';
        
        var panel = new Element('table').addClassName('dropdown_panel').setStyle({
            textAlign: 'left',
            opacity: 0.9
        });
        var ul = new Element('ul');
        var btn_label = '';
        selectbox.childElements().each((function (el, index) {
            if (index) {
                if (el.tagName.toLowerCase() === 'option') {
                    ul.appendChild(this.buildCol(el.value, el.className, el.innerHTML));
                } else {
                    var subul = new Element('ul', {id: el.id});
                    el.childElements().each(function (subel) {
                        subul.appendChild(this.buildCol(subel.value, subel.className, subel.innerHTML));
                    }.bind(this));
                    ul.appendChild(new Element('li').update(new Element('strong').update(el.label)).insert(subul).setStyle({
                        display: subul.childElements().length ? 'block' : 'none'
                    }));
                }
            } else { //the first one, "-- Add column". don't need it.
                btn_label = el.text.gsub(/^--\s*/, '');
            }
        }).bind(this));
        var handle = new Element('a', {
            href: '#', 
            title: btn_label,
            style: "font-size: 0.8em;"
        }).update(btn_label + ' <img src="' + codendi.imgroot + 'ic/sort--plus.png" style="vertical-align: middle;" />');
        
        Element.remove(
            selectbox.insert({
                after: handle 
            })
        );
        ul.id = selectbox.id;
        handle.insert({
            after: panel.insert(
                new Element('tbody').insert(
                    new Element('tr').insert(
                        new Element('td').insert(ul)
                    )
                )
            )
        });
        this.panel = new codendi.DropDownPanel(panel, handle);
    },
    /**
     * Build a li in the dropdown panel to toggle a column
     */
    buildCol: function (id, className, label) {
        return new Element('li', 
            { 
                id: this.prefix + id
            }).addClassName(className)
            .update(label)
            .observe('click', this.toggle_event);
    },
    /**
     * event listener to toggle a column
     */
    toggle: function (evt) {
        var li = evt.element();
        li.addClassName(this.prefix + 'waiting');
        if (li.hasClassName(this.prefix + 'used')) {
            this.remove(li.id.match(/_(\d+)$/)[1], li);
        } else {
            this.add(li.id.match(/_(\d+)$/)[1], li);
        }
    },
    /**
    * Add a column to the table 
     */
    add: function (field_id, li) {
        var form = $('tracker_report_table_addcolumn_form');
        if ($('tracker_report_table_column_' + field_id)) {
            $$('.tracker_report_table_column_' + field_id).invoke('show');
            $('tracker_report_table_column_' + field_id).show();
            
            codendi.tracker.report.table.saveColumnsWidth($('tracker_report_table'));
            
            this.setUsed(li);
        } else {
            var req = new Ajax.Request(
                '/tracker/?report=' + form.report.value + '&renderer=' + form.renderer.value,
                {
                    parameters: {
                        func:                         'renderer',
                        renderer:                     form.renderer.value,
                        'renderer_table[add-column]': field_id
                    },
                    onSuccess: function (transport) {
                        var div = new Element('div').update(transport.responseText);
                        var new_column = div.down('thead').down('th');
                        $('tracker_report_table').down('thead').down('tr').insert({bottom: new_column});
                        var new_trs = div.down('tbody').childElements().reverse();
                        $('tracker_report_table').down('tbody').childElements().each(function (tr) {
                            if (!tr.hasClassName('tracker_report_table_no_result')) {
                                tr.insert(new_trs.pop().down('td'));
                            }
                        });
                        
                        $$('tr.tracker_report_table_no_result td').each( function (td) {
                            td.colSpan = td.up('table').rows[0].cells.length;
                        });
                        
                        codendi.tracker.report.table.saveColumnsWidth($('tracker_report_table'));
                        
                        //Remove entry from the selectbox
                        this.setUsed(li);
                        
                        //eval scripts now (prototype defer scripts eval but we need them now for decorators)
                        //transport.responseText.evalScripts();
                        
                        //load aggregates
                        var selectbox = $('tracker_report_table').down('select[name="tracker_aggregate_function_add['+field_id+']"]');
                        if (selectbox) {
                            var report_id   = $F($('tracker_report_query_form')['report']);
                            var renderer_id = $$('.tracker_report_renderer')[0].id.gsub('tracker_report_renderer_', '');
                            codendi.tracker.report.loadAggregates(selectbox, report_id, renderer_id);
                        }
                        
                        codendi.tracker.report.setHasChanged();
                        
                        //reorder
                        codendi.reorder_columns[$('tracker_report_table').identify()].register(new_column);
                        
                        //resize
                        TableKit.reload();
                        
                    }.bind(this)
                }
            );
        }
    },
    /**
     * remove a column to the table
     */
    remove: function (field_id, li) {
        if ($('tracker_report_table_sort_by_' + field_id)) {
            //If the column is used to sort, we need to refresh all the page
            //Because we need to resort all the table
            // but before, save the new size of the remaining columns
            var col = $('tracker_report_table_column_' + field_id);
            if (col.nextSiblings()[0]) {
                col.nextSiblings()[0].setStyle({
                    width: (col.nextSiblings()[0].offsetWidth + col.offsetWidth) + 'px'
                });
            } else if (col.previousSiblings()[0].id) {
                col.previousSiblings()[0].setStyle({
                    width: (col.previousSiblings()[0].offsetWidth + col.offsetWidth) + 'px'
                });
            }
            col.hide();
            $$('.tracker_report_table_column_' + field_id).invoke('hide');
            
            codendi.tracker.report.table.saveColumnsWidth($('tracker_report_table'), function () {
                location.href = location.href +
                                '&func=renderer' + 
                                '&renderer=' + $('tracker_report_table_addcolumn_form').renderer.value +
                                '&renderer_table[remove-column]=' + field_id;
            });
        } else {
            var form = $('tracker_report_table_addcolumn_form');
            var req = new Ajax.Request('/tracker/?report=' + form.report.value + '&renderer=' + form.renderer.value, {
                parameters: {
                    func:                            'renderer',
                    renderer:                        $('tracker_report_table_addcolumn_form').renderer.value,
                    'renderer_table[remove-column]': field_id
                },
                onSuccess: function () {
                    var col = $('tracker_report_table_column_' + field_id);
                    if (col.nextSiblings()[0]) {
                        col.nextSiblings()[0].setStyle({
                            width: (col.nextSiblings()[0].offsetWidth + col.offsetWidth) + 'px'
                        });
                    } else if (col.previousSiblings()[0].id) {
                        col.previousSiblings()[0].setStyle({
                            width: (col.previousSiblings()[0].offsetWidth + col.offsetWidth) + 'px'
                        });
                    }
                    col.hide();
                    $$('.tracker_report_table_column_' + field_id).invoke('hide');
                    
                    codendi.tracker.report.table.saveColumnsWidth($('tracker_report_table'));
                    
                    this.setUnused(li);
                    codendi.tracker.report.setHasChanged();
                }.bind(this)
            });
        }
    },
    /**
     * Set the class name of the li to used and clear waiting
     */
    setUsed: function (li) {
        li.removeClassName(this.prefix + 'unused');
        li.removeClassName(this.prefix + 'waiting');
        li.addClassName(this.prefix + 'used');
    },
    /**
     * Set the class name of the li to unused and clear waiting
     */
    setUnused: function (li) {
        li.removeClassName(this.prefix + 'used');
        li.removeClassName(this.prefix + 'waiting');
        li.addClassName(this.prefix + 'unused');
    }
});


codendi.tracker.report.AddRemoveCriteria = Class.create({
    /**
     * Constructor
     */
    initialize: function (selectbox) {
        this.toggle_event = this.toggle.bindAsEventListener(this);
        this.prefix       = 'tracker_report_add_criteria_';
        
        var panel = new Element('table').addClassName('dropdown_panel').setStyle({
            textAlign: 'left',
            opacity: 0.9
        });
        var ul = new Element('ul');
        var btn_label = '';
        selectbox.childElements().each((function (el, index) {
            if (index) {
                if (el.tagName.toLowerCase() === 'option') {
                    ul.appendChild(this.buildCol(el.value, el.className, el.innerHTML));
                } else {
                    var subul = new Element('ul', {id: el.id});
                    el.childElements().each(function (subel) {
                        subul.appendChild(this.buildCol(subel.value, subel.className, subel.innerHTML));
                    }.bind(this));
                    ul.appendChild(new Element('li').update(new Element('strong').update(el.label)).insert(subul).setStyle({
                        display: subul.childElements().length ? 'block' : 'none'
                    }));
                }
            } else { //the first one, "-- Add column". don't need it.
                btn_label = el.text.gsub(/^--\s*/, '');
            }
        }).bind(this));
        var handle = new Element('a', {
            href: '#', 
            title: btn_label,
            style: "font-size: 0.8em;"
        }).update(btn_label + ' <img src="' + codendi.imgroot + 'ic/ui-search-field--plus.png" style="vertical-align: middle;" />');
        
        Element.remove(
            selectbox.insert({
                after: handle 
            })
        );
        ul.id = selectbox.id;
        handle.insert({
            after: panel.insert(
                new Element('tbody').insert(
                    new Element('tr').insert(
                        new Element('td').insert(ul)
                    )
                )
            )
        });
        this.panel = new codendi.DropDownPanel(panel, handle);
    },
    /**
     * Build a li in the dropdown panel to toggle a column
     */
    buildCol: function (id, className, label) {
        return new Element('li', 
            { 
                id: this.prefix + id
            }).addClassName(className)
            .update(label)
            .observe('click', this.toggle_event);
    },
    /**
     * event listener to toggle a column
     */
    toggle: function (evt) {
        var li = evt.element();
        li.addClassName(this.prefix + 'waiting');
        if (li.hasClassName(this.prefix + 'used')) {
            this.remove(li.id.match(/_(\d+)$/)[1], li);
        } else {
            this.add(li.id.match(/_(\d+)$/)[1], li);
        }
    },
    /**
     * Add a column to the table: criteria???
     */
    add: function (field_id, li) {
        var form = $('tracker_report_table_addcolumn_form'),
            req;
        if ($('tracker_report_crit_' + field_id)) {
            $$('.tracker_report_crit_' + field_id).invoke('show');
            $('tracker_report_crit_' + field_id).show();
            this.setUsed(li);
            codendi.tracker.report.setHasChanged();
            req = new Ajax.Request(location.href, {
                parameters: {
                    func: 'add-criteria',
                    field: field_id
                }
            });
        } else {
            req = new Ajax.Request(location.href, {
                parameters: {
                    func: 'add-criteria',
                    field: field_id
                },
                onSuccess: function (transport) {
                    var crit = new Element('li', {id: 'tracker_report_crit_' + field_id}).update(transport.responseText);
                    $('tracker_query').insert(crit);
                    
                    //Remove entry from the selectbox
                    this.setUsed(li);
                    
                    codendi.tracker.report.setHasChanged();
                    
                    //eval scripts now (prototype defer scripts eval but we need them now for decorators)
                    transport.responseText.evalScripts();
                    
                    //initialize events and other dynamic stuffs
                    codendi.tracker.report.loadAdvancedCriteria(crit.down('img.tracker_report_criteria_advanced_toggle'));
                    crit.select('input', 'select').map(datePickerController.create);
                }.bind(this)
            });
        }
    },
    /**
     * remove a criteria
     */
    remove: function (field_id, li) {
        //We don't need for now to make an ajax call... let's wait for the brainstorming on this topic
        //If so, please remove the 3 lines above
        var req = new Ajax.Request(location.href, {
            parameters: {
                func: 'remove-criteria',
                field: field_id
            },
            onSuccess: function () {
                $$('.tracker_report_crit_' + field_id).invoke('hide');
                $('tracker_report_crit_' + field_id).hide();
                this.setUnused(li);
                
                codendi.tracker.report.setHasChanged();
            }.bind(this)
        });
    },
    /**
     * Set the class name of the li to used and clear waiting
     */
    setUsed: function (li) {
        li.removeClassName(this.prefix + 'unused');
        li.removeClassName(this.prefix + 'waiting');
        li.addClassName(this.prefix + 'used');
    },
    /**
     * Set the class name of the li to unused and clear waiting
     */
    setUnused: function (li) {
        li.removeClassName(this.prefix + 'used');
        li.removeClassName(this.prefix + 'waiting');
        li.addClassName(this.prefix + 'unused');
    }
});

// Advanced criteria
codendi.tracker.report.loadAdvancedCriteria = function (element) {
    if (element) {
        var li = element.up('li');
        element.observe('click', function (evt) {
            if (/toggle_plus.png$/.test(element.src)) {
                //switch to advanced
                element.src = element.src.gsub('toggle_plus.png', 'toggle_minus.png');
            } else {
                //toggle off advanced
                element.src = element.src.gsub('toggle_minus.png', 'toggle_plus.png');
            }
            var field_id = element.up('td').next().down('label').htmlFor.match(/_(\d+)$/)[1];
            var req = new Ajax.Updater(li, location.href, {
                parameters: {
                    func: 'toggle-advanced',
                    field: field_id
                }, 
                onComplete: function (transport) {
                    //Force refresh of decorators and calendar
                    li.select('input', 'select').each(function (el) {
                        if (el.id && $('fd-' + el.id)) {
                            delete $('fd-' + el.id).remove();
                            delete datePickerController.datePickers[el.id];
                        }
                    });
                    
                    codendi.tracker.report.setHasChanged();
                    
                    //eval scripts now (prototype defer scripts eval but we need them now for decorators)
                    transport.responseText.evalScripts();
                    
                    //initialize events and other dynamic stuffs
                    codendi.tracker.report.loadAdvancedCriteria(li.down('img.tracker_report_criteria_advanced_toggle'));
                    li.select('input', 'select').map(datePickerController.create);
                }
            });
            Event.stop(evt);
            return false;
        });
    }
};

//Aggregates
codendi.tracker.report.loadAggregates = function (selectbox, report_id, renderer_id) {
    var prefix = 'tracker_aggregate_function_add_',
        parameters = {
            func: 'renderer',
            report: report_id,
            renderer: renderer_id
        },
        field_id = selectbox.name.split('[')[1].gsub(/\]/, ''),
        toggle = function (evt) {
            var li = evt.element();
            li.addClassName(prefix + 'waiting');
            parameters['renderer_table[add_aggregate]['+ field_id +']'] = li.id.match(/\d_(\w+)$/)[1];
            new Ajax.Request(location.href, {
                method: 'POST',
                parameters: parameters, 
                onComplete: function() {
                    window.location.reload(true);
                }
            });
        }.bindAsEventListener();
    
    function buildCol(id, className, label) {
        return new Element('li', 
            { 
                id: prefix + id
            }).addClassName(className)
            .update(label)
            .observe('click', toggle);
    }
    
    var panel = new Element('table').addClassName('dropdown_panel').setStyle({
        textAlign: 'left',
        opacity: 0.9
    });
    var ul = new Element('ul');
    var btn_label = '';
    selectbox.childElements().each((function (el, index) {
        if (index) {
            if (el.tagName.toLowerCase() === 'option') {
                ul.appendChild(buildCol(field_id + '_' + el.value, el.className, el.innerHTML));
            }
        } else { //the first one, "-- Add column". don't need it.
            btn_label = el.text.gsub(/^--\s*/, '');
        }
    }).bind(this));
    var handle = new Element('a', {
        href: '#', 
        title: btn_label,
        style: "font-size: 0.8em;"
    }).update('<img src="' + codendi.imgroot + 'ic/sum--plus.png" style="vertical-align: middle;" />');
    
    Element.remove(
        selectbox.insert({
            after: handle 
        })
    );
    ul.id = selectbox.id;
    handle.insert({
        after: panel.insert(
            new Element('tbody').insert(
                new Element('tr').insert(
                    new Element('td').insert(
                        new Element('strong').update(btn_label)
                    ).insert(ul)
                )
            )
        )
    });
    new codendi.DropDownPanel(panel, handle);
};

document.observe('dom:loaded', function () {
    
    function inject_data_in_form(form) {
        //table
        var columns = $$('.tracker_report_table_column');
        if (columns.length) {
            var total_width = columns[0].up('table').offsetWidth;
            var column_info = columns.collect(
                function (el) {
                    //extract the id of the field from the html id of the column
                    // tracker_report_table_column_5467
                    return el.id.gsub('tracker_report_table_column_', '') + '|' + Math.round(el.offsetWidth * 100 / total_width); 
                }
            ).join(',');
            form.appendChild(new Element('input', {
                type: 'hidden',
                name: 'renderer[' + renderer_id + '][columns]',
                value: column_info
            }));
        }

        //chunksz
        if ($('renderer_table_chunksz_input')) {
            form.appendChild(new Element('input', {
                type: 'hidden',
                name: 'renderer[' + renderer_id + '][chunksz]',
                value: $('renderer_table_chunksz_input').value
            }));
        }
        
        //Renderers order
        form.appendChild(
            new Element('input', {
                type: 'hidden',
                name: 'renderers',
                value: $$('#tracker_report_renderers li')
                        .collect(
                            function (li) { 
                                return li.id.replace(/^.*_(\d+)$/, '$1'); 
                            })
                        .without('')
                        .join(',')
            })
        );
    }
    
    if ($('tracker_query')) {
        $$('img.tracker_report_criteria_advanced_toggle').map(codendi.tracker.report.loadAdvancedCriteria);
        
        //User add criteria
        if ($('tracker_report_add_criteria')) {
            var arcr = new codendi.tracker.report.AddRemoveCriteria($('tracker_report_add_criteria'));
        }
        
        
        //User add/remove column
        if ($('tracker_report_table_add_column')) {
            var arco = new codendi.tracker.report.table.AddRemoveColumn($('tracker_report_table_add_column'));
        }
        
        // Masschange
        var button      = $$('input[name="renderer_table[masschange_all]"]')[0];
        var mc_panel    = $('tracker_report_table_masschange_panel');
        var mc_all_form = $('tracker_report_table_masschange_form');
        if (button) {
            mc_panel.up('.tracker_report_renderer').addClassName('tracker_report_table_hide_masschange');
            mc_panel.up('.tracker_report_renderer').removeClassName('tracker_report_table_show_masschange');
            $$('.tracker_report_table_masschange').invoke('hide');
            mc_panel.insert({
                top: new Element('br')
            }).insert({
                top: new Element('a', {
                    href: '#uncheck-all'
                }).observe('click', function (evt) {
                    $$('.tracker_report_table_masschange input[type=checkbox]').each(function (cb) {
                        cb.checked = false;
                    });
                    Event.stop(evt);
                }).update(codendi.locales.tracker_artifact.masschange_uncheck_all)
            }).insert({
                top: new Element('span')
                .update('&nbsp;|&nbsp;')
            }).insert({
                top: new Element('a', {
                    href: '#check-all'
                }).observe('click', function (evt) {
                    $$('.tracker_report_table_masschange input[type=checkbox]').each(function (cb) {
                        cb.checked = true;
                    });
                    Event.stop(evt);
                }).update(codendi.locales.tracker_artifact.masschange_check_all)
            });
            //get checked artifacts
            $('masschange_btn_checked').observe('click', function (evt) {
                var mc_aids = new Array();
                $$('input[name="masschange_aids[]"]').each( function (e) {
                    if ( $(e).checked ) {
                        mc_all_form.appendChild( new Element('input', {type:'hidden', name:'masschange_aids[]',value:$(e).value}) );
                    }
                });
                //$('masschange_form').;
                //mc_all_form.submit();
            });
            
            if (location.href.match(/#masschange$/)) {
                mc_panel.up('.tracker_report_renderer').toggleClassName('tracker_report_table_hide_masschange');
                mc_panel.up('.tracker_report_renderer').toggleClassName('tracker_report_table_show_masschange');
                $$('.tracker_report_table_masschange').invoke('show');
            } else {
                $('tracker_renderer_options_menu').down('ul').insert(
                    {
                        bottom: new Element('li').insert(
                            new Element('a', {
                                href: '#masschange'
                            }).observe('click', function (evt) {
                                codendi.dropdown_panels.invoke('reset');
                                $$('.tracker_report_table_masschange').invoke('show');
                                mc_panel.up('.tracker_report_renderer').toggleClassName('tracker_report_table_hide_masschange');
                                mc_panel.up('.tracker_report_renderer').toggleClassName('tracker_report_table_show_masschange');
                                if (mc_panel.up('.tracker_report_renderer').hasClassName('tracker_report_table_show_masschange')) {
                                    Element.scrollTo(mc_panel);
                                }
                                Event.stop(evt);
                            }).update('<img src="'+ codendi.imgroot +'ic/clipboard-lightning.png" style="vertical-align:top" /> ' + codendi.locales.tracker_artifact.masschange)
                        )
                    }
                );
            }
        }
        
        //Export
        if ($('tracker_report_table_export_panel') && !location.href.match(/#export$/)) {
            var export_panel = $('tracker_report_table_export_panel');
            export_panel.childElements().invoke('hide');
            export_panel.up('form').insert(
                {
                    before: new Element('a', {
                            href: '#export'
                        }).observe('click', function (evt) {
                            export_panel.childElements().invoke('toggle');
                            Event.stop(evt);
                        }).update('<img src="'+ codendi.imgroot +'ic/clipboard-paste.png" style="vertical-align:top" /> export')
                        .setStyle({
                            marginLeft: '1em'
                        })
                }
            );
        }
        
        if (TableKit) {
            TableKit.options.observers.onResizeEnd = function (table) {
                if (TableKit.Resizable._cell && TableKit.Resizable._cell.id.match(/_\d+$/)) {
                    codendi.tracker.report.table.saveColumnsWidth(table);
                }
            };
        }
    }
    
    if ($('tracker_select_report')) {
        $('tracker_select_report').observe('change', function () {
            this.form.submit();
        });
    }
    if ($('tracker_report_query_form')) {
        
        if ($('renderer_table_chunksz_input')) {
            var form = $('tracker_report_query_form');
            var element = $('renderer_table_chunksz_input');
            var chunksz_old_value = element.value;
           
            var btn = $('renderer_table_chunksz_btn');
            if (btn) {
                btn.hide();
                element.observe('focus', function () {
                    btn.show();
                });
                element.observe('blur', function () {
                    if (element.value === chunksz_old_value) {
                        btn.hide();
                    }
                });
            }
            
        }
        
        var report_id   = $F($('tracker_report_query_form')['report']);
        var renderer_id = $$('.tracker_report_renderer')[0].id.gsub('tracker_report_renderer_', '');
        
        /*var form = $('tracker_report_query_form');
        if (form) {
            form.observe('submit', function (evt) {
                inject_data_in_form(form);
            });
        }*/
        
        if ($('tracker_report_updater_save')) {
            //TODO: 'save as'
            $('tracker_report_updater_save').form.observe('submit', function (evt) {
                var save_btn = $('tracker_report_updater_save');
                if (save_btn.checked) {
                    //criteria
                    $('tracker_report_query_form').select('[name^=criteria]').each(function (criteria) {
                        save_btn.form.appendChild(criteria.cloneNode(true).hide());
                    });
                    inject_data_in_form(save_btn.form);
                }
            });
        }
        
        //Pager
        /*if ($('tracker_report_table_pager')) {
            $$('.tracker_report_table_pager a').each(function (a) {
                a.observe('click', function (evt) {
                    var href= a.href;
                    inject_data_in_form(form);                
                    href += '&' + Form.serializeElements($('tracker_report_query_form').select('[name^=criteria]', '[name^=renderer]'));
                    location.href = href;
                    evt.stop();
                });
            });
        }*/
        
        if ($('tracker_renderer_updater_move')) {
            var renderers_sorting = false;
            Sortable.create('tracker_report_renderers', {
                constraint: 'horizontal',
                only: 'tracker_report_renderer_tab',
                onUpdate: function (container) {
                    renderers_sorting = true;
                    var parameters = Sortable.serialize(container) + '&func=move-renderer&report=' + report_id + '&renderer=' + renderer_id;
                    
                    var req = new Ajax.Request(location.href, {
                        parameters: parameters,
                        onComplete: function (transport) {
                            codendi.tracker.report.setHasChanged();
                        }
                    });
                }
            });
            $('tracker_renderer_updater_move').up('ul').appendChild(
                new Element('li').update('<em>' + codendi.locales.tracker_renderer.order_dragdrop + '</em>')
            );
            $('tracker_renderer_updater_move').up('li').remove();
            $$('#tracker_report_renderers li a').each(function (a) {
                a.observe('click', function (evt) {
                    if (renderers_sorting) {
                        evt.stop();
                        renderers_sorting = false;
                    }
                });
            });
                   
            /*var tabs = $$('.tracker_report_renderer_tab');
            if (tabs.length) {
                var tabs_info = tabs.collect(
                    function (el) {
                        return el.id.gsub('tracker_report_renderer_', '');
                    }
                ).join(',');
            }*/
      
       
            /*
            $('tracker_renderer_updater_move').form.observe('submit', function (evt) {
                    var form = $('tracker_report_query_form');
                    var move_radio =  $('tracker_renderer_updater_move');
                    if (move_radio.checked) {
                        evt.stop();
                        var renderer_id = $$('.tracker_report_renderer')[0].id.gsub('tracker_report_renderer_', '');
                        var current = $$('li.tracker_report_renderers-current')[0];
                        var current_rank = current.down('[name=tracker_report_renderer_rank]').value;
                        var current_select_value = $('tracker_renderer_updater_move').form.down('[name=move-renderer-direction]').value;
                        var ul = current.up('ul');
                        
                        //move right
                        var sibling = current.next();
                        var pos  = 'after';
                        if (current_rank > current_select_value) {
                            //move left
                            sibling = current.previous();
                            pos  = 'before';
                        }
                        current.remove();
                        var params = {};
                        params[pos] = current;
                        sibling.insert(params);
                    }
            });
            */
        }
        
        $$('select[name^=tracker_aggregate_function_add]').each(function (selectbox) {
                codendi.tracker.report.loadAggregates(selectbox, report_id, renderer_id);
        });
    }
    
    
    /**
     * Trackers switching goes fast!
     */
    /* usability issues. we will comment this until it is fixed.
    var steps = $$('.breadcrumb-step-trackers');
    if (steps.length) {
        var all_trackers = null,
            timeout      = null;
        steps[0].observe('mouseover', function (evt) {
            if (!all_trackers) {
                var left_top = steps[0].cumulativeOffset();
                var top  = left_top[1] + steps[0].offsetHeight;
                var left = left_top[0] + Math.round(steps[0].offsetWidth / 2);
                var td = new Element('td').setStyle(
                    {
                        whiteSpace: 'nowrap',
                        padding: 0
                    }
                );
                var div = new Element('div').setStyle(
                    {
                        overflow: 'auto',
                        height: '300px'
                    }
                );
                all_trackers = new Element('table')
                    .update(new Element('tr')
                        .update(td.update(div)));
                document.body.appendChild(all_trackers);
                all_trackers.absolutize().setStyle(
                    {
                        top: top + 'px',
                        left: left + 'px',
                        background: 'white',
                        '-webkit-box-shadow': '0px 2px 10px #ccc',
                        '-moz-box-shadow': '0px 2px 10px #ccc',
                        boxShadow: '0px 2px 10px #ccc',
                        border: '1px solid #ccc',
                        zIndex: 1000
                    }
                ).observe('mouseover', function (evt) {
                        if (timeout) {
                            clearTimeout(timeout);
                        }
                    }).observe('mouseout', function (evt) {
                        timeout = setTimeout(function () {
                            all_trackers.hide();
                        }, 500);
                    }).hide();
                var req = new Ajax.Updater(
                    div,
                    steps[0].href,
                    {
                        onComplete: function () {
                            all_trackers.show();
                            if (all_trackers.offsetHeight > 300) {
                                div.setStyle(
                                    {
                                        top: top + 'px',
                                        left: left + 'px',
                                        height: '300px',
                                        width: (div.offsetWidth + 20) + 'px'
                                    }
                                );
                            }
                        }
                    }
                );
            } else if (!all_trackers.visible()) {
                if (timeout) {
                    clearTimeout(timeout);
                }
                all_trackers.show();
            }
        }).observe('mouseout', function (evt) {
            if (all_trackers.visible()) {
                timeout = setTimeout(function () {
                    all_trackers.hide();
                }, 500);
            }
        });
    }
    */

});

