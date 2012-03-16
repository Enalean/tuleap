var success = function() {};
describe("Jasmine", function() {
  it("makes testing JavaScript awesome!", function() {
    expect(sandbox("<div>toto</div>")).toHaveText('toto');
  });
});  

describe("Jasmine-ajax", function() {
  
  var sourceId = 152;
  var targetId = 666;
  //var item = $('<tr class="boxitemalt" id="art-'+sourceId+'"><td><div class="tree-pipe"></div><div class="tree-pipe"></div><div class="tree-last"></div><a class="direct-link-to-artifact" href="/plugins/tracker/?aid=152" title="">bugs #152</a></td><td>PhotoEditor crashes when I open RAW images</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>');
  var item = sandbox('<div id="art-'+ sourceId +'"></div>');
  var target = sandbox('<div id="art-'+targetId+'"></div>');
  beforeEach(function() {
//    jasmine.Ajax.useMock();
//
//    onSuccess = jasmine.createSpy('onSuccess');
//    onFailure = jasmine.createSpy('onFailure');

    console.log(item);
    spyOn(window, 'associateArtifactTo');
    dropItem(item, target);

//    request = mostRecentAjaxRequest();
  });
  it("saves the association", function() {
      expect(associateArtifactTo).toHaveBeenCalledWith(sourceId, targetId)
  });
});