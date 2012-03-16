var trackerBaseUrl = '/plugins/tracker/';

dropItem = function(item, target) {
    var itemId   = parseInt(item.id.match(/art-(\d+)/)[1]);
    var targetId = parseInt(target.id.match(/art-(\d+)/)[1]);
    associateArtifactTo(itemId, targetId);
}
associateArtifactTo = function(sourceId, targetId) {
    var r = new Ajax.Request(trackerBaseUrl + '?action=associate-artifact-to&item=' + sourceId + '&target=' + targetId, {
        onSuccess: refresh
    });
}

refresh = function() {
    window.location.href = window.location.href;
}

loadDroppables = function(container) {
    container.select('.planning-droppable').each(function(element) {
        Droppables.add(element, {
            hoverclass: 'planning-droppable-hover',
            onDrop: dropItem,
            accept: "planning-draggable"
        });
    });
}

loadDraggables = function(container) {
    container.select('.planning-draggable').each(function(element) {
        new Draggable(element, {
            revert: 'failure'
        });
    })
}