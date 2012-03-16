dropItem = function(item, target) {
    var itemId   = parseInt(item.id.match(/art-(\d+)/)[1]);
    var targetId = parseInt(target.id.match(/art-(\d+)/)[1]);
    associateArtifactTo(itemId, targetId);
}
var trackerBaseUrl = '/plugins/tracker/';
associateArtifactTo = function(sourceId, targetId) {
    var r = new Ajax.Request(trackerBaseUrl + '?action=associate-artifact-to&item=' + sourceId + '&target=' + targetId, {
        onSuccess: refresh
    });
}

refresh = function() {
    
}