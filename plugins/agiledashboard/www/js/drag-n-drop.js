

var Planning = {

    trackerBaseUrl: '/plugins/tracker/',

    activeRequestCount: 0,

    toggleFeedback: function () {
        var feedback_is_displayed = $('planner').hasClassName('show_feedback');
        if (Planning.activeRequestCount) {
            $('planner')
                .addClassName('show_feedback')
                .down('ul')
                    .removeClassName('feedback_info')
                    .addClassName('feedback_warning')
                    .down('li')
                        .update(codendi.locales.agiledashboard.saving);
        } else if (! Planning.activeRequestCount && feedback_is_displayed) {
            $('planner')
                .down('ul')
                    .removeClassName('feedback_warning')
                    .addClassName('feedback_info')
                    .down('li')
                        .update(codendi.locales.agiledashboard.saving_done);
            Planning.hideFeedback.delay(5);
        }
    },

    hideFeedback: function() {
        if ( ! Planning.activeRequestCount) {
            $('planner').removeClassName('show_feedback');
        }
    },

    unAssociateArtifactTo: function (sourceId, targetId) {
        Planning.executePlanRequest('unassociate-artifact-to', sourceId, targetId);
    },

    associateArtifactTo: function (sourceId, targetId) {
        Planning.executePlanRequest('associate-artifact-to', sourceId, targetId);
    },

    executePlanRequest: function (func, sourceId, targetId) {
        Planning.executeRequest('?func='+ func +'&linked-artifact-id=' + sourceId + '&aid=' + targetId);
    },

    executeSortRequest: function (func, sourceId, targetId) {
        Planning.executeRequest('?func='+ func +'&aid=' + sourceId + '&target-id=' + targetId);
    },

    executeRequest: function (query) {
        ++Planning.activeRequestCount;
        new Ajax.Request(Planning.trackerBaseUrl + query, {
            onFailure: Planning.errorOccured,
            onSuccess: function (transport) {
                --Planning.activeRequestCount;
                if (transport.responseJSON && typeof transport.responseJSON.remaining_effort !== undefined) {
                    $$('.release_planner .planning_remaining_effort').each(function (element) {
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
            }
        });
    },

    errorOccured: function (transport) {
        --Planning.activeRequestCount;
        codendi.feedback.log('error', transport.responseText);
    },

    removeItem: function (item, target) {
        var itemId   = parseInt(item.id.match(/art-(\d+)/)[1]);
        var targetId = parseInt(target.id.match(/art-(\d+)/)[1]);
        Planning.unAssociateArtifactTo(itemId, targetId);
    },

    dropItem: function (item, target) {
        var itemId   = parseInt(item.id.match(/art-(\d+)/)[1]);
        var targetId = parseInt(target.id.match(/art-(\d+)/)[1]);
        Planning.associateArtifactTo(itemId, targetId);
    },

    sort: function (item) { with (Planning) {
        var next = $(item).next();
        var prev = $(item).previous();
        if (next) {
            executeSortRequest('higher-priority-than', getArtifactId(item), getArtifactId(next));
        } else if (prev) {
            executeSortRequest('lesser-priority-than', getArtifactId(item), getArtifactId(prev));
        }
    }},

    move_to_plan: function (current_item, milestone) {
        Planning.dropItem(current_item, milestone);
        $(milestone).down('.milestone-noitems').map(Element.hide);
    },

    move_to_backlog: function (current_item, milestone) {
        Planning.removeItem(current_item, milestone);
        if (! $(milestone).down('.milestone-content > ul.cards > li')) {
            $(milestone).down('.milestone-noitems').map(Element.show);
        }
    },

    loadSortables: function (container) {
        (function ($j) {
            var options = {
                placeholder: 'card placeholder',
                scroll: true,
                stop: function (event, ui) { with (Planning) {
                    var current_item = ui.item.get(0);
                    sort(current_item);
                }}
            };
            $j('.backlog-content ul.cards, .milestone-content ul.cards').filter(Planning.doesntHaveDirectChildrenDraggable).sortable(options);

            options.stop = options.stop.wrap(function (wrapped_stop, event, ui) {
                Planning.wrapped_stop(event, ui);
                var current_item    = ui.item.get(0);
                var drop_zone       = ui.item.parents('.planning-droppable').get(0) || ui.item.parents('.planning-backlog').get(0);
                var move_to_plan    = $j(drop_zone).hasClass('planning-droppable') && $j(this).parents('.planning-backlog').get(0);
                var move_to_backlog = $j(drop_zone).hasClass('planning-backlog')   && $j(this).parents('.planning-droppable').get(0);
                if (move_to_backlog) {
                    Planning.move_to_backlog(current_item, $j(this).parents('.planning-droppable').get(0));
                } else if (move_to_plan) {
                    Planning.move_to_plan(current_item, drop_zone);
                }
            });

            options.connectWith = '.milestone-content > ul.cards',
            $j('.backlog-content ul.cards').filter(Planning.hasDirectChildrenDraggable).sortable(options);

            options.connectWith = '.backlog-content > ul.cards';
            $j('.milestone-content ul.cards').filter(Planning.hasDirectChildrenDraggable).sortable(options);
        })(window.jQuery);
    },

    hasDirectChildrenDraggable: function () {
        return window.jQuery(this).children().filter('.planning-draggable').length > 0;
    },

    doesntHaveDirectChildrenDraggable: function () {
        return !Planning.hasDirectChildrenDraggable();
    },

    getArtifactId: function (card) {
        return card.id.match(/art-(\d+)/)[1];
    }
}
