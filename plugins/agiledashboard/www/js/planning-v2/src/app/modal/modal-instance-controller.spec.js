describe("ModalInstanceCtrl", function() {
    var $scope, $q, $controller, controller_params, ModalInstanceCtrl, $modalInstance,
        ModalTuleapFactory, ModalModelFactory, ModalValidateFactory, opened, mockCallback;
    beforeEach(function() {
        module('modal');

        inject(function (_$controller_, $rootScope, _$q_) {
            $q = _$q_;
            ModalTuleapFactory = jasmine.createSpyObj("ModalTuleapFactory", [
                "getTrackerStructure",
                "getArtifactsTitles",
                "createArtifact"
            ]);
            ModalModelFactory = jasmine.createSpyObj("ModalModelFactory", [
                "createFromStructure"
            ]);
            ModalValidateFactory = jasmine.createSpyObj("ModalValidateFactory", [
                "validateArtifactFieldsValues"
            ]);
            $modalInstance = jasmine.createSpyObj("$modalInstance", [
                "close"
            ]);
            mockCallback = jasmine.createSpy("displayItemCallback");
            opened = $q.defer();
            $modalInstance.opened = opened.promise;
            $scope = $rootScope.$new();

            $controller = _$controller_;
            controller_params = {
                $modalInstance: $modalInstance,
                ModalTuleapFactory: ModalTuleapFactory,
                ModalModelFactory: ModalModelFactory,
                ModalValidateFactory: ModalValidateFactory,
                displayItemCallback: mockCallback
            };
        });
    });

    describe("activate() -", function() {
        it("Given a tracker id, when the controller is created, then the tracker's structure will be retrieved and the fields' initial values will be set in the scope", function() {
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

            controller_params.tracker_id = 35;
            ModalInstanceCtrl = $controller('ModalInstanceCtrl', controller_params);
            deferred.resolve(tracker_structure);
            opened.resolve();
            $scope.$apply();

            expect(ModalTuleapFactory.getTrackerStructure).toHaveBeenCalledWith(35);
            expect(ModalInstanceCtrl.structure).toEqual(tracker_structure);
            expect(ModalModelFactory.createFromStructure).toHaveBeenCalledWith(tracker_structure);
            expect(ModalInstanceCtrl.values).toEqual(initial_values);
        });

        it("Given a tracker that had a parent and given its id, when the controller is created, then the tracker's parent's structure will be retrieved and the parent artifacts list will be set in the scope", function() {
            var first_deferred = $q.defer();
            var second_deferred = $q.defer();
            var tracker_structure = {
                id: 5,
                parent: {
                    id: 79
                }
            };
            var artifacts_list = [
                { id: 75, title: "Bombinae" },
                { id: 395, title: "vergerism" }
            ];
            ModalTuleapFactory.getTrackerStructure.andReturn(first_deferred.promise);
            ModalTuleapFactory.getArtifactsTitles.andReturn(second_deferred.promise);

            controller_params.tracker_id = 94;
            ModalInstanceCtrl = $controller('ModalInstanceCtrl', controller_params);
            first_deferred.resolve(tracker_structure);
            second_deferred.resolve(artifacts_list);
            opened.resolve();
            $scope.$apply();

            expect(ModalTuleapFactory.getArtifactsTitles).toHaveBeenCalledWith(79);
            expect(ModalInstanceCtrl.parent_artifacts).toEqual(artifacts_list);
        });
    });

    describe("createArtifact() - Given a tracker id, field values and a callback function,", function() {
        var deferred;
        beforeEach(function() {
            controller_params.tracker_id = 39;
            ModalInstanceCtrl = $controller('ModalInstanceCtrl', controller_params);
            deferred = $q.defer();
            ModalValidateFactory.validateArtifactFieldsValues.andCallFake(function (values) {
                return values;
            });
            ModalTuleapFactory.createArtifact.andReturn(deferred.promise);
        });

        it("when I create an artifact, then the field values will be validated using the dedicated factory, the Tuleap factory will be called with the tracker_id and the validated field values, the modal will be closed and the callback will be called with the new artifact's id", function() {
            var values = [
                { field_id: 359, value: 907},
                { field_id: 613, bind_value_ids: [919]}
            ];
            ModalInstanceCtrl.values = values;

            ModalInstanceCtrl.createArtifact();
            // The request worked
            deferred.resolve({ id: 3042 });
            $scope.$apply();

            expect(ModalValidateFactory.validateArtifactFieldsValues).toHaveBeenCalledWith(values);
            expect(ModalTuleapFactory.createArtifact).toHaveBeenCalledWith(39, values);
            expect($modalInstance.close).toHaveBeenCalled();
            expect(mockCallback).toHaveBeenCalledWith(3042);
        });

        it("and given the server responded an error, when I create an artifact, then the modal will not be closed and the callback won't be called", function() {
            ModalInstanceCtrl.createArtifact();
            deferred.reject();
            $scope.$apply();

            expect($modalInstance.close).not.toHaveBeenCalled();
            expect(mockCallback).not.toHaveBeenCalled();
        });
    });
});
