describe("NewTuleapArtifactModalService", function() {
    var NewTuleapArtifactModalService, $modal, $rootScope, $q, TuleapArtifactModalRestService, TuleapArtifactModalModelFactory;

    beforeEach(function() {
        module('tuleap.artifact-modal', function($provide) {
            TuleapArtifactModalRestService = jasmine.createSpyObj("TuleapArtifactModalRestService", [
                "getArtifact",
                "getArtifactsTitles",
                "getTrackerStructure"
            ]);

            TuleapArtifactModalModelFactory = {
                createFromStructure: jasmine.createSpy("createFromStructure").andCallFake(function(artifact_values, structure) {
                    return structure;
                }),
                reorderFieldsInGoodOrder: jasmine.createSpy("reorderFieldsInGoodOrder").andCallFake(function(input) {
                    return input;
                })
            };

            $provide.value('TuleapArtifactModalRestService', TuleapArtifactModalRestService);
            $provide.value('TuleapArtifactModalModelFactory', TuleapArtifactModalModelFactory);
        });

        inject(function(_$modal_, _$q_, _NewTuleapArtifactModalService_, _$rootScope_) {
            $modal = _$modal_;
            $q = _$q_;
            NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
            $rootScope = _$rootScope_;
        });
    });

    describe("initModalModel() -", function() {
        var deferred, tracker_structure;
        beforeEach(function() {
            deferred = $q.defer();
            TuleapArtifactModalRestService.getTrackerStructure.andReturn(deferred.promise);
            spyOn(NewTuleapArtifactModalService, "getParentArtifactsTitle");
            spyOn(NewTuleapArtifactModalService, "getArtifactValues");
        });

        it("Given a tracker id, no artifact id and a color name, when I create the modal's model, then the tracker's structure will be retrieved and a promise will be resolved with the modal's model object", function() {
            tracker_structure = {
                id: 28,
                label: "preinvest",
                parent: null
            };

            var promise = NewTuleapArtifactModalService.initModalModel(28, undefined, "slackerism");
            deferred.resolve(tracker_structure);
            var success = jasmine.createSpy("success");
            promise.then(success);
            $rootScope.$apply();

            expect(TuleapArtifactModalRestService.getTrackerStructure).toHaveBeenCalledWith(28);
            expect(TuleapArtifactModalModelFactory.reorderFieldsInGoodOrder).toHaveBeenCalledWith(tracker_structure, true);
            expect(NewTuleapArtifactModalService.getParentArtifactsTitle).toHaveBeenCalledWith(null, jasmine.any(Object));
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
            NewTuleapArtifactModalService.getArtifactValues.andCallFake(function(a, b, model) {
                model.values = {
                    113: { field_id: 113, value: "onomatomania" }
                };
            });

            var promise = NewTuleapArtifactModalService.initModalModel(93, 250);
            deferred.resolve(tracker_structure);
            var success = jasmine.createSpy("success");
            promise.then(success);
            $rootScope.$apply();

            expect(TuleapArtifactModalRestService.getTrackerStructure).toHaveBeenCalledWith(93);
            expect(TuleapArtifactModalModelFactory.reorderFieldsInGoodOrder).toHaveBeenCalledWith(tracker_structure, false);
            expect(NewTuleapArtifactModalService.getArtifactValues).toHaveBeenCalledWith(250, tracker_structure, jasmine.any(Object));
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
            NewTuleapArtifactModalService.initModalModel(94);
            deferred.resolve(tracker_structure);
            $rootScope.$apply();

            expect(NewTuleapArtifactModalService.getParentArtifactsTitle).toHaveBeenCalledWith({ id: 79 }, jasmine.any(Object));
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
            TuleapArtifactModalRestService.getArtifactsTitles.andReturn(deferred.promise);

            NewTuleapArtifactModalService.getParentArtifactsTitle(parent, model);
            deferred.resolve(artifacts_list);
            $rootScope.$apply();

            expect(model.parent).toEqual({ id: 99 });
            expect(model.parent_artifacts).toEqual(artifacts_list);
        });

        it("Given no parent object (null) and the model to be completed, when I get the parent artifacts' titles, then the model's parent attribute will be null", function() {
            NewTuleapArtifactModalService.getParentArtifactsTitle(null, model);

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
            TuleapArtifactModalModelFactory.createFromStructure.andReturn(values);

            NewTuleapArtifactModalService.getArtifactValues(undefined, structure, model);
            $rootScope.$apply();

            expect(TuleapArtifactModalRestService.getArtifact).not.toHaveBeenCalled();
            expect(TuleapArtifactModalModelFactory.createFromStructure).toHaveBeenCalledWith([], structure);
            expect(model.values).toEqual(values);
        });

        it("given an artifact id, a tracker structure object and the model to be completed, when I get the artifact's values, then the model's values attribute will be filled up", function() {
            var structure = {};
            var values = {
                124 : { field_id: 124, value: "lobing" }
            };
            TuleapArtifactModalModelFactory.createFromStructure.andReturn(values);

            var deferred = $q.defer();
            TuleapArtifactModalRestService.getArtifact.andReturn(deferred.promise);

            NewTuleapArtifactModalService.getArtifactValues(281, structure, model);
            deferred.resolve({
                values: [
                    { field_id: 124, value: "lobing" }
                ]
            });
            $rootScope.$apply();

            expect(TuleapArtifactModalRestService.getArtifact).toHaveBeenCalledWith(281);
            expect(TuleapArtifactModalModelFactory.createFromStructure).toHaveBeenCalledWith([
                { field_id: 124, value: "lobing" }
            ], structure);
            expect(model.values).toEqual(values);
        });
    });
});
