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
    function resetArrows(li) {
        if (li) {
            var controls = li.down('.card').down('.card-planning-controls');
            controls.select('i').each(function (i) { i.setStyle({visibility: 'hidden'}); });
            if (li.nextSiblings().size()) {
                controls.down('i.icon-arrow-down').setStyle({visibility: 'visible'});
            }
            if (li.previousSiblings().size()) {
                controls.down('i.icon-arrow-up').setStyle({visibility: 'visible'});
            }
            if (li.hasClassName('planning-draggable') && li.up('.backlog-content')) {
                controls.down('i.icon-arrow-right').setStyle({visibility: 'visible'});
            }
            if (li.hasClassName('planning-draggable') && li.up('.milestone-content')) {
                controls.down('i.icon-arrow-left').setStyle({visibility: 'visible'});
            }
        }
    }

    function moveUp(evt) {
        var li     = Event.element(evt).up('.card').up('li');
        var offset = li.viewportOffset();
        var prev   = li.previous();
        if (prev) {
            //todo: ajax
            li.remove();
            prev.insert({ before: li });
            resetArrows(li.previous());
            resetArrows(li);
            resetArrows(li.next());

            var delta = li.viewportOffset().top - offset.top;
            window.scrollTo(window.scrollX, Math.max(0, window.scrollY + delta));
        }
        Event.stop(evt);
    }

    function moveDown(evt) {
        var li   = Event.element(evt).up('.card').up('li');
        var offset = li.viewportOffset();
        var next = li.next();
        if (next) {
            //todo: ajax
            li.remove();
            next.insert({ after: li });
            resetArrows(li.previous());
            resetArrows(li);
            resetArrows(li.next());

            var delta = li.viewportOffset().top - offset.top;
            window.scrollTo(window.scrollX, Math.max(0, window.scrollY + delta));
        }
        Event.stop(evt);
    }

    function moveToBacklog(evt) {
        var li   = Event.element(evt).up('.card').up('li');
        var prev = li.previous();
        var next = li.next();
        var backlog = $$('.backlog-content')[0].down('ul.cards');
        //todo: ajax
        // how do we know where to put the card?

        Event.stop(evt);
    }

    function moveToMilestone(evt) {
        var li   = Event.element(evt).up('.card').up('li');
        var prev = li.previous();
        var next = li.next();
        var milestone = $$('.milestone-content')[0].down('ul.cards');
        //todo: ajax
        li.remove();
        milestone.insert(li);
        resetArrows(li.previous());
        resetArrows(li);
        resetArrows(li.next());
        resetArrows(prev);
        resetArrows(next);

        Event.stop(evt);
    }

    $$('.backlog-content .card', '.milestone-content .card').each(function (card) {
        var controls = new Element('div')
            .addClassName('card-planning-controls')
            .update('<div>'+
                '<i class="icon-arrow-left" title="move to backlog"></i>' +
                '<i class="icon-arrow-down" title="move down"></i>' +
                '<i class="icon-arrow-up" title="move up"></i>' +
                '<i class="icon-arrow-right" title="move to milestone"></i>' +
                '</div>'
            );
        card.insert(controls);
        resetArrows(card.up('li'));
        controls.observe('click', function (evt) {
            var element = Event.element(evt);
            if (element.hasClassName('icon-arrow-down'))  moveDown(evt);
            if (element.hasClassName('icon-arrow-up'))    moveUp(evt);
            if (element.hasClassName('icon-arrow-left'))  moveToBacklog(evt);
            if (element.hasClassName('icon-arrow-right')) moveToMilestone(evt);
        });
    });
});
