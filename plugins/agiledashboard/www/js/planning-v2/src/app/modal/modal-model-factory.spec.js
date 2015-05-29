describe("ModalModelFactory createFromStructure() - ", function() {
    var ModalModelFactory;
    beforeEach(function() {
        module('modal');

        inject(function (_ModalModelFactory_) {
            ModalModelFactory = _ModalModelFactory_;
        });
    });

    describe("Given a tracker structure object containing a string field,", function() {
        it("and that it didn't have a default value, when I create the model from the structure, then an array of objects containing only the field's id and a null value will be returned", function() {
            var input = {
                fields: [
                    {
                        field_id: 870,
                        label: "Mammilloid",
                        name: "coquelicot",
                        type: "string"
                    }
                ]
            };
            var output = ModalModelFactory.createFromStructure(input);
            expect(output).toEqual([
                { field_id: 870, value: null }
            ]);
        });

        it("and that it had a default value, when I create the model from the structure, then an array of objects containing only the field's id and its default value will be returned", function() {
            var input = {
                fields: [
                    {
                        field_id: 175,
                        label: "cardiopneumograph",
                        name: "idolatrize",
                        type: "string",
                        default_value: "Despina Pistorius chronoisothermal"
                    }
                ]
            };
            var output = ModalModelFactory.createFromStructure(input);
            expect(output).toEqual([
                { field_id: 175, value: "Despina Pistorius chronoisothermal" }
            ]);
        });
    });

    describe("Given a tracker structure object containing a text field, an int field and a float field,", function() {
        it("and that those fields didn't have a default value, when I create the model from the structure, then an array of objects containing only the fields' id and a null value will be returned", function() {
            var input = {
                fields: [
                    {
                        field_id: 685,
                        label: "raiiform",
                        name: "loft",
                        type: "int"
                    }, {
                        field_id: 775,
                        label: "phalacrocoracine",
                        name: "unvariant",
                        type: "float"
                    }, {
                        field_id: 432,
                        label: "multivoltine",
                        name: "endevil",
                        type: "text"
                    }
                ]
            };
            var output = ModalModelFactory.createFromStructure(input);
            expect(output).toEqual([
                { field_id: 685, value: null },
                { field_id: 775, value: null },
                { field_id: 432, value: null }
            ]);
        });

        it("and that those fields had a default value, when I create the model from the structure, then an array of objects containing only the fields' id and their default value will be returned", function() {
            var input = {
                fields: [
                    {
                        field_id: 163,
                        label: "urinocryoscopy",
                        name: "priestless",
                        type: "float",
                        default_value: "68.8596"
                    }, {
                        field_id: 220,
                        label: "formel",
                        name: "hodograph",
                        type: "int",
                        default_value: "236"
                    }, {
                        field_id: 313,
                        label: "ophicephaloid",
                        name: "bombed",
                        type: "text",
                        default_value: {
                            format: "text",
                            content: "Dravidian ocarina"
                        }
                    }
                ]
            };
            var output = ModalModelFactory.createFromStructure(input);
            expect(output).toEqual([
                { field_id: 163, value: 68.8596 },
                { field_id: 220, value: 236 },
                { field_id: 313, value: "Dravidian ocarina" }
            ]);
            expect(_.isNumber(output[0].value)).toBeTruthy();
            expect(_.isNumber(output[1].value)).toBeTruthy();
        });
    });

    describe("Given a tracker structure object containing a selectbox and a multiselectbox field", function() {
        it("and that those fields didn't have a default value, when I create the model from the structure, then an array of objects containing the fields' id and an empty bind_value_ids array will be returned", function() {
            var input = {
                fields: [
                    {
                        field_id: 87,
                        label: "monarchist",
                        name: "artophorion",
                        type: "sb"
                    }, {
                        field_id: 860,
                        label: "gorilline",
                        name: "beefer",
                        type: "msb"
                    }
                ]
            };
            var output = ModalModelFactory.createFromStructure(input);
            expect(output).toEqual([
                { field_id: 87, bind_value_ids: [] },
                { field_id: 860, bind_value_ids: [] }
            ]);
        });

        it("and that those fields had a default value, when I create the model from the structure, then an array of objects containing the fields' id and an array containing their default value(s) will be returned", function() {
            var input = {
                fields: [
                    {
                        field_id: 622,
                        label: "perfectionize",
                        name: "boatmaster",
                        type: "sb",
                        default_value: 941
                    }, {
                        field_id: 698,
                        label: "perfectionize",
                        name: "boatmaster",
                        type: "msb",
                        default_value: [196, 800]
                    }
                ]
            };
            var output = ModalModelFactory.createFromStructure(input);
            expect(output).toEqual([
                { field_id: 622, bind_value_ids: [941] },
                { field_id: 698, bind_value_ids: [196, 800] }
            ]);
        });
    });

    describe("Given a track structure object containing a checkbox field with 3 possible values,", function() {
        it("and that it didn't have a default value, when I create the model from the structure, then an array of objects containing only the field's id and a bind_value_ids array filled with 3 nulls will be returned", function() {
            var input = {
                fields: [
                    {
                        field_id: 607,
                        label: "visit",
                        name: "Narcobatidae",
                        type: "cb",
                        values: [
                            { id: 842, label: "mussal"},
                            { id: 733, label: "Nepenthaceae"},
                            { id: 833, label: "Vaticanize"}
                        ]
                    }
                ]
            };
            var output = ModalModelFactory.createFromStructure(input);
            expect(output).toEqual([
                { field_id: 607, bind_value_ids: [null, null, null]}
            ]);
        });

        it("and that it had 2 default values, when I create the model from the structure, then an array of objects containing only the field's id and a bind_value_ids array filled with the 2 default values and a null will be returned", function() {
            var input = {
                fields: [
                    {
                        field_id: 910,
                        label: "transpirable",
                        name: "levolimonene",
                        type: "cb",
                        values: [
                            { id: 477, label: "Reuel"},
                            { id: 440, label: "espalier"},
                            { id: 848, label: "overtrust"}
                        ],
                        default_value: [477, 848]
                    }
                ]
            };
            var output = ModalModelFactory.createFromStructure(input);
            expect(output).toEqual([
                { field_id: 910, bind_value_ids: [477, null, 848]}
            ]);
        });
    });

    describe("Given a tracker structure object containing a radiobutton field,", function() {
        it("and that it didn't have a default value, when I create the model from the structure, then an array of objects containing only the field's id and a bind_value_ids array [100] will be returned", function() {
            var input = {
                fields: [
                    {
                        field_id: 242,
                        label: "haruspicy",
                        name: "Taraktogenos",
                        type: "rb"
                    }
                ]
            };
            var output = ModalModelFactory.createFromStructure(input);
            expect(output).toEqual([
                { field_id: 242, bind_value_ids: [100] }
            ]);
        });

        it("and that it had a default value, when I create the model from the structure, then an array of objects containing the field's id and an array of its default values will be returned", function() {
            var input = {
                fields: [
                    {
                        field_id: 897,
                        label: "healless",
                        name: "veiling",
                        type: "rb",
                        default_value: [931,410]
                    }
                ]
            };
            var output = ModalModelFactory.createFromStructure(input);
            expect(output).toEqual([
                { field_id: 897, bind_value_ids: [931, 410] }
            ]);
        });
    });

    it("Given a tracker structure object containing an artifact links field, when I create the model from the structure, then an array of objects containing the fields' id, an empty string that will contain the list of ids to link to and an empty links array will be returned", function() {
        var input = {
            fields: [
                {
                    field_id: 803,
                    label: "inspectrix",
                    name: "isonomic",
                    type: "art_link"
                }
            ]
        };
        var output = ModalModelFactory.createFromStructure(input);
        expect(output).toEqual([
            {
                field_id: 803,
                unformatted_links: "",
                links: [ {id: ""} ]
            }
        ]);
    });
});
