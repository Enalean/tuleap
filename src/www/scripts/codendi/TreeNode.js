/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/* global codendi:readonly Class:readonly $:readonly $$:readonly */

codendi.Tree = {};
codendi.Tree.nodes = {};

/**
 * Allow expand/collapse of TreeNodes
 *
 * /!\ This works only for TreeNode contained in a <table>.
 *     <ul> is not supported for now.
 */
codendi.Tree.Node = Class.create({
    initialize: function (node) {
        this.node = $(node);
        this.siblings = {};
        this.id = this.extractId(node);
        this.level = this.node.previousSiblings().size();
        this.toggleEvent = this.toggle.bindAsEventListener(this);
        this.collapse_icon = '<img src="' + codendi.imgroot + 'ic/toggle-small.png" />';
        this.expand_icon = '<img src="' + codendi.imgroot + 'ic/toggle-small-expand.png" />';
        this.node.observe("click", this.toggleEvent);
        this.displayCollapseIcon();
        codendi.Tree.nodes[this.id] = this;
    },
    extractId: function (node) {
        return node.id.match(/-(\d+)$/)[1];
    },
    displayCollapseIcon: function () {
        this.node.update(this.collapse_icon);
        this.method = "hide";
    },
    displayExpandIcon: function () {
        this.node.update(this.expand_icon);
        this.method = "show";
    },
    toggle: function (evt) {
        this.getSiblings().map(this.toggleSibling.bind(this));
        this.toggleIconForNodeAccordinglyToMethod(this, this.method);

        Event.stop(evt);
        return false;
    },
    getSiblings: function () {
        if (!this.siblings[this.id]) {
            this.a_sibling_has_been_found = false;
            this.siblings = this.node.up("tr").nextSiblings().findAll(this.isDescendant.bind(this));
        }
        return this.siblings;
    },
    isDescendant: function (tr) {
        var is_a_child_or_subchild = false;
        if (!this.a_sibling_has_been_found) {
            this.a_sibling_has_been_found = true;
            var div_at_the_same_level = tr.down("td").down("div", this.level);
            if (div_at_the_same_level) {
                is_a_child_or_subchild = this.divAtSameLevelTellsThatNodeIsAChildOrSubchild(
                    div_at_the_same_level
                );
                if (is_a_child_or_subchild) {
                    this.a_sibling_has_been_found = false;
                }
            }
        }
        return is_a_child_or_subchild;
    },
    divAtSameLevelTellsThatNodeIsAChildOrSubchild: function (div) {
        return !(div.hasClassName("tree-last") || div.hasClassName("tree-node"));
    },
    toggleSibling: function (tr) {
        var collapsable = tr.down("td").down(".tree-collapsable");
        if (collapsable) {
            var id = this.extractId(collapsable);
            if (codendi.Tree.nodes[id]) {
                this.toggleIconForNodeAccordinglyToMethod(codendi.Tree.nodes[id], this.method);
            }
        }
        tr[this.method].apply(tr);
    },
    toggleIconForNodeAccordinglyToMethod: function (treenode, method) {
        if (method == "hide") {
            treenode.displayExpandIcon();
        } else {
            treenode.displayCollapseIcon();
        }
    },
});

document.observe("dom:loaded", function () {
    $$(".tree-collapsable").each(function (node) {
        new codendi.Tree.Node(node);
    });
});
