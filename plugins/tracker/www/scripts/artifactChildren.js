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

    initialize : function(base_url, container, locales, imgroot, artifact_id) {
        var renderer = new tuleap.artifact.HierarchyViewer.Renderer(container, locales, imgroot),
            provider = new tuleap.artifact.HierarchyViewer.ItemProvider(base_url, renderer),
            root = renderer.insertRoot({
                id: artifact_id,
                parent_id: null,
                xref: null,
                title: null,
                status: null
            }),
            top_item = new tuleap.artifact.HierarchyViewer.Item(artifact_id, root, provider);

        top_item.open();
    }
});

/**
 * Display children and subchildren in the page
 */
tuleap.artifact.HierarchyViewer.Renderer = Class.create({

    initialize : function (container, locales, imgroot) {
        this.container = container;
        this.locales   = locales;
        this.imgroot   = imgroot;
        this.insertTable();
        this.body = this.container.down('tbody');
        this.row_template = new Template('<tr class="artifact-child" data-child-id="#{id}" data-parent-id="#{parent_id}"> \
                <td> \
                    <a href="#" class="toggle-child"><img src="'+ imgroot +'pointer_right.png" /></a> \
                    <a href="#{url}">#{xref}</a> \
                </td> \
                <td>#{title}</td> \
                <td>#{status}</td> \
            </tr>');
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

    insertRoot: function (root) {
        this.body.insert(this.row_template.evaluate(root));
        var element = this.body.childElements().last();
        element.setAttribute('data-is-root', 1);
        element.hide();
        return element;
    },

    insertChildAfter: function (parent_id, child) {
        var parent = this.body.down('tr[data-child-id='+ parent_id +']'),
            element;

        parent.insert({after: this.row_template.evaluate(child)});

        element = parent.next();
        if (! child.has_children) {
            element.down('a.toggle-child').setStyle({
                visibility: 'hidden'
            });
        }
        this.adjustPadding(parent, element);

        return element;
    },

    adjustPadding: function (parent, child) {
        var padding_left = 0;
        if (! parent.getAttribute('data-is-root')) {
            padding_left = ~~parent.down('td').getStyle('padding-left').sub('px', '') + 24
        }
        child.down('td').setStyle({
            paddingLeft: padding_left + 'px'
        });
    },

    startSpinner: function () {
        this.container.up('body').setStyle({
            cursor: 'progress'
        });
    },

    stopSpinner: function () {
        this.container.up('body').setStyle({
            cursor: 'default'
        });
    }
});

/**
 * Provide children of an item
 */
tuleap.artifact.HierarchyViewer.ItemProvider = Class.create({

    initialize : function(base_url, renderer) {
        this.base_url   = base_url;
        this.renderer   = renderer;
        this.nb_request = 0;
    },

    injectChildren: function (item) {
        this.nb_request--;
        this.renderer.startSpinner();
        new Ajax.Request(this.base_url, {
            method: 'GET',
            parameters: {
                aid: item.getId(),
                func: 'get-children'
            },
            onSuccess: function (transport) {
                this.receiveChildren(item, transport.responseJSON);
            }.bind(this),
            onComplete: function () {
                if (this.nb_request--) {
                    this.renderer.stopSpinner()
                }
            }.bind(this)
        });
    },

    receiveChildren: function (parent, children) {
        children.map(function (child) {
            var element = this.renderer.insertChildAfter(parent.getId(), child),
                item    = new tuleap.artifact.HierarchyViewer.Item(child.id, element, this);
            parent.addChild(item);
        }.bind(this));
    }
});

/**
 * A child
 */
tuleap.artifact.HierarchyViewer.Item = Class.create({

    initialize: function(id, element, item_provider) {
        this.id            = id;
        this.element       = element;
        this.item_provider = item_provider;
        this.children      = [];
        this.is_open       = false;

        this.icon          = element.down('a.toggle-child img');
        this.icon.observe('click', function (evt) {
            if (this.is_open) {
                this.close();
            } else {
                this.open();
            }
            Event.stop(evt);
        }.bind(this));
    },

    getId: function () {
        return this.id;
    },

    addChild: function (child) {
        if (! this.children) {
            this.children = [];
        }

        this.children.push(child);
    },

    open: function () {
        this.is_open = true;
        this.useHideIcon();
        if (this.children.size()) {
            this.showChildren();
        } else {
            this.item_provider.injectChildren(this);
        }
    },

    close: function () {
        this.is_open = false;
        this.useShowIcon();
        this.hideChildren();
    },

    show: function () {
        this.element.show();
        if (this.is_open) {
            this.showChildren();
        }
    },

    hide: function () {
        this.element.hide();
        this.hideChildren();
    },

    hideChildren: function () {
        this.children.each(function (child) {
            child.hide();
        });
    },

    showChildren: function () {
        this.children.each(function (child) {
            child.show();
        });
    },

    useShowIcon: function () {
        this.icon.src = this.icon.src.sub(/down.png$/, 'right.png');
    },

    useHideIcon: function () {
        this.icon.src = this.icon.src.sub(/right.png$/, 'down.png');
    },
});