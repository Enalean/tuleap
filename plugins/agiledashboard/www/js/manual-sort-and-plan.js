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


document.observe('dom:loaded', function (evt) {

    var enable_highest_lowest_priority = false;

    function highlightCard(element) {
        new Effect.OuterGlow(element, {
            duration: 1,
            startcolor: "#D9EDF7",
            queue: {
                position: 'end',
                scope: 'sort',
                limit: 1
            }
        });
    }

    function scrollViewport(element, previous_offset) {
        //var delta = element.viewportOffset().top - previous_offset.top;
        //window.scrollTo(window.scrollX, Math.max(0, window.scrollY + delta));
    }

    /**
     * resetArrows(elem1, elem2, elem3);
     */
    function resetArrows(li) {
        $A(arguments).each(function (li) {
            if (li) {
                var controls = li.down('.card').down('.card-planning-controls');
                controls.select('i').each(function (i) { i.setStyle({visibility: 'hidden'}); });
                if (li.nextSiblings().size()) {
                    controls.down('i.icon-arrow-down').setStyle({visibility: 'visible'});
                    if (enable_highest_lowest_priority) controls.down('i.icon-circle-arrow-down').setStyle({visibility: 'visible'});
                }
                if (li.previousSiblings().size()) {
                    controls.down('i.icon-arrow-up').setStyle({visibility: 'visible'});
                    if (enable_highest_lowest_priority) controls.down('i.icon-circle-arrow-up').setStyle({visibility: 'visible'});
                }
                if (li.hasClassName('planning-draggable') && li.up('.backlog-content')) {
                    controls.down('i.icon-arrow-right').setStyle({visibility: 'visible'});
                }
                if (li.hasClassName('planning-draggable') && li.up('.milestone-content')) {
                    controls.down('i.icon-arrow-left').setStyle({visibility: 'visible'});
                }
            }
        });
    }

    function moveUp(evt) {
        var li     = Event.element(evt).up('.card').up('li');
        var offset = li.viewportOffset();
        var prev   = li.previous();
        if (prev) {
            li.remove();
            prev.insert({ before: li });
            resetArrows(li.previous(), li, li.next());

            scrollViewport(li, offset);
            highlightCard(li.down('.card'));
            Planning.sort(li);
        }
        Event.stop(evt);
    }

    function moveDown(evt) {
        var li   = Event.element(evt).up('.card').up('li');
        var offset = li.viewportOffset();
        var next = li.next();
        if (next) {
            li.remove();
            next.insert({ after: li });
            resetArrows(li.previous(), li, li.next());

            scrollViewport(li, offset);
            highlightCard(li.down('.card'));
            Planning.sort(li);
        }
        Event.stop(evt);
    }

    function moveToBacklog(evt) {
        var li       = Event.element(evt).up('.card').up('li'),
            prev     = li.previous(),
            next     = li.next(),
            milestone = li.up('.planning-droppable'),
            ancestor = $('art-' + li.readAttribute('data-ancestor-id')) || $$('.backlog-content')[0];
        if (ancestor) {
            li.remove();
            ancestor.down('ul.cards').insert({ top: li });
            resetArrows(li.previous(), li, li.next(), prev, next);
            Planning.move_to_backlog(li, milestone);
        }
        Event.stop(evt);
    }

    function moveToMilestone(evt) {
        var li   = Event.element(evt).up('.card').up('li');
        var prev = li.previous();
        var next = li.next();
        var milestone = $$('.milestone-content > ul.cards')[0];
        li.remove();
        milestone.insert(li);
        resetArrows(li.previous(), li, li.next(), prev, next);
        highlightCard(li.down('.card'));
        Planning.move_to_plan(li, milestone.up('.planning-droppable'));
        Planning.sort(li);

        Event.stop(evt);
    }

    var milestone_content = $$('.milestone-content')[0],
        milestone_title   = milestone_content ? milestone_content.up('td').down('h3').innerHTML : '';
    $$('.backlog-content .card', '.milestone-content .card').each(function (card) {
        var controls = new Element('div')
            .addClassName('card-planning-controls')
            .update('<div>'+
                '<i class="icon-arrow-left" title="'+ codendi.getText('agiledashboard', 'move_backlog') +'"></i>' +
                (enable_highest_lowest_priority ? ('<i class="icon-circle-arrow-down" title="'+ codendi.getText('agiledashboard', 'move_bottom') +'"></i>') : '') +
                '<i class="icon-arrow-down" title="'+ codendi.getText('agiledashboard', 'move_down') +'"></i>' +
                '<i class="icon-arrow-up" title="'+ codendi.getText('agiledashboard', 'move_up') +'"></i>' +
                (enable_highest_lowest_priority ? ('<i class="icon-circle-arrow-up" title="'+ codendi.getText('agiledashboard', 'move_top') +'"></i>') : '') +
                '<i class="icon-arrow-right" title="'+ codendi.getText('agiledashboard', 'move_plan', milestone_title) + '"></i>' +
                '</div>'
            );
        card.insert(controls);
        resetArrows(card.up('li'));
        controls.observe('click', function (evt) {
            var element = Event.element(evt);
            if (element.hasClassName('icon-arrow-down'))        moveDown(evt);
            if (element.hasClassName('icon-arrow-up'))          moveUp(evt);
            if (element.hasClassName('icon-circle-arrow-down')) moveBottom(evt);
            if (element.hasClassName('icon-circle-arrow-up'))   moveTop(evt);
            if (element.hasClassName('icon-arrow-left'))        moveToBacklog(evt);
            if (element.hasClassName('icon-arrow-right'))       moveToMilestone(evt);
        });
    });
});


Effect.OuterGlow = Class.create(Effect.Highlight, {
  setup: function() {
    // Prevent executing on elements not in the layout flow
    if (this.element.getStyle('display')=='none') { this.cancel(); return; }
    this.oldStyle = { boxShadow: this.element.getStyle('box-shadow') };
  },
  update: function(position) {
    this.element.setStyle({ boxShadow: '0px 0px ' + Math.round(30 - 30 * position) + 'px ' + this.options.startcolor });
  },
  finish: function() {
    this.element.setStyle(this.oldStyle);
  }
});
