describe("TuleapArtifactModalComputedFieldController", function() {
    var TuleapArtifactModalComputedFieldController;

    beforeEach(function() {
        module('tuleap-artifact-modal-computed-field');

        var $controller;

        inject(function(
            _$controller_
        ) {
            $controller = _$controller_;
        });

        TuleapArtifactModalComputedFieldController = $controller('TuleapArtifactModalComputedFieldController', {});
        TuleapArtifactModalComputedFieldController.value_model = {
            value          : null,
            is_autocomputed: false
        };
        TuleapArtifactModalComputedFieldController.field = {
            value: 8
        };
    });

    describe("switchToAutocomputed() -", function() {
        it("When I switch the computed field to autocomputed, then its manual_value will be set to null and its is_autocomputed flag will be true", function() {
            TuleapArtifactModalComputedFieldController.value_model.manual_value    = 6;
            TuleapArtifactModalComputedFieldController.value_model.is_autocomputed = false;

            TuleapArtifactModalComputedFieldController.switchToAutocomputed();

            expect(TuleapArtifactModalComputedFieldController.value_model.manual_value).toBe(null);
            expect(TuleapArtifactModalComputedFieldController.value_model.is_autocomputed).toBe(true);
        });
    });

    describe("switchToManual() -", function() {
        it("When I switch the computed field to manual, then its is_autocomputed flag will be false", function() {
            TuleapArtifactModalComputedFieldController.value_model.is_autocomputed = true;

            TuleapArtifactModalComputedFieldController.switchToManual();

            expect(TuleapArtifactModalComputedFieldController.field.value).toEqual(8);
            expect(TuleapArtifactModalComputedFieldController.value_model.is_autocomputed).toBe(false);
        });
    });
});
