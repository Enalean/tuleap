/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

document.observe('dom:loaded', function () {
    
    function tracker_register_hide_value() {
        $$('.tracker_admin_static_value_hidden_chk').each(function (checkbox) {
            var img = checkbox.next();
            checkbox.hide();
            img.setStyle({cursor:'pointer'}).observe('click', function (evt) {
                if (checkbox.checked) {
                    //switch to "hidden"
                    checkbox.checked = false;
                    checkbox.up('tr').addClassName('tracker_admin_static_value_hidden');
                    img.src = img.src.gsub('eye.png', 'eye-half.png');
                } else {
                    //switch to "shown"
                    checkbox.checked = true;
                    checkbox.up('tr').removeClassName('tracker_admin_static_value_hidden');
                    img.src = img.src.gsub('eye-half.png', 'eye.png');
                }
            });
        });
    }
    tracker_register_hide_value();
    
    var palette = $$('.tracker-admin-palette')[0];
    if (palette) {
        var admin_field_properties = $('tracker-admin-field-properties');
        if (!admin_field_properties) {
            admin_field_properties = new Element(
                'div',
                {
                    id: 'tracker-admin-field-properties',
                    className: 'widget'
                }
            ).hide().update('<div class="widget_titlebar"><div class="widget_titlebar_title"></div></div><div class="widget_content"></div>');
            palette.insert({
                after: admin_field_properties
            });
        }
        
        $$('a.button[name^=create]').each(function (button) {
            button.observe('click', function (evt) {
                // Replace button icon with spinner
                var spinnerUrl = codendi.imgroot + '/ic/spinner-16.gif';
                var buttonImg  = button.down('img');
                var buttonIcon = buttonImg.src;
                buttonImg.src  = spinnerUrl;
                			
                $$('.tracker-admin-field-selected').each(function (selected_element) {
                    if (selected_element.visible()) {
                        var element = selected_element.up('.tracker-admin-field');
                        if (element) {
                            element.childElements().invoke('show');
                        }
                        selected_element.hide();
                    }
                });
                var parameters = {};
                parameters[button.name] = 1;
                var req = new Ajax.Request(
                    button.up('form').action,
                    {
                        parameters: parameters,
                        onComplete: function (transport) {
                            
                            var rtes = [];
                            
                            //Don't use directly updater since the form is stripped
                            admin_field_properties.down('.widget_content').update('').insert(new Element('div').update(transport.responseText).down());
                            admin_field_properties.down('.widget_titlebar_title').update('Create an element');
                            admin_field_properties.select('input[type=submit]')[0].insert({
                                before: new Element(
                                    'a',
                                    {
                                        href: '#cancel'
                                    }).observe('click', function (evt) {
                                        rtes.each(function (rte) {
                                            rte.destroy();
                                        });
                                        rtes = [];
                                        admin_field_properties.hide();
                                        palette.show();
                                        evt.stop();
                                    }).update('&laquo; ' + codendi.locales.tracker_formelement_admin.cancel)
                                }
                            );
                            admin_field_properties.select('input[type=submit]')[0].insert({
                                    before: new Element('span').update(' ')
                                }
                            );
                            palette.hide();
                            admin_field_properties.show();
                            
                            //Put here the javascript stuff you need to call once the content of the modal dialog is loaded
                            
                            //Richtext editor
                            admin_field_properties.select('.tracker-field-richtext').each(function (element) {
                                rtes.push(new codendi.RTE(element, {
                                    onLoad: function () {
                                        admin_field_properties.setStyle({
                                            width: 'auto',
                                            height: 'auto'
                                        });
                                        admin_field_properties.setStyle({
                                            width: 'auto',
                                            height: 'auto'
                                        });
                                    }
                                }));
                            });
                            
                            //Edit list values
                            var e = new codendi.tracker.bind.Editor(admin_field_properties);
                            
                            // Restore button icon
                            buttonImg.src = buttonIcon;
                        }
                    }
                );
                evt.stop();
            });
        });
        
        $$('a.button_disabled[name^=create]').each(function(button_disabled) {
            button_disabled.observe('click', function (evt) {
                alert(codendi.locales.tracker_formelement_admin.unique_field);
            });
        });
        
        $$('.tracker-admin-field-controls a.edit-field').each(function (a) {
            var selected_element, 
                element;
            var rtes = [];
            a.observe('click', function (evt) {
                if (!selected_element) {
                    selected_element = new Element('div')
                        .hide()
                        .addClassName('tracker-admin-field-selected')
                        .addClassName('widget')
                        .update('<div class="widget_titlebar"><div class="widget_titlebar_title"></div></div><div class="widget_content"></div>');
                    element = a.up('.tracker-admin-field');
                    if (element) {
                        element.insert(selected_element);
                    } else {
                        element = a.up('.tracker-admin-container');
                        element.down().insert({
                            after: selected_element
                        });
                    }
                }
                $$('.tracker-admin-field-selected').each(function (selected_element) {
                    if (selected_element.visible()) {
                        var element = selected_element.up('.tracker-admin-field');
                        if (element) {
                            element.childElements().invoke('show');
                        }
                        selected_element.hide();
                    }
                });
                if (admin_field_properties && admin_field_properties.visible()) {
                    admin_field_properties.hide();
                    palette.show();
                }
                var r = new Ajax.Request(
                    a.href,
                    {
                        onComplete: function (transport) {
                            //Don't use directly updater since the form is stripped
                            selected_element.down('.widget_content').update('').insert(new Element('div').update(transport.responseText).down());
                            selected_element.down('.widget_titlebar_title').update('Update an element');
                            var submit_button = selected_element.select('input[type=submit]')[0];
                            submit_button.insert({
                                before: new Element(
                                    'a',
                                    {
                                        href: '#cancel'
                                    }).observe('click', function (evt) {
                                        rtes.each(function (rte) {
                                            rte.destroy();
                                        });
                                        rtes = [];
                                        element.childElements().invoke('show');
                                        selected_element.hide();
                                        if (element.viewportOffset()[1] < 0) {
                                            element.scrollTo();
                                        }
                                        var e = new Effect.Highlight(element, { 
                                            queue: 'end' 
                                        });
                                        evt.stop();
                                    }).update('&laquo; ' + codendi.locales.tracker_formelement_admin.cancel)
                                }
                            );
                            selected_element.select('input[type=submit]')[0].insert({
                                    before: new Element('span').update(' ')
                                }
                            );
                            if (!element.hasClassName('tracker-admin-container')) {
                                element.childElements().invoke('hide');
                            }
                            selected_element.show();
                            
                            //Put here the javascript stuff you need to call once the content of the modal dialog is loaded
                            
                            //Color picker
                            selected_element.select('.colorpicker').each(function (element) {
                                    codendi.colorpicker.colorpickers[element.identify()] = new codendi.colorpicker.Colorpicker(element);
                                }
                            );
                            
                            //Richtext editor
                            selected_element.select('.tracker-field-richtext').each(function (element) {
                                rtes.push(new codendi.RTE(element, {
                                    onLoad: function () {
                                        admin_field_properties.setStyle({
                                            width: 'auto',
                                            height: 'auto'
                                        });
                                        admin_field_properties.setStyle({
                                            width: 'auto',
                                            height: 'auto'
                                        });
                                    }
                                }));
                            });
                            
                            //Edit list values
                            var e = new codendi.tracker.bind.Editor(selected_element);
                            
                            //register hide action
                            tracker_register_hide_value();
                        }
                    }
                );
                evt.stop();
            });
        });
    }
    
    
    // {{{ Drag 'n Drop 
    /*
    $$('.tracker-admin-field', '.tracker-admin-container').each(function (field) {
        var d = new Draggable(field, {
                handle: field.down('label'),
                revert: true,
                ghosting: true,
                scroll: window
            }
        );
    });
    
    $$('.tracker-admin-container').each(function (container) {
        Droppables.add(container, {
            accept: ['tracker-admin-container', 'tracker-admin-field'],
            hoverclass: 'tracker-admin-container_drop',
            onDrop: function (drag, drop, evt) {
                if (drop !== drag.up('.tracker-admin-container')) {
                    drop.insert({bottom: drag.remove()}).setStyle({ 
                        left: 'auto', 
                        top: 'auto'
                    });
                }
            }
        });
    });
    // }}} */
    
    var button = $('button_preview_xml');
    if (button) {
        var iframe = new Element('iframe', {
                src: 'about:blank',
                name:  'preview_xml_dialog',
                style: 'display: none; width:100%; height: 300px;'
            }
        );
        var td = new Element('td');
        td.colSpan = 4;
        button.up('tbody').appendChild(new Element('tr')).appendChild(td);
        td.appendChild(iframe);
        button.observe('click', function (evt) {
            button.form.target = 'preview_xml_dialog';
            iframe.show();
        });
    }
    
    $$(".tracker-field-richtext").each(function define_rich_text(elem) {
        var r = new codendi.RTE(elem);
    });
    
    $$("input[type=checkbox][name^=remove_postaction]", "input[type=checkbox][name^=remove_rule]").each(function (elem) {
        elem.observe('click', function (evt) {
            if (elem.checked) {
                elem.up('tr').down('div').addClassName('deleted');
                elem.up('tr').select('select').each(function (e) { e.disabled = true; e.readOnly = true; });
                elem.up('tr').select('input').each(function (e) { e.required = false; });
            } else {
                elem.up('tr').down('div').removeClassName('deleted');
                elem.up('tr').select('select').each(function (e) { e.disabled = false; e.readOnly = false; });
                elem.up('tr').select('input').each(function (e) { if (e.hasClassName('required')) e.required = true; });
            }
        });
    });

    $$('.add_new_rule_title').each(function (add) {
        var link = new Element('a', { href: '#add_new_rule' })
            .update(add.innerHTML)
            .observe('click', function (evt) {
                add.next().toggle();
                Event.stop(evt);
                return false;
            });
        add.update(link);
        add.next().hide();
    });

    /*
     * Trigger event handling
     */


     (function trigger_init() {
        if($('add_new_trigger_title')) {
            var trigger = new tuleap.trackers.trigger();

            (function display() {
                $('trigger_create_new').hide();

                (function bindAddNewTrigger() {
                    Event.observe($('add_new_trigger_title'), 'click', function() {
                        $('add_new_trigger_title').hide();
                        $('trigger_create_new').show();
                    });
                })()
            })();

            (function bindAddExtraCondition() {
                Event.observe($('trigger_add_condition'), 'click', function() {
                    var condition = trigger.addCondition();

                    condition.activateDeleteButton(trigger);
                    condition.makeQuantityDynamic();
               });
            })();

            (function bindCancelAddNewTrigger(){
                Event.observe($('trigger_add_cancel'), 'click', function() {
                    $('trigger_create_new').hide();
                    $('add_new_trigger_title').show();
                    reset();
                });
            })();

            (function bindSubmitNewTrigger(){
                var callbacks = {
                    'success' : function() {
                        $('trigger_create_new').hide();
                        $('add_new_trigger_title').show();
                        reset();
                    },

                    'fail' : function() {
                        alert('error');
                    }
                }

                Event.observe($('trigger_submit_new'), 'click', function() {
                    trigger.save(callbacks);
                });
            })();

            function reset() {
                trigger.getConditions().each(function(condition) {
                    if (condition.getContainer().readAttribute('data-trigger-condition-initial') !== 'true') {
                        trigger.removeCondition(condition);
                    } else {
                        condition.removeAllOptions();
                    }

                    (function resetForm() {
                        $$('#trigger_create_new select').each(function(select_box) {
                            select_box.value = '';
                        });
                    })();
                });
            }
         }
     })();
});


