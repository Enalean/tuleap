

var Planning = {

    trackerBaseUrl: '/plugins/tracker/',

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
        new Ajax.Request(Planning.trackerBaseUrl + query, {
            onFailure: Planning.errorOccured
        });
    },

    errorOccured: function (transport) {
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
            $j('.backlog-content > ul.cards ul.cards, .milestone-content > ul.cards ul.cards').sortable(options);

            options.stop = options.stop.wrap(function (wrapped_stop, event, ui) { with (Planning) {
                var current_item    = ui.item.get(0);
                var drop_zone       = ui.item.parents('.planning-droppable').get(0) || ui.item.parents('.planning-backlog').get(0);
                var move_to_plan    = $j(drop_zone).hasClass('planning-droppable');
                var move_to_backlog = $j(drop_zone).hasClass('planning-backlog');
                if (move_to_backlog) {
                    removeItem(current_item, $j(this).parents('.planning-droppable').get(0));
                    if ($j(this).children().length == 0) {
                        $j('.milestone-noitems').show();
                    }
                } else if (move_to_plan) {
                    dropItem(current_item, drop_zone);
                    $j('.milestone-noitems').hide();
                }
                wrapped_stop(event, ui);
            }});

            options.connectWith = '.milestone-content > ul.cards',
            $j('.backlog-content > ul.cards').sortable(options);

            options.connectWith = '.backlog-content > ul.cards';
            $j('.milestone-content > ul.cards').sortable(options);
        })(window.jQuery);
    },

    getArtifactId: function (card) {
        return card.id.match(/art-(\d+)/)[1];
    }
}
