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


    initialize : function(base_url, container, locales) {
        this.base_url  = base_url;
        this.container = container;
        this.locales   = locales;
    },

    getArtifactChildren : function(artifact_id) {
        new Ajax.Request( this.base_url, {
            method : 'GET',
            parameters : {
                aid : artifact_id,
                func : 'get-children'
            },
            onSuccess : this.receiveChildren.bind(this)
        });
    },

    receiveChildren: function (transport) {
        var children = transport.responseJSON,
            tbody;

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

        this.container.insert('<table class="tree-view artifact-children-table"> \
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

    insertChild: function (tbody, child) {
        var template = new Template('<tr class="artifact-child"> \
                <td><a href="#{url}">#{xref}</a></td> \
                <td>#{title}</td> \
                <td>#{status}</td> \
            </tr>')

        tbody.insert(template.evaluate(child));
    }
});