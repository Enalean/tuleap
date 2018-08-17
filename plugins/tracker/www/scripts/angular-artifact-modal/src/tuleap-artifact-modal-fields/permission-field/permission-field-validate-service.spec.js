import permission_field_module from "./permission-field.js";
import angular from "angular";
import "angular-mocks";

describe("TuleapArtifactModalPermissionFieldValidateService -", function() {
    var PermissionFieldValidateService;

    beforeEach(function() {
        angular.mock.module(permission_field_module);

        angular.mock.inject(function(_TuleapArtifactModalPermissionFieldValidateService_) {
            PermissionFieldValidateService = _TuleapArtifactModalPermissionFieldValidateService_;
        });
    });

    describe("validateFieldValue() -", function() {
        it("Given a field value that was undefined, then it will return null", function() {
            var result = PermissionFieldValidateService.validateFieldValue(undefined);

            expect(result).toBe(null);
        });

        it("Given a field value object, it will only keep field_id and value attributes", function() {
            var field_value = {
                field_id: 166,
                label: "stallboard",
                permissions: ["read", "update", "create"],
                value: {
                    is_used_by_default: true,
                    granted_groups: ["1", "101"]
                }
            };

            var result = PermissionFieldValidateService.validateFieldValue(field_value);

            expect(result).toEqual({
                field_id: 166,
                value: {
                    is_used_by_default: true,
                    granted_groups: ["1", "101"]
                }
            });
        });
    });
});
