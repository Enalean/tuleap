

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
        var r = new Ajax.Request(Planning.trackerBaseUrl + '?func='+ func +'&linked-artifact-id=' + sourceId + '&aid=' + targetId, {
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
        container.select('.planning-backlog').each(function(element) {
            Droppables.add(element, {
                hoverclass: 'planning-backlog-hover',
                onDrop: Planning.removeItem,
                accept: "planning-draggable-alreadyplanned"
            });
        });
        container.select('.planning-droppable').each(function(element) {
            Droppables.add(element, {
                hoverclass: 'planning-droppable-hover',
                onDrop: Planning.dropItem,
                accept: "planning-draggable-toplan"
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
