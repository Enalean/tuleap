

var Planning = {

    trackerBaseUrl: '/plugins/tracker/',
    
    reload: function() {
        window.location.reload();
    },

    associateArtifactTo: function (sourceId, targetId) {
        var r = new Ajax.Request(Planning.trackerBaseUrl + '?func=associate-artifact-to&linked-artifact-id=' + sourceId + '&aid=' + targetId, {
            onSuccess: Planning.reload,
            onFailure: Planning.errorOccured
        });
    },

    errorOccured: function (transport) {
        codendi.feedback.log('error', transport.responseText);
    },

    dropItem: function (item, target) {
        var itemId   = parseInt(item.id.match(/art-(\d+)/)[1]);
        var targetId = parseInt(target.id.match(/art-(\d+)/)[1]);
        Planning.associateArtifactTo(itemId, targetId);
    },

    loadDroppables: function (container) {
        container.select('.planning-droppable').each(function(element) {
            Droppables.add(element, {
                hoverclass: 'planning-droppable-hover',
                onDrop: Planning.dropItem,
                accept: "planning-draggable"
            });
        });
    },

    loadDraggables: function (container) {
        container.select('.planning-draggable').each(function(element) {
            new Draggable(element, {
                revert: 'failure'
            });
        })
    }

};
