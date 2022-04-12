import tuleap_artifact_modal_module from "../../tuleap-artifact-modal.js";
import angular from "angular";
import "angular-mocks";
import BaseController from "./link-field-controller.js";
import * as link_field_service from "./link-field-service.js";
import * as modal_creation_mode_state from "../../modal-creation-mode-state.js";
import * as rest_service from "../../rest/rest-service";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator";
import * as list_picker_lib from "@tuleap/list-picker";

jest.mock("@tuleap/list-picker");

describe("LinkFieldController -", () => {
    let $controller,
        $q,
        $rootScope,
        $scope,
        $element,
        gettextCatalog,
        LinkFieldController,
        canChooseArtifactsParent,
        isInCreationMode;

    beforeEach(() => {
        angular.mock.module(tuleap_artifact_modal_module);
        angular.mock.inject(function (_$controller_, _$q_, _$rootScope_, _gettextCatalog_) {
            $controller = _$controller_;
            $q = _$q_;
            $rootScope = _$rootScope_;
            $scope = $rootScope.$new();
            gettextCatalog = _gettextCatalog_;
        });

        $element = angular.element('<div><select data-select-type="list-picker"</div>');

        canChooseArtifactsParent = jest.spyOn(link_field_service, "canChooseArtifactsParent");

        isInCreationMode = jest
            .spyOn(modal_creation_mode_state, "isInCreationMode")
            .mockReturnValue(true);

        LinkFieldController = $controller(BaseController, {
            $element,
            $scope,
            gettextCatalog,
        });
    });

    describe("init() -", () => {
        beforeEach(() => {
            jest.spyOn(LinkFieldController, "loadParentArtifactsTitle").mockImplementation(
                () => {}
            );
            jest.spyOn(LinkFieldController, "hasArtifactAlreadyAParent").mockImplementation(
                () => {}
            );
        });

        it("Given the modal was in creation mode and given I can choose a parent, then the list of possible parents will be loaded", () => {
            canChooseArtifactsParent.mockReturnValue(true);
            isInCreationMode.mockReturnValue(true);

            LinkFieldController.$onInit();
            $rootScope.$apply();

            expect(LinkFieldController.loadParentArtifactsTitle).toHaveBeenCalled();
        });

        it("Given the modal was in creation mode and given a parent artifact id, then the parent artifact will be loaded and the list of possible parents won't be loaded", () => {
            const parent_artifact_id = 74;
            LinkFieldController.parent_artifact_id = parent_artifact_id;
            canChooseArtifactsParent.mockReturnValue(false);
            isInCreationMode.mockReturnValue(true);
            const artifact = { id: parent_artifact_id, title: "Julietta" };
            const getArtifact = jest
                .spyOn(rest_service, "getArtifact")
                .mockImplementation(() => $q.when(artifact));

            LinkFieldController.$onInit();
            $rootScope.$apply();

            expect(LinkFieldController.parent_artifact).toEqual(artifact);
            expect(getArtifact).toHaveBeenCalledWith(parent_artifact_id);
            expect(LinkFieldController.loadParentArtifactsTitle).not.toHaveBeenCalled();
        });

        it("Given the modal was in creation mode and given I can't choose a parent, then the list of possible parents won't be loaded", () => {
            canChooseArtifactsParent.mockReturnValue(false);
            isInCreationMode.mockReturnValue(true);

            LinkFieldController.$onInit();
            $rootScope.$apply();

            expect(LinkFieldController.parent_artifact).toBeNull();
            expect(LinkFieldController.loadParentArtifactsTitle).not.toHaveBeenCalled();
        });

        it("Given the modal was in edition mode and given I can choose a parent, then the list of possible parents will be loaded", () => {
            LinkFieldController.hasArtifactAlreadyAParent.mockReturnValue($q.when(null));
            canChooseArtifactsParent.mockReturnValue(true);
            isInCreationMode.mockReturnValue(false);

            LinkFieldController.$onInit();
            $rootScope.$apply();

            expect(LinkFieldController.loadParentArtifactsTitle).toHaveBeenCalled();
        });

        it("Given the modal was in edition mode and given I can't choose a parent, then the list of possible parents won't be loaded", () => {
            const artifact = { id: 59 };
            LinkFieldController.hasArtifactAlreadyAParent.mockReturnValue($q.when(artifact));
            canChooseArtifactsParent.mockReturnValue(false);
            isInCreationMode.mockReturnValue(false);

            LinkFieldController.$onInit();
            $rootScope.$apply();

            expect(LinkFieldController.parent_artifact).toEqual(artifact);
            expect(LinkFieldController.loadParentArtifactsTitle).not.toHaveBeenCalled();
        });
    });

    describe("showParentArtifactChoice() -", () => {
        let tracker, parent_artifact, possible_parent_artifacts;

        beforeEach(() => {
            canChooseArtifactsParent.mockReturnValue(true);
            tracker = {
                id: 43,
                parent: {
                    id: 64,
                },
            };
            parent_artifact = {
                id: 154,
            };
            possible_parent_artifacts = [{ id: 629 }];
        });

        it("Given that I can choose a parent artifact and given the list of possible parent artifacts wasn't empty, when I check if I show the parent artifact choice, then it will return true", () => {
            Object.assign(LinkFieldController, {
                tracker,
                parent_artifact,
                possible_parent_artifacts,
                has_current_project_parents: false,
            });

            const result = LinkFieldController.showParentArtifactChoice();

            expect(canChooseArtifactsParent).toHaveBeenCalledWith(tracker, parent_artifact, false);
            expect(result).toBeTruthy();
        });

        it("Given that the list of possible parent artifacts was empty, when I check if I show the parent artifact choice, then it will return false", () => {
            possible_parent_artifacts = [];
            Object.assign(LinkFieldController, {
                tracker,
                parent_artifact,
                possible_parent_artifacts,
            });

            const result = LinkFieldController.showParentArtifactChoice();

            expect(result).toBeFalsy();
        });

        it("Given that I cannot choose a parent artifact, when I check if I show the parent artifact choice, then it will return false", () => {
            canChooseArtifactsParent.mockReturnValue(false);
            Object.assign(LinkFieldController, {
                tracker,
                parent_artifact,
                possible_parent_artifacts,
            });

            const result = LinkFieldController.showParentArtifactChoice();

            expect(result).toBeFalsy();
        });

        describe("list-picker on parent selector", () => {
            beforeEach(() => {
                Object.assign(LinkFieldController, {
                    tracker,
                    parent_artifact,
                    possible_parent_artifacts,
                });
            });

            it("should not init list-picker when it is disabled", () => {
                LinkFieldController.is_list_picker_enabled = false;

                jest.spyOn(list_picker_lib, "createListPicker");

                LinkFieldController.$onInit();

                expect(list_picker_lib.createListPicker).not.toHaveBeenCalled();
            });

            it("should init list-picker when it is enabled and target select has been rendered", () => {
                LinkFieldController.is_list_picker_enabled = true;
                jest.spyOn(rest_service, "getAllOpenParentArtifacts").mockResolvedValue([]);

                jest.spyOn(list_picker_lib, "createListPicker");

                LinkFieldController.$onInit();

                expect(list_picker_lib.createListPicker).not.toHaveBeenCalled();

                LinkFieldController.is_loading = false;
                $scope.$digest();

                expect(list_picker_lib.createListPicker).toHaveBeenCalled();
            });

            it("list_picker_instance::destroy() callback should be called when there is a list-picker and the component is destroyed", () => {
                LinkFieldController.$onInit();

                jest.spyOn(LinkFieldController, "destroy_list_picker_callback");

                LinkFieldController.$onDestroy();

                expect(LinkFieldController.destroy_list_picker_callback).toHaveBeenCalled();
            });
        });
    });

    describe("hasArtifactAlreadyAParent() -", () => {
        let getFirstReverseIsChildLink, wrapPromise;
        beforeEach(() => {
            wrapPromise = createAngularPromiseWrapper($rootScope);
            getFirstReverseIsChildLink = jest.spyOn(rest_service, "getFirstReverseIsChildLink");
            LinkFieldController.$onInit();
        });

        it("will return the first linked reverse _is_child artifact", async () => {
            LinkFieldController.artifact_id = 82;
            const parent_artifact = { id: 45 };
            getFirstReverseIsChildLink.mockReturnValue($q.when([parent_artifact]));

            const promise = LinkFieldController.hasArtifactAlreadyAParent();
            expect(LinkFieldController.is_loading).toBe(true);

            await expect(wrapPromise(promise)).resolves.toBe(parent_artifact);
            expect(getFirstReverseIsChildLink).toHaveBeenCalledWith(82);
            expect(LinkFieldController.is_loading).toBe(false);
        });

        it("Given there wasn't any linked reverse _is_child artifact, then it will return null", async () => {
            LinkFieldController.artifact_id = 34;
            getFirstReverseIsChildLink.mockReturnValue($q.when([]));

            const promise = LinkFieldController.hasArtifactAlreadyAParent();

            await expect(wrapPromise(promise)).resolves.toBeNull();
        });
    });

    describe("loadParentArtifactsTitle() -", () => {
        it("will load all the possible parent artifacts and assign them to the controller, formatted and sorted by project trackers", async () => {
            LinkFieldController.tracker = {
                id: 37,
            };
            const collection = [
                {
                    id: 747,
                    title: "forcipated",
                    tracker: {
                        id: 30,
                        label: "flareboard",
                        project: {
                            id: 104,
                            label: "Program 1",
                        },
                    },
                },
                {
                    id: 634,
                    title: "viability",
                    tracker: {
                        id: 31,
                        label: "skateboard",
                        project: {
                            id: 105,
                            label: "Program 2",
                        },
                    },
                },
                {
                    id: 636,
                    title: "stability",
                    tracker: {
                        id: 32,
                        label: "longboard",
                        project: {
                            id: 106,
                            label: "Program 2",
                        },
                    },
                },
            ];
            jest.spyOn(rest_service, "getAllOpenParentArtifacts").mockImplementation(() =>
                $q.when(collection)
            );
            const wrapPromise = createAngularPromiseWrapper($rootScope);

            LinkFieldController.$onInit();
            const promise = LinkFieldController.loadParentArtifactsTitle();
            expect(LinkFieldController.is_loading).toBe(true);

            await wrapPromise(promise);
            expect(LinkFieldController.is_loading).toBe(false);
            expect(LinkFieldController.possible_parent_artifacts).toEqual([
                {
                    artifacts: [
                        {
                            formatted_ref: "flareboard #747 - forcipated",
                            id: 747,
                        },
                    ],
                    label: "Program 1 - open flareboard",
                },
                {
                    artifacts: [
                        {
                            formatted_ref: "skateboard #634 - viability",
                            id: 634,
                        },
                    ],
                    label: "Program 2 - open skateboard",
                },
                {
                    artifacts: [
                        {
                            formatted_ref: "longboard #636 - stability",
                            id: 636,
                        },
                    ],
                    label: "Program 2 - open longboard",
                },
            ]);
        });
    });
});
