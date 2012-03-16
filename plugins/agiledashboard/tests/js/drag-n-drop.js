
describe("associateArtifactTo", function () { 
    
    var sourceId = 152;
    var targetId = 666;
    var request, refresh;
    var TestResponses = {
        drop: {
            success: {
                status: 200,
                responseText: ''
            },
            failure: {
                status: 400
            }
        }
    };

    beforeEach(function() {
        refresh = spyOn(window, 'refresh');
        jasmine.Ajax.useMock();

        associateArtifactTo(sourceId, targetId);
        request = mostRecentAjaxRequest();
    });

    it("refreshes the page after successfully saving", function() {
        request.response(TestResponses.drop.success);
        expect(refresh).toHaveBeenCalled();
    });

    it("does nothing if the save was unsuccessful", function() {
        request.response(TestResponses.drop.failure);
        expect(refresh).not.toHaveBeenCalled();
    });
    
    it("tells the server to associate the first item to the second", function() {
        request.response(TestResponses.drop.success);
        expect(request.url).toContain(trackerBaseUrl+'?action=associate-artifact-to');
        expect(request.url).toContain('item=152');
        expect(request.url).toContain('target=666');
        
    });
});

describe("dropItem", function() {
  
    var sourceId = 152;
    var targetId = 666;
    //var item = $('<tr class="boxitemalt" id="art-'+sourceId+'"><td><div class="tree-pipe"></div><div class="tree-pipe"></div><div class="tree-last"></div><a class="direct-link-to-artifact" href="/plugins/tracker/?aid=152" title="">bugs #152</a></td><td>PhotoEditor crashes when I open RAW images</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>');
    var item   = sandbox('<div id="art-'+ sourceId +'"></div>');
    var target = sandbox('<div id="art-'+targetId+'"></div>');
        
    it("saves the association between source and target", function() {
        spyOn(window, 'associateArtifactTo');
        dropItem(item, target);
        expect(associateArtifactTo).toHaveBeenCalledWith(sourceId, targetId)
    });


});

describe("loadDroppables", function() {
    it ("instantiate a droppable for each target elements", function() {
        var element = '<div class="planning-droppable" id="art-666"></div>';
        var dom = sandbox('<div>'+element+'<div class="other useless elements"></div></div>');
        
        expect(Droppables.drops.length).toEqual(0);
        loadDroppables(dom);
        
        expect(Droppables.drops.length).toEqual(1);
        expect(Droppables.drops[0].hoverclass).toEqual('planning-droppable-hover');
        expect(Droppables.drops[0].onDrop).toEqual(dropItem);
        expect(Droppables.drops[0].accept).toContain("planning-draggable");
    });
});

describe("loadDraggables", function() {
    it ("instantiate a draggable for each source element", function() {
        var element = '<div class="planning-draggable" id="art-666"></div>';
        var dom = sandbox('<div>'+element+'<div class="other useless elements"></div></div>');
        expect(Draggables.drags.length).toEqual(0);
        
        loadDraggables(dom);

        expect(Draggables.drags.length).toEqual(1);
        expect(Draggables.drags[0].options.revert).toEqual('failure');
    });
});
