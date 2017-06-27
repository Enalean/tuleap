describe("TuleapArtifactModalPermissionFieldValidateService -", function() {
    var TuleapArtifactModalPermissionFieldValidateService;

    beforeEach(function() {
        module('tuleap-artifact-modal-permission-field');

        inject(function(
            _TuleapArtifactModalPermissionFieldValidateService_
        ) {
            TuleapArtifactModalPermissionFieldValidateService = _TuleapArtifactModalPermissionFieldValidateService_;
        });
    });

    describe("validateFieldValue() -", function() {
        it("Given a field value that was undefined, then it will return null", function() {
            var result = TuleapArtifactModalPermissionFieldValidateService.validateFieldValue(undefined);

            expect(result).toBe(null);
        });

        it("Given a field value object, it will only keep field_id and value attributes", function() {
            var field_value = {
                field_id   : 166,
                label      : "stallboard",
                permissions: ["read", "update", "create"],
                value      : {
                    is_used_by_default: true,
                    granted_groups    : ["1", "101"]
                }
            };

            var result = TuleapArtifactModalPermissionFieldValidateService.validateFieldValue(field_value);

            expect(result).toEqual({
                field_id: 166,
                value   : {
                    is_used_by_default: true,
                    granted_groups    : ["1", "101"]
                }
            });
        });
    });
});
