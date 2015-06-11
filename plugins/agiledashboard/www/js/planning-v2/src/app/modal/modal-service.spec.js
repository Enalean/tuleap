describe("ModalService", function() {
    var ModalService, $modal, $rootScope, $q, ModalTuleapFactory, ModalModelFactory;

    beforeEach(function() {
        module('modal', function($provide) {
            ModalTuleapFactory = jasmine.createSpyObj("ModalTuleapFactory", [
                "getArtifact",
                "getArtifactsTitles",
                "getTrackerStructure"
            ]);

            ModalModelFactory = {
                createFromStructure: jasmine.createSpy("createFromStructure").andCallFake(function(artifact_values, structure) {
                    return structure;
                }),
                reorderFieldsInGoodOrder: jasmine.createSpy("reorderFieldsInGoodOrder").andCallFake(function(input) {
                    return input;
                })
            };

            $provide.value('ModalTuleapFactory', ModalTuleapFactory);
            $provide.value('ModalModelFactory', ModalModelFactory);
        });

        inject(function(_$modal_, _$q_, _ModalService_, _$rootScope_) {
            $modal = _$modal_;
            $q = _$q_;
            ModalService = _ModalService_;
            $rootScope = _$rootScope_;
        });
    });

    describe("initModalModel() -", function() {
        var deferred, tracker_structure;
        beforeEach(function() {
            deferred = $q.defer();
            ModalTuleapFactory.getTrackerStructure.andReturn(deferred.promise);
            spyOn(ModalService, "getParentArtifactsTitle");
            spyOn(ModalService, "getArtifactValues");
        });

        it("Given a tracker id, no artifact id and a color name, when I create the modal's model, then the tracker's structure will be retrieved and a promise will be resolved with the modal's model object", function() {
            tracker_structure = {
                id: 28,
                label: "preinvest",
                parent: null
            };

            var promise = ModalService.initModalModel(28, undefined, "slackerism");
            deferred.resolve(tracker_structure);
            var success = jasmine.createSpy("success");
            promise.then(success);
            $rootScope.$apply();

            expect(ModalTuleapFactory.getTrackerStructure).toHaveBeenCalledWith(28);
            expect(ModalModelFactory.reorderFieldsInGoodOrder).toHaveBeenCalledWith(tracker_structure, true);
            expect(ModalService.getParentArtifactsTitle).toHaveBeenCalledWith(null, jasmine.any(Object));
            expect(success).toHaveBeenCalled();
            var model = success.calls[0].args[0];
            expect(model.tracker_id).toEqual(28);
            expect(model.artifact_id).toBeUndefined();
            expect(model.color).toEqual("slackerism");
            expect(model.structure).toEqual(tracker_structure);
            expect(model.ordered_fields).toBeDefined();
            expect(model.creation_mode).toBeTruthy();
            expect(model.title).toEqual("preinvest");
        });

        it("Given a tracker id, an artifact id, ant no color name, when I create the modal's model, then the artifact's field values will be retrieved, the tracker's structure will be retrieved and a promise will be resolved with the modal's model object", function() {
            tracker_structure = {
                id: 93,
                label: "unstainableness",
                parent: null,
                semantics: {
                    title: {
                        field_id: 113
                    }
                },
                fields: [
                    { field_id: 113, value: "onomatomania" }
                ]
            };
            ModalService.getArtifactValues.andCallFake(function(a, b, model) {
                model.values = {
                    113: { field_id: 113, value: "onomatomania" }
                };
            });

            var promise = ModalService.initModalModel(93, 250);
            deferred.resolve(tracker_structure);
            var success = jasmine.createSpy("success");
            promise.then(success);
            $rootScope.$apply();

            expect(ModalTuleapFactory.getTrackerStructure).toHaveBeenCalledWith(93);
            expect(ModalModelFactory.reorderFieldsInGoodOrder).toHaveBeenCalledWith(tracker_structure, false);
            expect(ModalService.getArtifactValues).toHaveBeenCalledWith(250, tracker_structure, jasmine.any(Object));
            expect(success).toHaveBeenCalled();
            var model = success.calls[0].args[0];
            expect(model.tracker_id).toEqual(93);
            expect(model.artifact_id).toEqual(250);
            expect(model.color).toBeUndefined();
            expect(model.structure).toEqual(tracker_structure);
            expect(model.ordered_fields).toBeDefined();
            expect(model.creation_mode).toBeFalsy();
            expect(model.title).toEqual("onomatomania");
        });

        it("Given a tracker that had a parent and given its id, when I create the modal's model, then the tracker's parent's structure will be retrieved and and a promise will be resolved with the modal's model object", function() {
            tracker_structure = {
                id: 94,
                parent: {
                    id: 79
                }
            };
            ModalService.initModalModel(94);
            deferred.resolve(tracker_structure);
            $rootScope.$apply();

            expect(ModalService.getParentArtifactsTitle).toHaveBeenCalledWith({ id: 79 }, jasmine.any(Object));
        });
    });

    describe("getParentArtifactsTitle() -", function() {
        var model;
        beforeEach(function() {
            model = {};
        });

        it("Given a parent object and the model to be completed, when I get the parent artifacts' titles, then the model's parent and parent_artifacts attributes will be filled up", function() {
            var parent = { id: 99 };
            var artifacts_list = [
                { id: 968, title: "Cucurbita" },
                { id: 129, title: "acatharsia" }
            ];
            var deferred = $q.defer();
            ModalTuleapFactory.getArtifactsTitles.andReturn(deferred.promise);

            ModalService.getParentArtifactsTitle(parent, model);
            deferred.resolve(artifacts_list);
            $rootScope.$apply();

            expect(model.parent).toEqual({ id: 99 });
            expect(model.parent_artifacts).toEqual(artifacts_list);
        });

        it("Given no parent object (null) and the model to be completed, when I get the parent artifacts' titles, then the model's parent attribute will be null", function() {
            ModalService.getParentArtifactsTitle(null, model);

            expect(model.parent).toEqual(null);
            expect(model.parent_artifacts).toBeUndefined();
        });
    });

    describe("getArtifactValues() -", function() {
        var model;
        beforeEach(function() {
            model = {};
        });

        it("given no artifact id, a tracker structure object and the model to be completed, when I get the artifact's values, then the model's values attribute will be filled up only from the tracker structure", function() {
            var structure = {};
            var values = {
                221 : { field_id: 221, value: "motherlike" }
            };
            ModalModelFactory.createFromStructure.andReturn(values);

            ModalService.getArtifactValues(undefined, structure, model);
            $rootScope.$apply();

            expect(ModalTuleapFactory.getArtifact).not.toHaveBeenCalled();
            expect(ModalModelFactory.createFromStructure).toHaveBeenCalledWith([], structure);
            expect(model.values).toEqual(values);
        });

        it("given an artifact id, a tracker structure object and the model to be completed, when I get the artifact's values, then the model's values attribute will be filled up", function() {
            var structure = {};
            var values = {
                124 : { field_id: 124, value: "lobing" }
            };
            ModalModelFactory.createFromStructure.andReturn(values);

            var deferred = $q.defer();
            ModalTuleapFactory.getArtifact.andReturn(deferred.promise);

            ModalService.getArtifactValues(281, structure, model);
            deferred.resolve({
                values: [
                    { field_id: 124, value: "lobing" }
                ]
            });
            $rootScope.$apply();

            expect(ModalTuleapFactory.getArtifact).toHaveBeenCalledWith(281);
            expect(ModalModelFactory.createFromStructure).toHaveBeenCalledWith([
                { field_id: 124, value: "lobing" }
            ], structure);
            expect(model.values).toEqual(values);
        });
    });
});
