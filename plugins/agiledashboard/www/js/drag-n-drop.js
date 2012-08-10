

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

    /**
     * resetArrows(elem1, elem2, elem3);
     */
    resetArrows: function (li) {
        $A(arguments).each(function (li) {
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
        });
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
        Planning.resetArrows.apply(this, $(item).up().childElements());
    }},

    move_to_plan: function (current_item, milestone) {
        Planning.dropItem(current_item, milestone);
        $(milestone).down('.milestone-noitems').hide();
    },

    move_to_backlog: function (current_item, milestone) {
        Planning.removeItem(current_item, milestone);
        if (! $(milestone).down('.milestone-content > ul.cards > li')) {
            $(milestone).down('.milestone-noitems').show();
        }
    },

    loadSortables: function (container) {
        (function ($j) {
            var options = {
                placeholder: 'card placeholder',
                scroll: true,
                stop: function (event, ui) {
                    var current_item = ui.item.get(0);
                    Planning.sort(current_item);
                }
            };
            $j('.backlog-content ul.cards, .milestone-content ul.cards').sortable(options);
        })(window.jQuery);
    },

    getArtifactId: function (card) {
        return card.id.match(/art-(\d+)/)[1];
    }
}
