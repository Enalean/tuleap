/**
  * Copyright (c) Enalean, 2013. All rights reserved
  *
  * This file is a part of Tuleap.
  *
  * Tuleap is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or
  * (at your option) any later version.
  *
  * Tuleap is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

var tuleap = tuleap || { };
tuleap.artifact = tuleap.artifact || { };

tuleap.artifact.HierarchyViewer = Class.create({

    initialize : function(base_url, container, locales, imgroot) {
        this.base_url  = base_url;
        this.container = container;
        this.locales   = locales;
        this.row_template = new Template('<tr class="artifact-child" data-child-id="#{id}" data-parent-id="#{parent_id}"> \
                <td> \
                    <a href="#" class="toggle"><img src="'+ imgroot +'pointer_right.png" /></a> \
                    <a href="#{url}">#{xref}</a> \
                </td> \
                <td>#{title}</td> \
                <td>#{status}</td> \
            </tr>');
    },

    getArtifactChildren : function(artifact_id) {
        new Ajax.Request( this.base_url, {
            method : 'GET',
            parameters : {
                aid : artifact_id,
                func : 'get-children'
            },
            onSuccess : function (transport) {
                this.receiveChildren(artifact_id, transport.responseJSON);
            }.bind(this)
        });
    },

    receiveChildren: function (parent_id, children) {
        var tbody,
            existing_parent = this.container.down('tr[data-child-id='+ parent_id +']');

        if (existing_parent) {
            children.map(function (child) {
                this.insertChildAfter(existing_parent, child);
            }.bind(this));
            return;
        }

        if (! children.length) {
            this.displaysNoChild();
            return;
        }

        this.insertTable();
        tbody = this.container.down('tbody');

        children.map(function (child) {
            this.insertChild(tbody, child);
        }.bind(this));
    },

    displaysNoChild: function () {
        var message = this.locales.tracker_hierarchy.no_child_artifacts;
        this.container.insert('<em class="info-no-child">' + message + '</em>');
    },

    insertTable: function () {
        var title  = this.locales.tracker_hierarchy.title_column_name,
            status = this.locales.tracker_hierarchy.status_column_name;

        this.container.insert('<table class="artifact-children-table"> \
                <thead> \
                    <tr class="boxtable artifact-children-table-head"> \
                        <th class="boxtitle"></th> \
                        <th class="boxtitle">'+ title +'</th> \
                        <th class="boxtitle">'+ status +'</th> \
                    </tr> \
                </thead> \
                <tbody> \
                </tbody> \
            </table>');
    },

    insertChildAfter: function (parent, child) {
        var icon = parent.down('a.toggle img');
        icon.src = icon.src.sub(/right.png$/, 'down.png');
        parent.insert({after: this.row_template.evaluate(child)});
        var padding_left = ~~parent.down('td').getStyle('padding-left').sub('px', '') + 24;
        parent.next().down('td').setStyle({
                paddingLeft:  padding_left + 'px'
        });
        this.registerEvent(parent.up('tbody'), child);
    },

    insertChild: function (tbody, child) {
        tbody.insert(this.row_template.evaluate(child));
        this.registerEvent(tbody, child);
    },

    registerEvent: function (tbody, child) {
        tbody.down('tr[data-child-id='+ child.id +'] a.toggle').observe('click', function (evt) {
            var icon = evt.element();
            console.log(icon);
            //.down('img');
            icon.src = icon.src.sub(/down.png$/, 'right.png');
            this.toggleItem(tbody, child.id);
            Event.stop(evt);
        }.bind(this));
    },

    toggleItem: function (tbody, item_id) {
        var existing_child = tbody.select('tr[data-parent-id='+ item_id +']');
        if (existing_child.size()) {
            if (existing_child[0].visible()) {
                existing_child.map(this.hideRecursive.bind(this));
            } else {
                existing_child.map(this.showRecursive.bind(this));
            }
        } else {
            this.getArtifactChildren(item_id);
        }
    },

    showRecursive: function (tr) {
        var icon = tr.down('a.toggle img');
        icon.src = icon.src.sub(/right.png$/, 'down.png');
        tr.show();
        tr.up().select('tr[data-parent-id='+ tr.getAttribute('data-child-id') +']').map(this.showRecursive.bind(this));
    },

    hideRecursive: function (tr) {
        var icon = tr.down('a.toggle img');
        icon.src = icon.src.sub(/down.png$/, 'right.png');
        tr.hide();
        tr.up().select('tr[data-parent-id='+ tr.getAttribute('data-child-id') +']').map(this.hideRecursive.bind(this));
    }
});