import artifact_modal_module from "./tuleap-artifact-modal.js";
import angular from "angular";
import "angular-mocks";

describe("TuleapArtifactModalValidateService validateArtifactFieldsValues() -", function() {
    var ValidateService, creation_mode;
    beforeEach(function() {
        angular.mock.module(artifact_modal_module);

        angular.mock.inject(function(_TuleapArtifactModalValidateService_) {
            ValidateService = _TuleapArtifactModalValidateService_;
        });
    });

    describe("Given the modal was opened in edition mode", function() {
        beforeEach(function() {
            creation_mode = false;
        });

        it("and given an array containing field data including read-only and create-only fields, when I validate the fields data, then an object containing only fields whose permissions include 'update' will be returned", function() {
            var input = [
                {
                    field_id: 620,
                    permissions: ["read"],
                    value: "Meg Hinkston"
                },
                {
                    field_id: 17,
                    permissions: ["create"],
                    value: "Sharen Wikstrom"
                },
                {
                    field_id: 503,
                    permissions: ["update"],
                    value: "Kasie Steppello"
                }
            ];
            var output = ValidateService.validateArtifactFieldsValues(input, creation_mode);
            expect(output).toEqual([{ field_id: 503, value: "Kasie Steppello" }]);
        });

        it("and given an array containing field data including empty string, null and undefined values, when I validate the fields data, then an object containing only fields whose value is defined will be returned", function() {
            var input = [
                {
                    field_id: 422,
                    permissions: ["update"],
                    value: null
                },
                {
                    field_id: 967,
                    permissions: ["update"],
                    value: "petrogenic"
                },
                {
                    field_id: 768,
                    permissions: ["update"],
                    value: undefined
                },
                {
                    field_id: 847,
                    permissions: ["update"],
                    value: 1.37765
                },
                {
                    field_id: 328,
                    permissions: ["update"],
                    value: ""
                },
                {
                    field_id: 898,
                    permissions: ["update"]
                }
            ];
            var output = ValidateService.validateArtifactFieldsValues(input, creation_mode);
            expect(output).toEqual([
                { field_id: 422, value: null },
                { field_id: 967, value: "petrogenic" },
                { field_id: 847, value: 1.37765 },
                { field_id: 328, value: "" }
            ]);
        });
    });

    describe("Given the modal was opened in creation mode", function() {
        beforeEach(function() {
            creation_mode = true;
        });

        it("and given an array containing field data including read-only and update-only fields, when I validate the fields data, then an object containing only fields whose permissions include 'create' will be returned", function() {
            var input = [
                {
                    field_id: 907,
                    permissions: ["read"],
                    value: "Claud Shein"
                },
                {
                    field_id: 939,
                    permissions: ["create"],
                    value: "Tyesha Schatzman"
                },
                {
                    field_id: 597,
                    permissions: ["update"],
                    value: "Malorie Labossiere"
                }
            ];
            var output = ValidateService.validateArtifactFieldsValues(input, creation_mode);
            expect(output).toEqual([{ field_id: 939, value: "Tyesha Schatzman" }]);
        });

        it("and given an array containing date, int, fload and string fields with null values, when I validate the fields data, then an object containing all the fields' values as empty strings will be returned", function() {
            var input = [
                {
                    field_id: 54,
                    type: "int",
                    permissions: ["create"],
                    value: null
                },
                {
                    field_id: 257,
                    type: "float",
                    permissions: ["create"],
                    value: null
                },
                {
                    field_id: 195,
                    type: "date",
                    permissions: ["create"],
                    value: null
                },
                {
                    field_id: 461,
                    type: "string",
                    permissions: ["create"],
                    value: null
                }
            ];
            var output = ValidateService.validateArtifactFieldsValues(input, creation_mode);
            expect(output).toEqual([
                { field_id: 54, value: "" },
                { field_id: 257, value: "" },
                { field_id: 195, value: "" },
                { field_id: 461, value: "" }
            ]);
        });

        it("and given an array containing selectboxes or multiselectboxes or checkboxes fields, when I validate the fields data, then an object containing only fields whose bind_value_ids are not empty or null will be returned", function() {
            var input = [
                {
                    field_id: 87,
                    bind_value_ids: null,
                    permissions: ["create"]
                },
                {
                    field_id: 597,
                    bind_value_ids: [],
                    permissions: ["create"]
                },
                {
                    field_id: 785,
                    bind_value_ids: [787, 857],
                    permissions: ["create"]
                },
                {
                    field_id: 180,
                    permissions: ["create"]
                }
            ];
            var output = ValidateService.validateArtifactFieldsValues(input, creation_mode);
            expect(output).toEqual([
                { field_id: 597, bind_value_ids: [] },
                { field_id: 785, bind_value_ids: [787, 857] }
            ]);
        });

        it("and given an array containing a checkbox field and given that its bind_value_ids contains null and undefined values, when I validate the fields, then an object containing this field with a bind_value_ids containing only non-null integers will be returned", function() {
            var input = [
                {
                    field_id: 643,
                    bind_value_ids: [undefined, 840, null, 959],
                    permissions: ["create"]
                }
            ];
            var output = ValidateService.validateArtifactFieldsValues(input, creation_mode);
            expect(output).toEqual([{ field_id: 643, bind_value_ids: [840, 959] }]);
        });

        it("and given an array containing a file field with an empty value array, when I validate the fields, then the returned object will not contain the file field", function() {
            var input = [
                {
                    field_id: 166,
                    type: "int",
                    permissions: ["create"],
                    value: 1
                },
                {
                    field_id: 837,
                    type: "file",
                    permissions: ["create"],
                    value: []
                }
            ];
            var output = ValidateService.validateArtifactFieldsValues(input, creation_mode);
            expect(output).toEqual([{ field_id: 166, value: 1 }]);
        });

        describe("and given an array containing an artifact link field and", function() {
            it("given that its links array contains empty string, null and undefined values, when I validate the fields, then an object containing this field with a links containing only non-null ids will be returned", function() {
                var input = [
                    {
                        field_id: 986,
                        links: [
                            { id: "" },
                            { id: 202 },
                            { id: undefined },
                            { id: 584 },
                            { id: null }
                        ],
                        permissions: ["create"]
                    }
                ];
                var output = ValidateService.validateArtifactFieldsValues(input, creation_mode);
                expect(output).toEqual([
                    {
                        field_id: 986,
                        links: [{ id: 202 }, { id: 584 }]
                    }
                ]);
            });

            it("given that its links array contains an object with an id and its unformatted_links contains a comma-separated list of ids, when I validate the fields, then an object containing the field with a links containing only non-null ids will be returned", function() {
                var input = [
                    {
                        field_id: 162,
                        links: [{ id: 18 }],
                        permissions: ["create"],
                        unformatted_links: "text,650, 673"
                    }
                ];
                var output = ValidateService.validateArtifactFieldsValues(input, creation_mode);
                expect(output).toEqual([
                    {
                        field_id: 162,
                        links: [{ id: 18 }, { id: 650 }, { id: 673 }]
                    }
                ]);
            });
        });
    });
});
