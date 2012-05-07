

var Planning = {

    trackerBaseUrl: '/plugins/tracker/',
    
    reload: function() {
        window.location.reload();
    },

    unAssociateArtifactTo: function (sourceId, targetId) {
        Planning.executeRequest('unassociate-artifact-to', sourceId, targetId);
    },

    associateArtifactTo: function (sourceId, targetId) {
        Planning.executeRequest('associate-artifact-to', sourceId, targetId);
    },

    executeRequest: function (func, sourceId, targetId) {
        new Ajax.Request(Planning.trackerBaseUrl + '?func='+ func +'&linked-artifact-id=' + sourceId + '&aid=' + targetId, {
            onSuccess: Planning.reload,
            onFailure: Planning.errorOccured
        });
    },

    errorOccured: function (transport) {
        codendi.feedback.log('error', transport.responseText);
    },

    removeItem: function (item, target) {
        var itemId   = parseInt(item.id.match(/art-(\d+)/)[1]);
        var targetId = parseInt(item.up('.planning-droppable').id.match(/art-(\d+)/)[1]);
        Planning.unAssociateArtifactTo(itemId, targetId);
    },

    dropItem: function (item, target) {
        var itemId   = parseInt(item.id.match(/art-(\d+)/)[1]);
        var targetId = parseInt(target.id.match(/art-(\d+)/)[1]);
        Planning.associateArtifactTo(itemId, targetId);
    },

    loadDroppables: function (container) {
        (function ($j) {
            $j(container).find('.planning-backlog').droppable({
                hoverClass: 'planning-backlog-hover',
                accept: '.planning-draggable-alreadyplanned',
                drop: function (event, ui) {
                    Planning.removeItem(ui.draggable.get(0), $j(this).get(0));
                }
            });
            $j(container).find('.planning-droppable').droppable({
                hoverClass: 'planning-droppable-hover',
                accept: '.planning-draggable-toplan',
                drop: function (event, ui) {
                    Planning.dropItem(ui.draggable.get(0), $j(this).get(0));
                }
            });
        })(window.jQuery);
    },

    loadDraggables: function (container) {
        (function ($j) {
            $j(container).find('.planning-draggable').draggable({
                revert: 'invalid',
                cursor: 'move'
            });
        })(window.jQuery);
    }

};
