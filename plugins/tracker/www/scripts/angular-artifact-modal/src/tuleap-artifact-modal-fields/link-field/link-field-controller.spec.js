import link_module from './link-field.js';
import angular     from 'angular';
import 'angular-mocks';
import BaseController from './link-field-controller.js';
import {
    rewire$canChooseArtifactsParent,
    restore
} from './link-field-service.js';

describe("LinkFieldController -", () => {
    let $controller,
        LinkFieldController,
        TuleapArtifactModalRestService,
        canChooseArtifactsParent;

    beforeEach(() => {
        angular.mock.module(link_module);
        angular.mock.inject(function(
            _$controller_,
            _TuleapArtifactModalRestService_,
        ) {
            $controller                    = _$controller_;
            TuleapArtifactModalRestService = _TuleapArtifactModalRestService_;
        });

        canChooseArtifactsParent = jasmine.createSpy("canChooseArtifactsParent");
        rewire$canChooseArtifactsParent(canChooseArtifactsParent);

        LinkFieldController = $controller(BaseController, {
            TuleapArtifactModalRestService
        });
    });

    afterEach(() => {
        restore();
    });

    describe("showParentArtifactChoice() -", () => {
        let tracker,
            linked_artifact,
            possible_parent_artifacts;

        beforeEach(() => {
            canChooseArtifactsParent.and.returnValue(true);
            tracker = {
                id    : 43,
                parent: {
                    id: 64
                }
            };
            linked_artifact = {
                id: 154
            };
            possible_parent_artifacts = [
                { id: 629 }
            ];
        });

        it("Given that I can choose a parent artifact and given the list of possible parent artifacts wasn't empty, when I check if I show the parent artifact choice, then it will return true", () => {
            Object.assign(LinkFieldController, {
                tracker,
                linked_artifact,
                possible_parent_artifacts
            });

            const result = LinkFieldController.showParentArtifactChoice();

            expect(canChooseArtifactsParent).toHaveBeenCalledWith(
                tracker,
                linked_artifact
            );
            expect(result).toBeTruthy();
        });

        it("Given that the list of possible parent artifacts was empty, when I check if I show the parent artifact choice, then it will return false", () => {
            possible_parent_artifacts = [];
            Object.assign(LinkFieldController, {
                tracker,
                linked_artifact,
                possible_parent_artifacts
            });

            const result = LinkFieldController.showParentArtifactChoice();

            expect(result).toBeFalsy();
        });

        it("Given that I cannot choose a parent artifact, when I check if I show the parent artifact choice, then it will return false", () => {
            canChooseArtifactsParent.and.returnValue(false);
            Object.assign(LinkFieldController, {
                tracker,
                linked_artifact,
                possible_parent_artifacts
            });

            const result = LinkFieldController.showParentArtifactChoice();

            expect(result).toBeFalsy();
        });
    });

    describe("formatArtifact() -", () => {
        it("Given a parent artifact, when I format the title of the artifact, then the tracker's label, the artifact's id and its title will be concatenated and returned", () => {
            const artifact = {
                title  : "forcipated",
                id     : 747,
                uri    : "artifacts/747",
                tracker: {
                    id   : 47,
                    uri  : "trackers/47",
                    label: "flareboard"
                }
            };

            const result = LinkFieldController.formatArtifact(artifact);

            expect(result).toEqual("flareboard #747 - forcipated");
        });
    });
});
