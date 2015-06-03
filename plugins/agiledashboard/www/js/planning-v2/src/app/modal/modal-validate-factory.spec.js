describe("ModalValidateFactory validateArtifactFieldsValues() -", function() {
    var ModalValidateFactory;
    beforeEach(function() {
        module('modal');

        inject(function(_ModalValidateFactory_) {
            ModalValidateFactory = _ModalValidateFactory_;
        });
    });

    it("Given an array containing field data including empty string, null and undefined values, when I validate the fields data, then an object containing only fields whose value is defined will be returned", function() {
        var input = [
            { field_id: 422, value: null },
            { field_id: 967, value: "petrogenic" },
            { field_id: 768, value: undefined },
            { field_id: 847, value: 1.37765 },
            { field_id: 328, value: "" },
            { field_id: 898 }
        ];
        var output = ModalValidateFactory.validateArtifactFieldsValues(input);
        expect(output).toEqual([
            { field_id: 422, value: null },
            { field_id: 967, value: "petrogenic" },
            { field_id: 847, value: 1.37765 },
            { field_id: 328, value: "" }
        ]);
    });

    it("Given an array containing date, int and fload fields with null values, when I validate the fields data, then an object containing all the fields' values as empty strings will be returned", function() {
        var input = [
            { field_id: 54, type: "int", value: null },
            { field_id: 257, type: "float", value: null },
            { field_id: 195, type: "date", value: null }
        ];
        var output = ModalValidateFactory.validateArtifactFieldsValues(input);
        expect(output).toEqual([
            { field_id: 54, value: "" },
            { field_id: 257, value: "" },
            { field_id: 195, value: "" }
        ]);
    });

    it("Given an array containing selectboxes or multiselectboxes or checkboxes fields, when I validate the fields data, then an object containing only fields whose bind_value_ids are not empty or null will be returned", function() {
        var input = [
            { field_id: 87, bind_value_ids: null },
            { field_id: 597, bind_value_ids: [] },
            { field_id: 785, bind_value_ids: [787, 857] },
            { field_id: 180 }
        ];
        var output = ModalValidateFactory.validateArtifactFieldsValues(input);
        expect(output).toEqual([
            { field_id: 597, bind_value_ids: [] },
            { field_id: 785, bind_value_ids: [787, 857] }
        ]);
    });

    it("Given an array containing a checkbox field and given that its bind_value_ids contains null and undefined values, when I validate the fields, then an object containing this field with a bind_value_ids containing only non-null integers will be returned", function() {
        var input = [
            { field_id: 643, bind_value_ids: [undefined, 840, null, 959] }
        ];
        var output = ModalValidateFactory.validateArtifactFieldsValues(input);
        expect(output).toEqual([
            { field_id: 643, bind_value_ids: [840, 959] }
        ]);
    });

    describe("Given an array containing an artifact link field and", function() {
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
                    ]
                }
            ];
            var output = ModalValidateFactory.validateArtifactFieldsValues(input);
            expect(output).toEqual([
                {
                    field_id: 986,
                    links: [
                        { id: 202 },
                        { id: 584 }
                    ]
                }
            ]);
        });

        it("given that its links array contains an object with an id and its unformatted_links contains a comma-separated list of ids, when I validate the fields, then an object containing the field with a links containing only non-null ids will be returned", function() {
            var input = [
                {
                    field_id: 162,
                    links: [
                        { id: 18 }
                    ],
                    unformatted_links: "text,650, 673"
                }
            ];
            var output = ModalValidateFactory.validateArtifactFieldsValues(input);
            expect(output).toEqual([
                {
                    field_id: 162,
                    links: [
                        { id: 18 },
                        { id: 650 },
                        { id: 673 }
                    ]
                }
            ]);
        });
    });
});
