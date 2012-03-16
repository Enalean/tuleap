

var Planning = { } ;

Planning.reload = function() {
    window.location.reload();
}

Planning.trackerBaseUrl = '/plugins/tracker/';

Planning.associateArtifactTo = function(sourceId, targetId) {
    var r = new Ajax.Request(Planning.trackerBaseUrl + '?action=associate-artifact-to&item=' + sourceId + '&target=' + targetId, {
        onSuccess: Planning.reload
    });
}

Planning.dropItem = function(item, target) {
    var itemId   = parseInt(item.id.match(/art-(\d+)/)[1]);
    var targetId = parseInt(target.id.match(/art-(\d+)/)[1]);
    Planning.associateArtifactTo(itemId, targetId);
}

Planning.loadDroppables = function(container) {
    container.select('.planning-droppable').each(function(element) {
        Droppables.add(element, {
            hoverclass: 'planning-droppable-hover',
            onDrop: Planning.dropItem,
            accept: "planning-draggable"
        });
    });
}

Planning.loadDraggables = function(container) {
    container.select('.planning-draggable').each(function(element) {
        new Draggable(element, {
            revert: 'failure'
        });
    })
}
