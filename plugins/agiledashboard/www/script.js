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
 
codendi.Tree = { };
codendi.Tree.Node = Class.create({
    initialize: function (node) {
        this.node   = $(node);
        this.method = 'hide';
        this.id     = this.node.id.match(/-(\d+)$/)[1];
        this.level  = this.node.previousSiblings().size();
        this.toggleEvent = this.toggle.bindAsEventListener(this);
        this.node.observe('click', this.toggleEvent);
        this.node.update('<img src="'+ codendi.imgroot +'ic/toggle-small.png" />');
    },
    toggle: function (evt) {
        var stop = false;
        var siblings = this.node
            .up('tr')
            .nextSiblings()
            .findAll(function (tr) {
                var found = false;
                if (!stop) {
                    stop = true;
                    var div_at_the_same_level = tr.down('td').down('div', this.level);
                    if (div_at_the_same_level) {
                        found = ! (div_at_the_same_level.hasClassName('tree-last') || div_at_the_same_level.hasClassName('tree-node'));
                        if (found) {
                            stop = false;
                        }
                    }
                }
                return found;
            }.bind(this));
        
        siblings.invoke(this.method);
        
        if (this.method == 'hide') {
            this.node.update('<img src="'+ codendi.imgroot +'ic/toggle-small-expand.png" />');
            this.method = 'show';
        } else {
            this.node.update('<img src="'+ codendi.imgroot +'ic/toggle-small.png" />');
            this.method =  'hide';
        }
        
        Event.stop(evt);
        return false;
    }
});


document.observe('dom:loaded', function () {
        try {
    $$('.tree-collapsable').each(function (node) {
        new codendi.Tree.Node(node);
    });
        } catch (e) {
            console.error(e);
        }
});
