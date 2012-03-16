dropItem = function(item, target) {
    var itemId   = parseInt(item.id.match(/art-(\d+)/)[1]);
    var targetId = parseInt(target.id.match(/art-(\d+)/)[1]);
    associateArtifactTo(itemId, targetId);
}

associateArtifactTo = function(sourceId, targetId) {
    
}