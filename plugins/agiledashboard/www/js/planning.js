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

var tuleap = tuleap || { };
tuleap.agiledashboard = tuleap.agiledashboard || { };

/**
 * Add the ControlPanel (div that contains the action arrows) on each card.
 * Manage click on arrows
 */
tuleap.agiledashboard.PlanningManualControls = Class.create({
    initialize: function (planning, planner, milestone, milestone_content, milestone_cards) {
        this.planning          = planning;
        this.planner           = planner;
        this.milestone         = milestone;
        this.milestone_content = milestone_content;
        this.milestone_cards   = milestone_cards;
        this.milestone_title   = this.milestone ? this.milestone.previous('h3').innerHTML   : '';
        this.backlog_content   = this.planner.down('.backlog-content');

        this.backlog_content.select('.card').each(this.addControlPanelToCard.bind(this));
        if (this.milestone_content) {
            this.milestone_content.select('.card').each(this.addControlPanelToCard.bind(this));
        }
    },

    addControlPanelToCard: function (card) {
        this.appendArrowsContainer(card);
        this.resetArrowsAccordingToCardsPositions(card.up('li'));
    },

    appendArrowsContainer: function (card) {
        var controls = new Element('div')
            .addClassName('card-planning-controls')
            .update('<div>' +
                (this.milestone ? '<i class="icon-arrow-left" title="'+ codendi.getText('agiledashboard', 'move_backlog') +'"></i>' : '') +
                '<i class="icon-arrow-down" title="'+ codendi.getText('agiledashboard', 'move_down') +'"></i>' +
                '<i class="icon-arrow-up" title="'+ codendi.getText('agiledashboard', 'move_up') +'"></i>' +
                (this.milestone ? '<i class="icon-arrow-right" title="'+ codendi.getText('agiledashboard', 'move_plan', this.milestone_title) + '"></i>' : '') +
                '</div>'
            );
        card.insert(controls);
        controls.observe('click', this.clickOnArrows.bind(this));
    },

    clickOnArrows: function (evt) {
        var element = Event.element(evt);
        if (element.hasClassName('icon-arrow-down'))  this.moveDown(evt);
        if (element.hasClassName('icon-arrow-up'))    this.moveUp(evt);
        if (element.hasClassName('icon-arrow-left'))  this.moveToBacklog(evt);
        if (element.hasClassName('icon-arrow-right')) this.moveToMilestone(evt);
    },

    highlightCard: function(element) {
        new Effect.OuterGlow(element, {
            duration: 2,
            startcolor: "#D9EDF7",
            queue: {
                position: 'end',
                scope: 'sort',
                limit: 1
            }
        });
    },

    scrollViewport: function(element, previous_offset) {
        //var delta = element.viewportOffset().top - previous_offset.top;
        //window.scrollTo(window.scrollX, Math.max(0, window.scrollY + delta));
    },

    getCardLi: function (evt) {
        return Event.element(evt).up('.card').up('li')
    },

    moveUp: function(evt) {
        var li      = this.getCardLi(evt);
        var sibling = li.previous();
        this.moveVertically(li, sibling, 'before');
        Event.stop(evt);
    },

    moveDown: function(evt) {
        var li      = this.getCardLi(evt);
        var sibling = li.next();
        this.moveVertically(li, sibling, 'after');
        Event.stop(evt);
    },

    moveVertically: function(li, sibling, way) {
        if (sibling) {
            li.remove();

            var options  = { };
            options[way] = li;
            sibling.insert(options);

            this.resetArrowsAndHighlight(li, li.previous(), li.next());
            this.planning.sort(li);
        }
    },

    moveToBacklog: function(evt) {
        var li       = this.getCardLi(evt);
        var prev     = li.previous();
        var next     = li.next();
        var ancestor = $('art-' + li.readAttribute('data-ancestor-id')) || this.backlog_content;
        if (ancestor) {
            li.remove();
            ancestor.down('ul.cards').insert({ top: li });

            this.resetArrowsAndHighlight(li, li.previous(), li.next(), prev, next);
            this.planning.moveToBacklog(li);
        }
        Event.stop(evt);
    },

    moveToMilestone: function(evt) {
        if (this.milestone) {
            var li   = this.getCardLi(evt);
            var prev = li.previous();
            var next = li.next();

            li.remove();
            this.milestone_cards.insert(li);

            this.resetArrowsAndHighlight(li, li.previous(), li.next(), prev, next);
            this.planThenSort(li);
        }

        Event.stop(evt);
    },

    planThenSort: function (li) {
        this.planning.moveToMilestone(li);
        this.planning.sort(li);
    },
    
    resetArrowsAndHighlight: function (li) {
        this.resetArrowsAccordingToCardsPositions.apply(this, arguments);
        this.highlightCard(li.down('.card'));
    },

    /**
     * By default all arrows are hidden, display only the relevant ones
     * resetArrowsAccordingToCardsPositions(elem1, elem2, elem3);
     */
    resetArrowsAccordingToCardsPositions: function (li) {
        $A(arguments).each(function (li) {
            if (li) {
                var controls = li.down('.card').down('.card-planning-controls');
                controls.select('i').map(this.hideArrow);
                this.showArrowsAccordingToPosition(controls, li);
            }
        }.bind(this));
    },
    showArrowsAccordingToPosition: function (controls, li) {
        if (li.nextSiblings().size()) {
            this.showArrow(controls.down('i.icon-arrow-down'));
        }
        if (li.previousSiblings().size()) {
            this.showArrow(controls.down('i.icon-arrow-up'));
        }
        if (li.hasClassName('planning-draggable') && li.up('.backlog-content')) {
            this.showArrow(controls.down('i.icon-arrow-right'));
        }
        if (li.hasClassName('planning-draggable') && li.up('.milestone-content')) {
            this.showArrow(controls.down('i.icon-arrow-left'));
        }
    },
    hideArrow: function (arrow) {
        arrow.setStyle({visibility: 'hidden'});
    },
    showArrow: function (arrow) {
        arrow.setStyle({visibility: 'visible'});
    }
});

tuleap.agiledashboard.PlanningDragNDrop = Class.create({
    initialize: function (planning, manual_controls, planner) {
        this.planning        = planning;
        this.planner         = planner;
        this.manual_controls = manual_controls;

        this.loadSortables(this.planner);
    },

    loadSortables: function (container) {
        (function ($j, planning, controls) {
            var options = {
                placeholder: 'card placeholder',
                scroll: true,
                stop: function (event, ui) {
                    var current_item = ui.item.get(0);
                    planning.sort(current_item);
                    controls.resetArrowsAccordingToCardsPositions.apply(this, $(current_item).up().childElements());
                }
            };
            $j('.backlog-content ul.cards, .milestone-content ul.cards').sortable(options);
        })(window.jQuery, this.planning, this.manual_controls);
    }
});

tuleap.agiledashboard.PlanningRequest = Class.create({

    activeRequestCount: 0,

    initialize: function (planning, planner, milestone) {
        this.planning  = planning;
        this.planner   = planner;
        this.milestone = milestone;
        Ajax.Responders.register({
            onCreate:   this.toggleFeedback.bind(this),
            onComplete: this.toggleFeedback.bind(this)
        });
    },

    toggleFeedback: function () {
        var feedback_is_displayed = this.planner.hasClassName('show_feedback');
        if (this.activeRequestCount) {
            this.planner
                .addClassName('show_feedback')
                .down('ul')
                    .removeClassName('feedback_info')
                    .addClassName('feedback_warning')
                    .down('li')
                        .update(codendi.locales.agiledashboard.saving);
        } else if (! this.activeRequestCount && feedback_is_displayed) {
            this.planner
                .down('ul')
                    .removeClassName('feedback_warning')
                    .addClassName('feedback_info')
                    .down('li')
                        .update(codendi.locales.agiledashboard.saving_done);
            window.setTimeout(this.hideFeedback.bind(this), 5000);
        }
    },

    hideFeedback: function() {
        if ( ! this.activeRequestCount) {
            this.planner.removeClassName('show_feedback');
        }
    },

    unAssociateArtifactTo: function (sourceId, targetId) {
        this.executePlanRequest('unassociate-artifact-to', sourceId, targetId);
    },

    associateArtifactTo: function (sourceId, targetId) {
        this.executePlanRequest('associate-artifact-to', sourceId, targetId);
    },

    executePlanRequest: function (func, sourceId, targetId) {
        this.executeRequest('?func='+ func +'&linked-artifact-id=' + sourceId + '&aid=' + targetId);
    },

    sortHigher: function (sourceId, targetId) {
        this.sort('higher-priority-than', sourceId, targetId);
    },

    sortLesser: function (sourceId, targetId) {
        this.sort('lesser-priority-than', sourceId, targetId);
    },

    sort: function (func, sourceId, targetId) {
        this.executeRequest('?func='+ func +'&aid=' + sourceId + '&target-id=' + targetId);
    },

    executeRequest: function (query) {
        ++this.activeRequestCount;
        new Ajax.Request(codendi.tracker.base_url + query, {
            onFailure: this.errorOccured.bind(this),
            onSuccess: function (transport) {
                --this.activeRequestCount;
                if (transport.responseJSON && typeof transport.responseJSON.remaining_effort !== undefined) {
                    this.milestone.select('.planning_remaining_effort').each(function (element) {
                        element.update(transport.responseJSON.remaining_effort);
                        if (transport.responseJSON.is_over_capacity) {
                            element.up('span').addClassName('planning_overcapacity');
                        } else {
                            element.up('span').removeClassName('planning_overcapacity');
                        }
                        new Effect.Highlight(element, {
                            queue: {
                                position: 'end',
                                scope: 'remaining_effort',
                                limit: 2
                            }
                        });
                    });
                }
            }.bind(this)
        });
    },

    errorOccured: function (transport) {
        --this.activeRequestCount;
        codendi.feedback.log('error', transport.responseText);
    }

});

tuleap.agiledashboard.Planning = Class.create({

    initialize: function (planner) {
        this.planner           = $(planner);
        this.milestone         = this.planner.down('.planning-droppable');
        this.milestone_content = this.milestone ? this.milestone.down('.milestone-content') : null;
        this.milestone_noitems = this.milestone ? this.milestone.down('.milestone-noitems') : null;
        this.milestone_cards   = this.milestone ? this.milestone_content.down('ul.cards')   : null;

        this.manual_controls = new tuleap.agiledashboard.PlanningManualControls(this, this.planner, this.milestone, this.milestone_content, this.milestone_cards);
        this.drag_n_drop     = new tuleap.agiledashboard.PlanningDragNDrop(this, this.manual_controls, this.planner);
        this.request         = new tuleap.agiledashboard.PlanningRequest(this, this.planner, this.milestone);

        this.ensureThatIdsAreUniqOnBothPanelsOfThePlanningWithAHack();

    },

    ensureThatIdsAreUniqOnBothPanelsOfThePlanningWithAHack: function () {
        if (this.milestone_content) {
            this.milestone_content.select('.dropdown').each(function (dropdown) {
                dropdown.id += '-backlog';
                var a = dropdown.down('a.dropdown-toggle');
                if (a) {
                    a.writeAttribute('data-target', a.readAttribute('data-target') + '-backlog');
                }
            });
        }
    },

    sort: function (item) {
        var next = $(item).next();
        var prev = $(item).previous();
        if (next) {
            this.request.sortHigher(this.getArtifactId(item), this.getArtifactId(next));
        } else if (prev) {
            this.request.sortLesser(this.getArtifactId(item), this.getArtifactId(prev));
        }
    },

    moveToMilestone: function (current_item) {
        this.request.associateArtifactTo(this.getArtifactId(current_item), this.getArtifactId(this.milestone));
        this.milestone_noitems.hide();
    },

    moveToBacklog: function (current_item) {
        this.request.unAssociateArtifactTo(this.getArtifactId(current_item), this.getArtifactId(this.milestone));
        if (! this.milestone_cards.down('li')) {
            this.milestone_noitems.show();
        }
    },

    getArtifactId: function (card) {
        return parseInt(card.id.match(/art-(\d+)/)[1]);
    }
});

// inline-blocks may have different heights (depends on the content)
// so align them to have sexy homepage
tuleap.agiledashboard.align_short_access_heights = function() {
    $$('.ad_index_plannings').map(tuleap.agiledashboard.fixHeightOfShortAccessBox);
}

tuleap.agiledashboard.resetHeightOfShortAccessBox = function(list_of_plannings) {
    list_of_plannings.childElements().invoke('setStyle', { height: 'auto' });
}
tuleap.agiledashboard.fixHeightOfShortAccessBox = function(list_of_plannings) {
    var max_height = list_of_plannings.childElements().inject(0, function(m, v) {
        return Math.max(m, v.getHeight());
    });
    list_of_plannings.childElements().invoke('setStyle', {height: max_height+'px'});
}
