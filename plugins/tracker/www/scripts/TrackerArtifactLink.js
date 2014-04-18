/*
 *Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
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
codendi.tracker = codendi.tracker || { };
codendi.tracker.artifact = codendi.tracker.artifact || { };

codendi.tracker.artifact.artifactLink = {

    overlay_window: null,
        
    strike: function(td, checkbox) {
        td.up().childElements().invoke('setStyle', {
            textDecoration: (checkbox.checked ? 'line-through' : 'none')
        });
    },
    set_checkbox_style_as_cross: function(table_cells) {
        
        table_cells.each(function (td) {
            var unlinked = codendi.imgroot + 'ic/cross.png';
            var linked   = codendi.imgroot + 'ic/cross-disabled.png';
            if ( td.down('span') ) {
                td.down('span').hide();
            }
            if ( !td.down('img') ) {
                var checkbox = td.down('input[type="checkbox"]');
                if (checkbox) {
                    var img = new Element('img', {
                        src: checkbox.checked ? unlinked : linked
                    }).setStyle({
                        cursor: 'pointer',
                        verticalAlign: 'middle'
                    }).observe('click', function (evt) {
                        checkbox.checked = !checkbox.checked;
                        codendi.tracker.artifact.artifactLink.strike(td, checkbox);
                        codendi.tracker.artifact.artifactLink.load_nb_artifacts(checkbox.up('.tracker-form-element-artifactlink-trackerpanel'));
                        codendi.tracker.artifact.artifactLink.reload_aggregates_functions(checkbox.up('.tracker_artifact_field '));
                    }).observe('mouseover', function (evt) {
                        img.src = checkbox.checked ? linked : unlinked;
                    }).observe('mouseout', function (evt) {
                        img.src = checkbox.checked ? unlinked : linked;
                    });
                    td.appendChild(img);
                    codendi.tracker.artifact.artifactLink.strike(td, checkbox);
                }
            }
        });
    },
    newArtifact: function (aid) {
        if (codendi.tracker.artifact.artifactLinker_currentField) {
            //add to the existing ones
            var input_field = codendi.tracker.artifact.artifactLinker_currentField.down('input[type=text][name^=artifact]');
            if (input_field.value) {
                input_field.value += ',';
            }
            input_field.value += aid;
            codendi.tracker.artifact.artifactLink.addTemporaryArtifactLinks();
            overlay_window.deactivate();
        }
    },
    showReverseArtifactLinks: function() {
        var show_reverse_artifact_button = $('display-tracker-form-element-artifactlink-reverse');

        if (show_reverse_artifact_button) {
            show_reverse_artifact_button.observe('click', function(event) {
                Event.stop(event);

                this.adjacent('#tracker-form-element-artifactlink-reverse').invoke('show');
                this.hide();
            });
        }
    },
    addTemporaryArtifactLinks: function () {
        if (codendi.tracker.artifact.artifactLinker_currentField) {
        var ids = codendi.tracker.artifact.artifactLinker_currentField.down('input.tracker-form-element-artifactlink-new').value;
        if ($('lightwindow_contents') && $('lightwindow_contents').down('input[name="link-artifact[manual]"]')) {
            if (ids) {
                ids += ',';
            }
            ids += $('lightwindow_contents').down('input[name="link-artifact[manual]"]').value;
        }
        ids = ids.split(',')
            .invoke('strip')
            .reject(function (id){
                    //prevent doublons
                    return $$('input[name="artifact['+ codendi.tracker.artifact.artifactLinker_currentField_id +'][removed_values]['+ id +'][]"]').size() != 0;
                }
            )
            .join(',');
        if (ids) {
            var req = new Ajax.Request(
            codendi.tracker.base_url + '?',
            {
                parameters: {
                    formElement: codendi.tracker.artifact.artifactLinker_currentField_id,
                    func: 'fetch-artifacts',
                    ids: ids
                },
                onSuccess: function (transport) {
                    if (transport.responseJSON) {
                        var json = transport.responseJSON;
                        if (json.rows) {
                            $H(json.rows).each(function (pair) {
                                var renderer_table = $('tracker_report_table_' + pair.key);
                                if (!renderer_table) {
                                    // remove the empty element
                                    var empty_value = codendi.tracker.artifact.artifactLinker_currentField.down('.empty_value');
                                    if (empty_value) {
                                        empty_value.remove();
                                    }
                                    var list = codendi.tracker.artifact.artifactLinker_currentField.down('.tracker-form-element-artifactlink-list');
                                    list.insert(json.head[pair.key] + '<tbody>');
                                    var first_list = $$('.tracker-form-element-artifactlink-list ul').first();
                                    codendi.tracker.artifact.artifactLink.tabs[codendi.tracker.artifact.artifactLinker_currentField.identify()].loadTab(list.childElements().last().down('h2'), first_list);
                                    renderer_table = $('tracker_report_table_' + pair.key);
                                    renderer_table.up('div').hide();

                                    var current_tab = first_list.down('li.tracker-form-element-artifactlink-list-nav-current');
                                    var pos = current_tab.previousSiblings().length;
                                    first_list.siblings()[pos].show();
                                }
                                
                                //make sure new rows are inserted before the aggregate function row
                                renderer_table.select('tr.tracker_report_table_aggregates').invoke('remove');
                                renderer_table.down('tbody').insert(pair.value);
                                
                                codendi.tracker.artifact.artifactLink.set_checkbox_style_as_cross(renderer_table.select('td.tracker_report_table_unlink'));
                                codendi.tracker.artifact.artifactLink.load_nb_artifacts(renderer_table.up());
                            });
                            codendi.tracker.artifact.artifactLink.reload_aggregates_functions(codendi.tracker.artifact.artifactLinker_currentField);
                        }
                    }
                }
            }
        );
        }
    }
    },
    reload_aggregates_functions_request: null,
    reload_aggregates_functions: function (artifactlink_field) {
        //If there is a pending request, abort it
        if (codendi.tracker.artifact.artifactLink.reload_aggregates_functions_request) {
            codendi.tracker.artifact.artifactLink.reload_aggregates_functions_request.abort();
        }
        //remove old aggregates
        artifactlink_field.select('tr.tracker_report_table_aggregates').invoke('remove');
        
        var field_id = artifactlink_field.down('.tracker-form-element-artifactlink-new').name.split('[')[1].split(']')[0];
        
        //Compute the new aggregates
        codendi.tracker.artifact.artifactLink.reload_aggregates_functions_request = new Ajax.Request(
            codendi.tracker.base_url + '?',
            {
                parameters: {
                    formElement: field_id,
                    func: 'fetch-aggregates',
                    ids: artifactlink_field.select('input[type=checkbox][name^="artifact[' + field_id + '][removed_values]"]')
                    .reject(function (checkbox) {return checkbox.checked;})
                    .collect(function (checkbox) {return checkbox.name.split('[')[3].split(']')[0];})
                        .join(',')
                },
                onSuccess: function (transport) {
                    transport.responseJSON.tabs.each(function (tab) {
                        if ($('tracker_report_table_' + tab.key)) {
                            //make sure that the previous aggregates have been removed
                            $('tracker_report_table_' + tab.key).select('tr.tracker_report_table_aggregates').invoke('remove');
                            //insert the new ones
                            $('tracker_report_table_' + tab.key).down('tbody').insert(tab.src);
                        }
                    });
                }
            }
        );
    },
    load_nb_artifacts: function (tracker_panel) {
        var nb_artifacts = tracker_panel.down('tbody').select('tr:not(.tracker_report_table_aggregates)').size();
        var h3 = tracker_panel.down('h3');
        var txt = nb_artifacts + ' ' + codendi.locales.tracker_artifact_link.nb_artifacts;
        if (h3) {
            h3.update(txt);
        } else {
            tracker_panel.insert({top :'<h3>' + txt + '</h3>'});
        }
    },
    
    Tab: Class.create({
        // Store all tracker panels of this artifact links
        tracker_panels: [],
        ul: null,
        initialize: function (artifact_link) {
            var self = this;

            //build a nifty navigation list
            if (location.href.toQueryParams().func != 'new-artifact' && location.href.toQueryParams().func != 'submit-artifact') {
                if (!location.href.toQueryParams().modal) {
                    this.ul = new Element('ul').addClassName('nav nav-tabs tracker-form-element-artifactlink-list-nav');
                }
            }

            artifact_link.insert({top: this.ul});
            //foreach tracker panels, fills the navigation list and put behaviors
            artifact_link.select('h2').each(function(obj) {
                self.loadTab(obj);
            });
        },

        showTrackerPanel: function(event, tracker_panel, element, h2) {
            var ul = element.up('ul');
            ul.childElements().invoke('removeClassName', 'tracker-form-element-artifactlink-list-nav-current');
            ul.childElements().invoke('removeClassName', 'active');
            element.up('li').addClassName('tracker-form-element-artifactlink-list-nav-current active');

            // hide all panels
            tracker_panel.adjacent('div').invoke('hide');

            //except the wanted one
            tracker_panel.show();

            if (! ul.up('div').hasClassName('read-only')) {
                //change the current tracker for the selector
                codendi.tracker.artifact.artifactLink.selector_url.tracker = h2.className.split('_')[1]; // class="tracker-form-element-artifactlink-tracker_974"
            }

            // stop the propagation of the event
            if (event) {
                Event.stop(event);
            }
        },
        
        loadTab: function (h2, tab_list) {
            if (typeof tab_list === 'undefined') {
                tab_list = this.ul;
            }

            var self = this;
            var tracker_panel = h2.up();
            codendi.tracker.artifact.artifactLink.load_nb_artifacts(tracker_panel);
            //add a new navigation element
            var li = new Element('li');
            var a = new Element('a', {
                href: '#show-tab-' + h2.innerHTML

            }).observe('click', function (evt) {
                self.showTrackerPanel(evt, tracker_panel, a, h2);

            }.bind(this));

            a.update(h2.innerHTML);
            
            li.appendChild(a);
            tab_list.appendChild(li);
            
            //hide this panel and its title unless is first
            if (this.tracker_panels.size() == 0) {
                codendi.tracker.artifact.artifactLink.selector_url.tracker = h2.className.split('_')[1]; // class="tracker-form-element-artifactlink-tracker_974"
            }

            if (li == tab_list.firstDescendant()) {
                li.addClassName('tracker-form-element-artifactlink-list-nav-current active');
                this.showTrackerPanel(null, tracker_panel, a, h2);
            }

            h2.hide();
            
            //add this panel to the store
            this.tracker_panels.push(tracker_panel);
        }
    }),
    tabs: { },
    //TODO: replace '1' below by the tracker id of the current artifact
    //      this is helpful(mandatory) when there is not any links yet.
    selector_url: {
        tracker: location.href.toQueryParams().tracker ? location.href.toQueryParams().tracker : 0,
        'link-artifact-id': location.href.toQueryParams().aid ? location.href.toQueryParams().aid : ''
    }
};


document.observe('dom:loaded', function () {

    //{{{ artifact links    

    overlay_window = new lightwindow({
        resizeSpeed: 10,
        delay: 0,
        finalAnimationDuration: 0,
        finalAnimationDelay: 0
    });

    var artifactlink_selector_url = {
        tracker: 1,
        'link-artifact-id': location.href.toQueryParams().aid
    };
    
    $$('.tracker-form-element-artifactlink-list').each(function (artifact_link) {
        codendi.tracker.artifact.artifactLink.tabs[artifact_link.up('.tracker_artifact_field').identify()] = new codendi.tracker.artifact.artifactLink.Tab(artifact_link);
    });
    
    var artifact_links_values = { };

    codendi.tracker.artifact.artifactLink.showReverseArtifactLinks();
    
    function load_behaviors_in_slow_ways_panel() {
        //links to artifacts load in a new browser tab/window
        $$('#tracker-link-artifact-slow-way-content a.cross-reference', 
        '#tracker-link-artifact-slow-way-content a.direct-link-to-artifact',
        '#tracker-link-artifact-slow-way-content a.direct-link-to-user').each(function (a) {
            a.target = '_blank';
        });
        
        var renderer_panel = $$('.tracker_report_renderer')[0];
        load_behavior_in_renderer_panel(renderer_panel);
        
        //links to switch among renderers should load via ajax
        $$('.tracker_report_renderer_tab a').each(function (a) {
            a.observe('click', function (evt) {
                new Ajax.Updater(renderer_panel, a.href, {
                    onComplete: function (transport, json) {
                        a.up('ul').childElements().invoke('removeClassName', 'tracker_report_renderers-current');
                        a.up('li').addClassName('tracker_report_renderers-current');
                        load_behavior_in_renderer_panel();
                    }
                });
                Event.stop(evt);
                return false;
            });
        });
        
        $('tracker_select_tracker').observe('change', function () {
            new Ajax.Updater('tracker-link-artifact-slow-way-content', codendi.tracker.base_url, {
                parameters: {
                    tracker: $F('tracker_select_tracker'),
                    'link-artifact-id': $F('link-artifact-id'),
                    'report-only': 1
                },
                method: 'GET',
                onComplete: function() {
                    load_behaviors_in_slow_ways_panel();
                }
            });
        });
        
        if ($('tracker_select_report')) {
            $('tracker_select_report').observe('change', function () {
                new Ajax.Updater('tracker-link-artifact-slow-way-content', codendi.tracker.base_url, {
                    parameters: {
                        tracker: $F('tracker_select_tracker'),
                        report: $F('tracker_select_report'),
                        'link-artifact-id': $F('link-artifact-id')
                    },
                    method: 'GET',
                    onComplete: function() {
                        load_behaviors_in_slow_ways_panel();
                    }
                });
            });
        }
        
        codendi.Toggler.init($('tracker_report_query_form'), 'hide', 'noajax');
        
        $('tracker_report_query_form').observe('submit', function (evt) {
            $('tracker_report_query_form').request({
                parameters: {
                    aid: 0,
                    'only-renderer': 1,
                    'link-artifact-id': $F('link-artifact-id')
                },
                onSuccess: function (transport, json) {
                    var renderer_panel = $('tracker-link-artifact-slow-way-content').up().down('.tracker_report_renderer');
                    renderer_panel.update(transport.responseText);
                    load_behavior_in_renderer_panel(renderer_panel);
                }
            });
            Event.stop(evt);
            return false;
        });
    }
    
    function force_check(checkbox) {
        var re = new RegExp('(?:^|,)\s*'+ checkbox.value +'\s*(?:,|$)');
        if (artifact_links_values[codendi.tracker.artifact.artifactLinker_currentField_id][checkbox.value] 
            || $$('input[name="artifact['+ codendi.tracker.artifact.artifactLinker_currentField_id +'][new_values]"]')[0].value.match(re)
    ) {
            checkbox.checked = true;
            checkbox.disabled = true;
        }
    }
    
    function load_behavior_in_renderer_panel(renderer_panel) {
        
        codendi.Tooltip.load(renderer_panel);
        
        //pager links should load via ajax
        $$('#tracker_report_table_pager a').each(function (a) {
            a.observe('click', function (evt) {
                new Ajax.Updater(renderer_panel, a.href, {
                    onComplete: function (transport) {
                        load_behavior_in_renderer_panel(renderer_panel);
                    }
                });
                Event.stop(evt);
                return false;
            });
        });
        
        var input_to_link = $('lightwindow_contents').down('input[name="link-artifact[manual]"]');
        $('lightwindow_contents').select('input[name^="link-artifact[search]"]').each(function (elem) {
            add_remove_selected(elem, input_to_link);
        });
        
        //check already linked artifacts in recent panel
        $(renderer_panel).select('input[type=checkbox][name^="link-artifact[search][]"]').each(force_check);
        
        //check manually added artifact in the renderer
        input_to_link.value.split(',').each(function (link) {
            checked_values_panels(link);
        });
        
        //try to resize smartly the lightwindow
        var diff = $('lightwindow_contents').scrollWidth - $('lightwindow_contents').offsetWidth;
        if (diff > 0 && document.body.offsetWidth > $('lightwindow_contents').scrollWidth + 40) {
            
            var previous_left            = $('lightwindow').offsetLeft;
            var previous_container_width = $('lightwindow_container').offsetWidth;
            var previous_contents_width  = $('lightwindow_contents').offsetWidth;
            
            $('lightwindow').setStyle({
                left: Math.round($('lightwindow').offsetLeft - diff/2) +'px'
            });
            
            $('lightwindow_container').setStyle({
                width: Math.round(previous_container_width + diff + 30) +'px'
            });
            
            $('lightwindow_contents').setStyle({
                width: Math.round(previous_contents_width + diff + 30) +'px'
            });
        }
        
        resize_lightwindow.defer();
    }
    
    function resize_lightwindow () {
        var effective_height = $('lightwindow_contents').childElements().inject(0, function (acc, elem) { 
            return acc + (elem.visible() ? elem.getHeight() : 0);
        });
        if (effective_height < $('lightwindow_contents').getHeight()
            || (effective_height > $('lightwindow_contents').getHeight() &&
            (effective_height + 100 < document.documentElement.clientHeight))
    ) {
            $('lightwindow_contents').setStyle({
                height: (effective_height + 20) +'px'
            });
        }
    }
    
    function checked_values_panels(artifact_link_id) {
        checked_values_panel_recent(artifact_link_id, true);
        checked_values_panel_search(artifact_link_id, true);
    }
    
    function checked_values_panel_recent(artifact_link_id, checked) {
        $('lightwindow_contents').select('input[name^="link-artifact[recent]"]').each(function (elem) {
            if (elem.value == artifact_link_id) {
                elem.checked = checked;
            }
        });
    }
    
    function checked_values_panel_search(artifact_link_id, checked) {
        $('lightwindow_contents').select('input[name^="link-artifact[search]"]').each(function (elem) {
            if (elem.value == artifact_link_id) {
                elem.checked = checked;
            }
        });
    }
    
    function add_remove_selected(elem, input_to_link) {
        elem.observe('change', function (evt) {
           
            if(elem.checked) {
                if (input_to_link.value) {
                    input_to_link.value += ',';
                }
                input_to_link.value += elem.value;
            } else {
                input_to_link.value =
                    input_to_link.value
                .split(',')
                .reject(function (link) {
                    return link.strip() == elem.value;
                })
                .join(',');
            }
            if (elem.name == 'link-artifact[search][]') {
                checked_values_panel_recent(elem.value, elem.checked);
            }
            if (elem.name == 'link-artifact[recent][]') {
                checked_values_panel_search(elem.value, elem.checked);
            }
        });
    }
    
    //TODO: inject the links 'create' with javascript to prevent bad usage for non javascript users
    
    // inject the links 'link'
    $$('input.tracker-form-element-artifactlink-new').each(function (input) {
        input.observe('change', codendi.tracker.artifact.artifactLink.addTemporaryArtifactLinks);
        if (location.href.toQueryParams().func == 'new-artifact' || location.href.toQueryParams().func == 'submit-artifact') {
            input.up().insert('<br /><em style="color:#666; font-size: 0.9em;">'+ codendi.locales.tracker_artifact_link.advanced + '<br />'+ input.title +'</em>');
        }
        
        if (location.href.toQueryParams().func != 'new-artifact' && location.href.toQueryParams().func != 'submit-artifact') {
            if (!location.href.toQueryParams().modal) {
                var link = new Element('a', {
                    title: codendi.locales.tracker_artifact_link.select
                })
                .addClassName('tracker-form-element-artifactlink-selector btn')
                .update('<img src="'+ codendi.imgroot +'ic/clipboard-search-result.png" />');
                
                var link_create = new Element('a', {
                    title: codendi.locales.tracker_artifact_link.create,
                    href: '#'
                })
                .addClassName('tracker-form-element-artifactlink-selector btn')
                .update('<img src="'+ codendi.imgroot +'ic/artifact-plus.png" style="vertical-align: middle;"/> ');
                input.up()
                    .insert(link)
                    .insert(link_create)
                    .up()
                        .insert('<br /><em style="color:#666; font-size: 0.9em;">'+ input.title +'</em>');
            }
        }
        
        if (location.href.toQueryParams().modal == 1) {
            input.up().insert('<br /><em style="color:#666; font-size: 0.9em;">'+ codendi.locales.tracker_artifact_link.advanced + '<br />'+ input.title +'</em>');
        }

        if (! link) {
            return;
        }
        codendi.tracker.artifact.artifactLinker_currentField = link.up('.tracker_artifact_field');
        codendi.tracker.artifact.artifactLinker_currentField_id = input.name.gsub(/artifact\[(\d+)\]\[new_values\]/, '#{1}');
        
        //build an array to store the existing links
        if (!artifact_links_values[codendi.tracker.artifact.artifactLinker_currentField_id]) {
            artifact_links_values[codendi.tracker.artifact.artifactLinker_currentField_id] = { };
        }
        link.up('.tracker_artifact_field')
        .select('input[type=checkbox]')
        .inject(artifact_links_values[codendi.tracker.artifact.artifactLinker_currentField_id], function (acc, e) {
            acc[e.name.split('[')[3].gsub(']', '')] = 1;
            return acc;
        });
        
        //register behavior when we click on the [create]
        link_create.observe('click', function(evt) {
                
            //create a new artifact via artifact links
            //tracker='.$tracker_id.'&func=new-artifact-link&id='.$artifact->getId().
            overlay_window.options.afterFinishWindow = function() {
                
            };
            overlay_window.activateWindow({
                href: codendi.tracker.base_url + '?'+ $H({
                    tracker: codendi.tracker.artifact.artifactLink.selector_url.tracker,
                    func: 'new-artifact-link',
                    id: codendi.tracker.artifact.artifactLink.selector_url['link-artifact-id'],
                    modal:1
                }).toQueryString(),
                title: link.title,
                iframeEmbed: true
            });

            Event.stop(evt);
            return false;
        });
        //register behavior when we click on the [link]
        link.observe('click', function (evt) {
                
            $$('button.tracker-form-element-artifactlink-selector')
            overlay_window.options.afterFinishWindow = function() {
                if ($('tracker-link-artifact-fast-ways')) {
                    //Tooltips. load only in fast ways panels 
                    // since report table are loaded later in 
                    // the function load_behavior_...
                    codendi.Tooltip.load('tracker-link-artifact-fast-ways');
                    
                    load_behaviors_in_slow_ways_panel();
                    
                    //links to artifacts load in a new browser tab/window
                    $$('#tracker-link-artifact-fast-ways a.cross-reference', 
                    '#tracker-link-artifact-fast-ways a.direct-link-to-artifact',
                    '#tracker-link-artifact-fast-ways a.direct-link-to-user').each(function (a) {
                        a.target = '_blank';
                    });
                        
                    var input_to_link = $('lightwindow_contents').down('input[name="link-artifact[manual]"]');
                    
                    //Checked/unchecked values are added/removed in the manual panel
                    $('lightwindow_contents').select('input[name^="link-artifact[recent]"]').each(function (elem) {
                        add_remove_selected(elem, input_to_link);
                    });
                    
                    //Check/Uncheck values in recent and search panels linked to manual panel changes
                    function observe_input_field(evt) {
                        
                        var manual_value = input_to_link.value;
                        var links_array = manual_value.split(',');
                        
                        //unchecked values from recent panel
                        $('lightwindow_contents').select('input[name^="link-artifact[recent]"]').each(function (elem) {
                            if (!elem.disabled) {
                                elem.checked = false;
                            }
                        });
                        
                        //unchecked values from search panel
                        $('lightwindow_contents').select('input[name^="link-artifact[search]"]').each(function (elem) {   
                            if (!elem.disabled) {                                                             
                                elem.checked = false;
                            }
                        });
                        
                        links_array.each(function (link) {
                            checked_values_panels(link.strip());
                        });
                    };
                    
                    input_to_link.observe('change', observe_input_field);
                    input_to_link.observe('keyup', observe_input_field);
                    
                    //check already linked artifacts in recent panel
                    $$('#tracker-link-artifact-fast-ways input[type=checkbox][name^="link-artifact[recent][]"]').each(force_check);
    
                    var button = $('lightwindow_contents').down('button[name=link-artifact-submit]');
                    button.observe('click', function (evt) {
                        var to_add = [];
                        
                        //manual ones
                        var manual = $('lightwindow_contents').down('input[name="link-artifact[manual]"]').value;
                        if (manual) {
                            to_add.push(manual);
                        }
                        
                        //add to the existing ones
                        if (to_add.size()) {
                            var input_field = codendi.tracker.artifact.artifactLinker_currentField.down('input.tracker-form-element-artifactlink-new');
                            if (input_field.value) {
                                input_field.value += ',';
                            }
                            input_field.value += to_add.join(',');
                            
                        }
                        codendi.tracker.artifact.artifactLink.addTemporaryArtifactLinks();
                        
                        //hide the modal window
                        overlay_window.deactivate();
                        
                        //stop the propagation of the event (don't submit any forms)
                        Event.stop(evt);
                        return false;
                    });
                }
            };
            
         overlay_window.activateWindow({
                href: location.href.split('?')[0] + '?' + $H(codendi.tracker.artifact.artifactLink.selector_url).toQueryString(),
                title: ''
            });
            Event.stop(evt);
            return false;
        });
    });
    //}}}
    codendi.tracker.artifact.artifactLink.set_checkbox_style_as_cross( $$('.tracker-form-element-artifactlink-list td.tracker_report_table_unlink') );
    codendi.tracker.artifact.artifactLink.addTemporaryArtifactLinks();
});

