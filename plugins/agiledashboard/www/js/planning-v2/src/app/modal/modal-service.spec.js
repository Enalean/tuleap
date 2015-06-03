describe("ModalService", function() {
    var ModalService, $modal, $rootScope, ModalTuleapFactory, ModalModelFactory, $q;

    beforeEach(function() {
        module('modal', function ($provide) {
            ModalTuleapFactory = jasmine.createSpyObj("ModalTuleapFactory", [
                "getTrackerStructure",
                "reorderFieldsInGoodOrder",
                "getArtifactsTitles"
            ]);

            ModalModelFactory = jasmine.createSpyObj("ModalModelFactory", [
                "createFromStructure"
            ]);

            $provide.value('ModalTuleapFactory', ModalTuleapFactory);
            $provide.value('ModalModelFactory', ModalModelFactory);
        });

        inject(function (_$modal_, _$q_, _ModalService_, _$rootScope_) {
            $modal = _$modal_;
            $q = _$q_;
            ModalService = _ModalService_;
            $rootScope = _$rootScope_;
        });
    });

    describe("initModalModel() -", function() {
        it("Given a tracker id, the tracker's structure will be retrieved and the fields' initial values will be given to the modal", function() {
            var deferred = $q.defer();
            var tracker_structure = {
                id: 28,
                parent: null
            };
            var initial_values = [
                { field_id: 744, value: null },
                { field_id: 585, bind_value_ids: [100] }
            ];
            ModalTuleapFactory.getTrackerStructure.andReturn(deferred.promise);
            ModalModelFactory.createFromStructure.andReturn(initial_values);

            var promise = ModalService.initModalModel(28);
            var success = jasmine.createSpy("success");
            promise.then(success);

            expect(ModalTuleapFactory.getTrackerStructure).toHaveBeenCalledWith(28);
            deferred.resolve(tracker_structure);
            $rootScope.$apply();

            expect(success).toHaveBeenCalled();
            expect(ModalTuleapFactory.reorderFieldsInGoodOrder).toHaveBeenCalledWith(tracker_structure);
            expect(ModalModelFactory.createFromStructure).toHaveBeenCalledWith(tracker_structure);
            var returned = success.calls[0].args[0];
            expect(returned.values).toEqual(initial_values);
            expect(returned.tracker_id).toEqual(28);
        });
    });
});
