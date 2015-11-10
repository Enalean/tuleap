describe('BacklogItemFactory', function() {

    beforeEach(module('backlog-item'));

    describe('augment', function() {
        var item = {
                "artifact" : {
                    "tracker" : {
                        "id" : 78
                    }
                },
                "accept" : {
                    "trackers" : [
                        {"id" : 123},
                        {"id" : 895}
                    ]
                },
                "status" : "Open"
            };

        beforeEach(inject(function(BacklogItemFactory) {
            spyOn(BacklogItemFactory, 'augment').and.callThrough();

            BacklogItemFactory.augment(item);
        }));

        it('adds allowed tracker types to backlog item', function() {
            expect(item.accepted_types.toString()).toEqual('trackerId123|trackerId895');
            expect(item.trackerId).toEqual('trackerId78');
        });

        it('adds children properties', function() {
            var expected = {
                 "data"   : [],
                 "loaded" : false
            };

            expect(item.children).toEqual(expected);
        });

        it('has method isOpen', function() {
            expect(item.isOpen()).toEqual(true);
        });
    });
});
