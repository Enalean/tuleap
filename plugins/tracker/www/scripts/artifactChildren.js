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

    initialize : function(base_url, container) {
        this.base_url = base_url;
        this.container = container;
    },

    getArtifactChildren : function(artifact_id) {
        new Ajax.Request( this.base_url + '/artifactChildren.json', {
            method : 'GET',
            parameters : { aid : artifact_id },
            onSuccess : this.receiveChildren.bind(this)
        });
    },

    receiveChildren: function (transport) {
        var children = transport.responseJSON;

        var table = new Element('table');
        this.container.insert(table);

        children.map(function (child) {
            this.insertChild(table, child, this.base_url);
        }.bind(this));
    },

    insertChild: function (table, child, base_url) {
        var row = new Element('tr'),
            cell_xref = new Element('td').update(
                new Element('a', {
                    href: base_url + '/?aid=' + child.id
                }).update(child.xref)
            ),
            cell_status = new Element('td').update(child.status),
            cell_title = new Element('td').update(child.title);

        row.insert(cell_xref);
        row.insert(cell_title);
        row.insert(cell_status);
        table.insert(row);
    }
});
