/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
*
* Originally written by Nicolas Terray, 2008
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

codendi.layout_manager = { 
    addCol: function(col) {
        var new_col = '<td class="layout-manager-column"><div class="layout-manager-column-remove">x</div><div class="layout-manager-column-width"><input type="text" value="" autocomplete="off" size="1" maxlength="3" />%</div></td>';
        col.up('tr').insert({bottom: new_col});
        new_col = new Element('td');
        new_col.addClassName("layout-manager-column-add");
        new_col.update('+');
        this.loadColAdd(new_col);
        col.up('tr').insert({bottom: new_col});
        
        this.distributeWidth(col.up('tr'));
        var rm = col.up('tr').down('.layout-manager-column-remove', col.up('tr').select('.layout-manager-column').size() - 1);
        this.loadColRemove(rm);
    },
    addRow: function(row) {
        var new_row = '<table class="layout-manager-row" cellspacing="5" cellpadding="2" border="0"><tr><td>+</td></tr></table>';
        row.insert({after: new_row});

        var new_col = row.next().down('td');
        new_col.addClassName("layout-manager-column-add");
        new_col.update('+');
        this.loadColAdd(new_col);
        
        this.addCol(new_col);
        
        new_row = new Element('div');
        new_row.addClassName("layout-manager-row-add");
        new_row.update('+');
        this.loadRowAdd(new_row);
        
        row.next().insert({after: new_row});
    },
    removeCol: function (rm) {
        var row = rm.up('tr');
        
        //Check columns number in layout
        if ($('layout-manager').select('.layout-manager-column').size() == 1) {
            alert('You must keep at least one column in your layout.');
        } else if (row.select('.layout-manager-column').size() == 1) {
            //remove row
            Element.remove(row.up('table').next());
            Element.remove(row.up('table'));
        } else {
            //remove column
            Element.remove(rm.up('td').next());
            Element.remove(rm.up('td'));
            this.distributeWidth(row);
        }
    },
    distributeWidth: function (row) {
        var cols = row.select('input[type=text]');
        var width = Math.round(100 / cols.size());
        cols.each(function (input) {
            input.value = width;
        });
    },
    load: function() {
        $$(".layout-manager-column-add").each(this.loadColAdd.bind(this));
        $$(".layout-manager-column-remove").each(this.loadColRemove.bind(this));
        $$(".layout-manager-row-add").each(this.loadRowAdd.bind(this));
    },
    loadColAdd: function(col) {
        col.observe('mouseover', function() {
            this.addClassName('layout-manager-column-add_hover');
        });
        col.observe('mouseout', function() {
            this.removeClassName('layout-manager-column-add_hover');
        });
        col.observe('click', function () { this.addCol(col) }.bind(this));
    },
    loadColRemove: function(rm) {
        rm.observe('mouseover', function() {
            this.addClassName('layout-manager-column-remove_hover');
        }).observe('mouseout', function() {
            this.removeClassName('layout-manager-column-remove_hover');
        }).observe('click', function () { this.removeCol(rm); }.bind(this));
    },
    loadRowAdd: function(row) {
        row.observe('mouseover', function() {
            this.addClassName('layout-manager-row-add_hover');
        });
        row.observe('mouseout', function() {
            this.removeClassName('layout-manager-row-add_hover');
        });
        row.observe('click', function () { this.addRow(row); }.bind(this));
    }
}

document.observe('dom:loaded', function() {
    if ($('layout-manager')) {
        codendi.layout_manager.load();
        $('save').observe('click', function(evt) {
            if ($('layout-manager')) {
                var reg = /^\d+$/
                var invalid = $('layout-manager').select('.layout-manager-column input[type=text]').find(function (element) {
                    return (!reg.test(element.value)) ? element : false;
                });
                if (invalid) {
                    alert(invalid.value+' is not a valid number');
                } else {
                    var form = $('layout-manager').up('form');
                    if (form) {
                        $('layout-manager').select('.layout-manager-row').each(function (row) {
                            form.insert(new Element('input', {
                                    type: 'hidden',
                                    name: 'new_layout[]',
                                    value: row.select('.layout-manager-column input[type=text]').pluck('value').join(',')
                            }));
                        });
                    }
                }
            }
        });
        $$('.layout-manager-chooser').each(function (row) {
                row.down('input[type=radio]').observe('change', function() {
                        $$('.layout-manager-chooser').each(function (row) {
                                row.removeClassName('layout-manager-chooser_selected');
                        });
                        row.addClassName('layout-manager-chooser_selected');
                });
        });
    }

    //Widget categorizer
    var default_categ = location.href.match(/#filter-(widget-categ-[a-z0-9-_]+)$/);
    var current_categ;
    if (default_categ && default_categ[1]) {
        current_categ = default_categ[1];
    } else {
        current_categ = 'widget-categ-general';
    }
    $$('.widget-categ-switcher').each(function (a) {
        var scan_id = a.href.match(/#(widget-categ-[a-z0-9-_]+)$/);
        if (scan_id && scan_id[1]) {
            var id = scan_id[1];
            a.href = a.href.gsub(/#(widget-categ-[a-z0-9-_]+)$/, '#filter-'+id);
            a.observe('click', function(evt) {
                current_categ = id;
                //Display widgets of this category
                $('widget-content-categ').childElements().invoke('hide');
                a.up('ul').select('.widget-categ-switcher').each(function(other_a) {
                    other_a.up().removeClassName('selected');
                });
                a.up().addClassName('selected');
                $(id).show();
            });
            //remove corresponding table
            if (id != current_categ) {
                $(id).hide();
            } else {
                a.up().addClassName('selected');
            }
            Element.remove($(id).down('h4'));
            $('widget-content-categ').appendChild(Element.remove($(id)));
        }
    });
});
