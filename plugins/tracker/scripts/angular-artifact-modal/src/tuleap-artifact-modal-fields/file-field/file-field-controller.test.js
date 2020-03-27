import file_field_module from "./file-field.js";
import angular from "angular";
import "angular-mocks";

import BaseFileFieldController from "./file-field-controller.js";

describe("TuleapArtifactModalFileFieldController", function () {
    var TuleapArtifactModalFileFieldController;

    beforeEach(function () {
        angular.mock.module(file_field_module);

        angular.mock.inject(function ($controller) {
            TuleapArtifactModalFileFieldController = $controller(BaseFileFieldController, {});
            TuleapArtifactModalFileFieldController.value_model = {
                value: [],
                temporary_files: [],
            };
        });
    });

    describe("addTemporaryFileInput() -", function () {
        it("When I add a temporary file upload, then an empty object will be pushed into the value_model object's temporary_files array", function () {
            TuleapArtifactModalFileFieldController.value_model.temporary_files = [
                {
                    file: {},
                    description: "",
                },
            ];

            TuleapArtifactModalFileFieldController.addTemporaryFileInput();

            expect(TuleapArtifactModalFileFieldController.value_model.temporary_files).toEqual([
                {
                    file: {},
                    description: "",
                },
                {},
            ]);
        });
    });

    describe("resetTemporaryFileInput() -", function () {
        it("Given an index and given there was a temporary file object in the value_model at this index, when I reset a temporary file input, then temporary file object at that index will be replaced with empty properties", function () {
            TuleapArtifactModalFileFieldController.value_model.temporary_files = [
                {
                    file: {
                        filename: "Tristram",
                    },
                    description: "one",
                },
                {
                    file: {
                        filename: "foreconsent",
                    },
                    description: "two",
                },
                {
                    file: {
                        filename: "nondisciplinary",
                    },
                    description: "three",
                },
            ];

            TuleapArtifactModalFileFieldController.resetTemporaryFileInput(1);

            expect(TuleapArtifactModalFileFieldController.value_model.temporary_files).toEqual([
                {
                    file: {
                        filename: "Tristram",
                    },
                    description: "one",
                },
                {
                    file: {},
                    description: "",
                },
                {
                    file: {
                        filename: "nondisciplinary",
                    },
                    description: "three",
                },
            ]);
        });

        it("Given that the value_model's temporary_files array was empty, and given an index of 2, when I reset a temporary file input, then the temporary_files array will stay empty", function () {
            TuleapArtifactModalFileFieldController.value_model.temporary_files = [];

            TuleapArtifactModalFileFieldController.resetTemporaryFileInput(2);

            expect(TuleapArtifactModalFileFieldController.value_model.temporary_files).toEqual([]);
        });
    });

    describe("toggleMarkedForRemoval() -", function () {
        it("Given a file object that was marked for removal and an index, when I toggle the mark for removal on the file, then the file will no longer be marked for removal and its id will be inserted at the given index in the value_model object's value array", function () {
            TuleapArtifactModalFileFieldController.value_model.value = [84, 71, 42];
            var file = {
                id: 20,
                marked_for_removal: true,
            };
            var index = 3;

            TuleapArtifactModalFileFieldController.toggleMarkedForRemoval(file, index);

            expect(TuleapArtifactModalFileFieldController.value_model.value[index]).toEqual(
                file.id
            );
            expect(file.marked_for_removal).toBeFalsy();
        });

        it("Given that the value_model's value array was empty, and given a file object that was marked for removal and an index of 2, when I toggle the mark for removal on the file, then the file's id will be the only value in the array", function () {
            TuleapArtifactModalFileFieldController.value_model.value = [];
            var file = {
                id: 77,
                marked_for_removal: true,
            };
            var index = 2;

            TuleapArtifactModalFileFieldController.toggleMarkedForRemoval(file, index);

            expect(TuleapArtifactModalFileFieldController.value_model.value).toEqual([77]);
        });

        it("Given a file object that wasn't marked for removal and an index, when I toggle the mark for removal on the file, then the file will be marked for removal and its id will be removed from the value_model object's value array", function () {
            TuleapArtifactModalFileFieldController.value_model.value = [69, 48, 43];
            var file = {
                id: 43,
                marked_for_removal: false,
            };
            var index = 3;

            TuleapArtifactModalFileFieldController.toggleMarkedForRemoval(file, index);

            expect(TuleapArtifactModalFileFieldController.value_model.value).toEqual([69, 48]);
            expect(file.marked_for_removal).toBeTruthy();
        });
    });
});
