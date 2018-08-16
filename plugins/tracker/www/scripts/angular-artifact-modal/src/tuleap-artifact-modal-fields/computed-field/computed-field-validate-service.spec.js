import computed_field_module from "./computed-field.js";
import angular from "angular";
import "angular-mocks";

describe("TuleapArtifactModalComputedFieldValidateService", function() {
    var ComputedFieldValidateService;

    beforeEach(function() {
        angular.mock.module(computed_field_module);

        angular.mock.inject(function(_TuleapArtifactModalComputedFieldValidateService_) {
            ComputedFieldValidateService = _TuleapArtifactModalComputedFieldValidateService_;
        });
    });

    describe("validateFieldValue() -", function() {
        it("Given a field value that was undefined, then it will return null", function() {
            var result = ComputedFieldValidateService.validateFieldValue(undefined);

            expect(result).toBe(null);
        });

        it("Given that the field value was set to autocomputed, then it will return the field without its manual value", function() {
            var field_value = {
                field_id: 415,
                is_autocomputed: true,
                label: "heresiologist",
                manual_value: 4,
                permissions: ["read", "update", "create"],
                value: 10
            };

            var result = ComputedFieldValidateService.validateFieldValue(field_value);

            expect(result).toEqual({
                field_id: 415,
                is_autocomputed: true
            });
        });

        describe("Given that the field value was not set to autocomputed", function() {
            it("and the manual value was null, then it will return null", function() {
                var field_value = {
                    field_id: 827,
                    is_autocomputed: false,
                    label: "Sangraal",
                    manual_value: null,
                    permissions: ["read", "update", "create"],
                    value: 97
                };

                var result = ComputedFieldValidateService.validateFieldValue(field_value);

                expect(result).toBe(null);
            });

            it("and the manual value was not null, then it will return the field without its is_autocomputed property", function() {
                var field_value = {
                    field_id: 306,
                    is_autocomputed: false,
                    label: "psalmless",
                    manual_value: 33,
                    permissions: ["read", "update", "create"],
                    value: 88
                };

                var result = ComputedFieldValidateService.validateFieldValue(field_value);

                expect(result).toEqual({
                    field_id: 306,
                    manual_value: 33
                });
            });
        });
    });
});
