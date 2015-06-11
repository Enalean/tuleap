describe("ModalInstanceCtrl", function() {
    var $scope, $q, $controller, controller_params, ModalInstanceCtrl, $modalInstance,
        ModalTuleapFactory, ModalModelFactory, ModalValidateFactory, mockCallback;
    beforeEach(function() {
        module('modal');

        inject(function(_$controller_, $rootScope, _$q_) {
            $q = _$q_;
            ModalTuleapFactory = jasmine.createSpyObj("ModalTuleapFactory", [
                "createArtifact",
                "editArtifact",
                "getArtifactsTitles",
                "getTrackerStructure",
                "reorderFieldsInGoodOrder"
            ]);
            ModalModelFactory = jasmine.createSpyObj("ModalModelFactory", [
                "createFromStructure"
            ]);
            ModalValidateFactory = jasmine.createSpyObj("ModalValidateFactory", [
                "validateArtifactFieldsValues"
            ]);
            $modalInstance = jasmine.createSpyObj("$modalInstance", [
                "close",
                "dismiss"
            ]);
            mockCallback = jasmine.createSpy("displayItemCallback");
            $modalInstance.opened = $q.defer().promise;
            $modalInstance.result = $q.defer().promise;
            $scope = $rootScope.$new();

            $controller = _$controller_;
            controller_params = {
                $modalInstance: $modalInstance,
                modal_model: {},
                ModalTuleapFactory: ModalTuleapFactory,
                ModalModelFactory: ModalModelFactory,
                ModalValidateFactory: ModalValidateFactory,
                displayItemCallback: mockCallback
            };
        });
    });

    describe("submit() - Given a tracker id, field values, a callback function", function() {
        var deferred;
        beforeEach(function() {
            ModalValidateFactory.validateArtifactFieldsValues.andCallFake(function(values) {
                return values;
            });
            deferred = $q.defer();
            ModalTuleapFactory.createArtifact.andReturn(deferred.promise);
            ModalTuleapFactory.editArtifact.andReturn(deferred.promise);
        });

        it("and no artifact_id, when I submit the modal to Tuleap, then the field values will be validated, the artifact will be created , the modal will be closed and the callback will be called", function() {
            controller_params.modal_model.creation_mode = true;
            controller_params.modal_model.tracker_id = 39;
            ModalInstanceCtrl = $controller('ModalInstanceCtrl', controller_params);
            var values = [
                { field_id: 359, value: 907},
                { field_id: 613, bind_value_ids: [919]}
            ];
            ModalInstanceCtrl.values = values;

            ModalInstanceCtrl.submit();
            // The request worked
            deferred.resolve({ id: 3042 });
            $scope.$apply();

            expect(ModalValidateFactory.validateArtifactFieldsValues).toHaveBeenCalledWith(values, true);
            expect(ModalTuleapFactory.createArtifact).toHaveBeenCalledWith(39, values);
            expect(ModalTuleapFactory.editArtifact).not.toHaveBeenCalled();
            expect($modalInstance.close).toHaveBeenCalled();
            expect(mockCallback).toHaveBeenCalled();
        });

        it("and an artifact_id to edit, when I submit the modal to Tuleap, then the field values will be validated, the artifact will be edited, the modal will be closed and the callback will be called", function() {
            controller_params.modal_model.creation_mode = false;
            controller_params.modal_model.artifact_id = 8155;
            controller_params.modal_model.tracker_id = 186;
            ModalInstanceCtrl = $controller('ModalInstanceCtrl', controller_params);
            var values = [
                { field_id: 983, value: 741},
                { field_id: 860, bind_value_ids: [754]}
            ];
            ModalInstanceCtrl.values = values;

            ModalInstanceCtrl.submit();
            // The request worked
            deferred.resolve({ id: 8155 });
            $scope.$apply();

            expect(ModalValidateFactory.validateArtifactFieldsValues).toHaveBeenCalledWith(values, false);
            expect(ModalTuleapFactory.editArtifact).toHaveBeenCalledWith(8155, values);
            expect(ModalTuleapFactory.createArtifact).not.toHaveBeenCalled();
            expect($modalInstance.close).toHaveBeenCalled();
            expect(mockCallback).toHaveBeenCalledWith(8155);
        });

        it("and given the server responded an error, when I submit the modal to Tuleap, then the modal will not be closed and the callback won't be called", function() {
            ModalInstanceCtrl.submit();
            deferred.reject();
            $scope.$apply();

            expect($modalInstance.close).not.toHaveBeenCalled();
            expect(mockCallback).not.toHaveBeenCalled();
        });
    });
});
