import artifact_modal_module from './tuleap-artifact-modal.js';
import angular from 'angular';
import 'angular-mocks';

describe("TuleapArtifactModalParentService -", function() {
    var ParentService;

    beforeEach(function() {
        angular.mock.module(artifact_modal_module);

        angular.mock.inject(function(_TuleapArtifactModalParentService_) {
            ParentService = _TuleapArtifactModalParentService_;
        });
    });

    describe("canChooseArtifactsParent() -", function() {
        it("Given no parent_tracker object, when I check if I'll be able to choose the artifact's parent, then it will return false", function() {
            var result = ParentService.canChooseArtifactsParent(undefined);

            expect(result).toBeFalsy();
        });

        it("Given a parent_tracker object and no parent_artifact object, when I check if I'll be able to choose the artifact's parent, then it will return true", function() {
            var parent_tracker = { id: 33 };

            var result = ParentService.canChooseArtifactsParent(parent_tracker, undefined);

            expect(result).toBeTruthy();
        });

        it("Given a parent_tracker object and a parent_artifact object and given that the parent_artifact's tracker id is different from the parent_tracker's id, when I check if I'll be able to choose the artifact's parent, then it will return true", function() {
            var parent_tracker = { id: 60 };
            var parent_artifact = {
                artifact: {
                    tracker: {
                        id: 95
                    }
                }
            };

            var result = ParentService.canChooseArtifactsParent(parent_tracker, parent_artifact);

            expect(result).toBeTruthy();
        });

        it("Given a parent_tracker object and a parent_artifact object and given that the parent_artifact's tracker id is the same as the parent_tracker's id, when I check if I'll be able to choose the artifact's parent, then it will return false", function() {
            var parent_tracker = { id: 20 };
            var parent_artifact = {
                artifact: {
                    tracker: {
                        id: 20
                    }
                }
            };

            var result = ParentService.canChooseArtifactsParent(parent_tracker, parent_artifact);

            expect(result).toBeFalsy();
        });

        it("Given a parent_tracker object that is malformed, when I check if I'll be able to choose the artifact's parent, then it will return false", function() {
            var parent_tracker = { cityfolk: 44 };
            var parent_artifact = {
                artifact: {
                    tracker: {
                        id: 79
                    }
                }
            };

            var result = ParentService.canChooseArtifactsParent(parent_tracker, parent_artifact);

            expect(result).toBeFalsy();
        });

        it("Given a parent_tracker object and a parent_artifact object that is malformed, when I check if I'll be able to choose the artifact's parent, then it will return false ", function() {
            var parent_tracker = { id: 22 };
            var parent_artifact = {
                artifact: {
                    isogeny: {
                        goblinesque: 99
                    }
                }
            };

            var result = ParentService.canChooseArtifactsParent(parent_tracker, parent_artifact);

            expect(result).toBeFalsy();
        });
    });
});
