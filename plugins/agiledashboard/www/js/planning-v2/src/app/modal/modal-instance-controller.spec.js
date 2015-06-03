describe("ModalInstanceCtrl", function() {
    var $scope, $q, $controller, controller_params, ModalInstanceCtrl, $modalInstance,
        ModalTuleapFactory, ModalModelFactory, ModalValidateFactory, opened, mockCallback;
    beforeEach(function() {
        module('modal');

        inject(function (_$controller_, $rootScope, _$q_) {
            $q = _$q_;
            ModalTuleapFactory = jasmine.createSpyObj("ModalTuleapFactory", [
                "getTrackerStructure",
                "reorderFieldsInGoodOrder",
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
                modal_model: {},
                ModalTuleapFactory: ModalTuleapFactory,
                ModalModelFactory: ModalModelFactory,
                ModalValidateFactory: ModalValidateFactory,
                displayItemCallback: mockCallback
            };
        });
    });

    describe("createArtifact() - Given a tracker id, field values and a callback function,", function() {
        var deferred;
        beforeEach(function() {
            controller_params.modal_model.tracker_id = 39;
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
