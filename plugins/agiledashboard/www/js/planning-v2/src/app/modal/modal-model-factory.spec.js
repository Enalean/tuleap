describe("ModalModelFactory createFromStructure() - ", function() {
    var ModalModelFactory, $window;
    beforeEach(function() {
        module('modal');

        inject(function(_ModalModelFactory_, _$window_) {
            ModalModelFactory = _ModalModelFactory_;
            $window = _$window_;

            $window.moment = jasmine.createSpy("moment").andReturn({
                ISO_8601: "ISO_8601",
                format: jasmine.createSpy("format")
            });
        });
    });

    describe("createFromStructure() -", function() {
        it("Given an array of artifact field objects containing a field_id and a value and given a tracker structure object containing those fields, when I create the model from the structure, then a map containing all the fields provided and also containing default values for all the other fields of the structure will be returned", function() {
            var artifact_values = [
                { field_id: 655, value: "alumna Aurora Arpin" },
                { field_id: 378, bind_value_ids: [667, 967] },
                { field_id: 320, links: [
                    { id: 158},
                    { id: 434}
                ]}
            ];
            var structure = {
                fields: [
                    {
                        field_id: 655,
                        label: "antithetically",
                        name: "arbusterol",
                        type: "string",
                        default_value: "yogasana"
                    }, {
                        field_id: 728,
                        label: "turus",
                        name: "hemicycle",
                        type: "rb",
                        default_value: [422]
                    }, {
                        field_id: 378,
                        label: "overplay",
                        name: "awaredom",
                        type: "sb",
                        default_value: [967]
                    }, {
                        field_id: 320,
                        label: "rani",
                        name: "troot",
                        type: "art_link"
                    }
                ]
            };
            var output = ModalModelFactory.createFromStructure(artifact_values, structure);
            expect(output).toEqual({
                655: { field_id: 655, value: "alumna Aurora Arpin" },
                728: { field_id: 728, bind_value_ids: [422] },
                378: { field_id: 378, bind_value_ids: [667, 967] },
                320: { field_id: 320, links: [
                    { id: 158 },
                    { id: 434 }
                ]}
            });
        });

        it("Given an array of artifact field values containing a string field, a cross field, a burndown field, a priority field and a computed field and given a tracker structure object containing those fields, when I create the model from the structure, then the cross, burndown, priority and computed fields won't have a value in the returned map", function() {
            var artifact_values = [
                { field_id: 33, value: "Nadia Ledon" },
                { field_id: 79, value: [] },
                { field_id: 81, value: 98 },
                { field_id: 10, value: {
                    "duration": 85,
                    "capacity": 79,
                    "points": [11.52, 87.50, 70.65]
                } },
                { field_id: 28, value: 86 }
            ];
            var structure = {
                fields: [
                    { field_id: 33, type: "string" },
                    { field_id: 79, type: "cross" },
                    { field_id: 81, type: "priority" },
                    { field_id: 10, type: "burndown" },
                    { field_id: 28, type: "computed" }
                ]
            };
            var output = ModalModelFactory.createFromStructure(artifact_values, structure);
            expect(output).toEqual({
                33: { field_id: 33, value: "Nadia Ledon" },
                79: { field_id: 79 },
                81: { field_id: 81 },
                10: { field_id: 10 },
                28: { field_id: 28 }
            });
        });

        describe("Given a tracker structure object containing a string field,", function() {
            it("and that it didn't have a default value, when I create the model from the structure, then a map of objects containing only the field's id and a null value will be returned", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 870,
                            label: "Mammilloid",
                            name: "coquelicot",
                            type: "string"
                        }
                    ]
                };
                var output = ModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    870: { field_id: 870, value: null }
                });
            });

            it("and that it had a default value, when I create the model from the structure, then a map of objects containing only the field's id and its default value will be returned", function() {
                var structure = {
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
                var output = ModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    175: { field_id: 175, value: "Despina Pistorius chronoisothermal" }
                });
            });
        });

        describe("Given a tracker structure object containing a text field,", function() {
            it("and given an array of artifact field values containing that field, when I create the model from the structure, then a map of objects containing the formatted artifact value will be returned", function() {
                var artifact_values = [
                    {
                        field_id: 901,
                        value: "<p><b>Cleta</b> Goetsch bicipital <em>xylophagid</em></p>",
                        format: "HTML"
                    }
                ];
                var structure = {
                    fields: [
                        {
                            field_id: 901,
                            label: "holard",
                            name: "flueless",
                            type: "text"
                        }
                    ]
                };
                var output = ModalModelFactory.createFromStructure(artifact_values, structure);
                expect(output).toEqual({
                    901: {
                        field_id: 901,
                        format: "HTML",
                        value: "<p><b>Cleta</b> Goetsch bicipital <em>xylophagid</em></p>"
                    }
                });
            });

            it("and that id didn't have a default value, when I create the model from the structure, then a map of objects containing the field's id, the 'text' format and a null value", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 336,
                            label: "heritage",
                            name: "forbidder",
                            type: "text"
                        }
                    ]
                };
                var output = ModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    336: {
                        field_id: 336,
                        format: "text",
                        value: null
                    }
                });
            });

            it("and that it had a default value, when I create the model from the structure, then a map of objects containing the field's id, the default format and the default value", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 349,
                            label: "excoriator",
                            name: "phratrial",
                            type: "text",
                            default_value: {
                                format: "HTML",
                                content: "<p>quartane <b>Christel</b> Kalchik roentgentherapy</p>"
                            }
                        }
                    ]
                };
                var output = ModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    349: {
                        field_id: 349,
                        format: "HTML",
                        value: "<p>quartane <b>Christel</b> Kalchik roentgentherapy</p>"
                    }
                });
            });
        });

        describe("Given a tracker structure object containing an int field and a float field,", function() {
            it("and that those fields didn't have a default value, when I create the model from the structure, then a map of objects containing only the fields' id and a null value will be returned", function() {
                var structure = {
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
                        }
                    ]
                };
                var output = ModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    685: { field_id: 685, value: null },
                    775: { field_id: 775, value: null }
                });
            });

            it("and that those fields had a default value, when I create the model from the structure, then a map of objects containing only the fields' id and their default value will be returned", function() {
                var structure = {
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
                        }
                    ]
                };
                var output = ModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    163: { field_id: 163, value: 68.8596 },
                    220: { field_id: 220, value: 236 }
                });
                expect(_.isNumber(output[163].value)).toBeTruthy();
                expect(_.isNumber(output[220].value)).toBeTruthy();
            });
        });

        describe("Given a tracker structure object containing a date field", function() {
            it("without the time displayed and given an array of artifact field values containing that field, when I create the model from the structure, then a map of objects containing the formatted artifact value will be returned ", function() {
                var artifact_values = [
                    { field_id: 824, value: "2015-05-29T00:00:00+02:00"}
                ];
                var structure = {
                    fields: [
                        {
                            field_id: 824,
                            label: "nondrying",
                            name: "indisciplined",
                            type: "date"
                        }
                    ]
                };
                var output = ModalModelFactory.createFromStructure(artifact_values, structure);
                expect($window.moment).toHaveBeenCalledWith("2015-05-29T00:00:00+02:00", $window.moment.ISO_8601);
                expect($window.moment().format).toHaveBeenCalledWith("YYYY-MM-DD");
                expect(output[824].field_id).toEqual(824);
            });

            it("with the time displayed and given an array of artifact field values containing that field, when I create the model from the structure, then a map of objects containing the formatted artifact value will be returned", function() {
                var artifact_values = [
                    { field_id: 609, value: "2015-06-02T18:09:43+03:00"}
                ];
                var structure = {
                    fields: [
                        {
                            field_id: 609,
                            label: "",
                            name: "",
                            type: "date",
                            is_time_displayed: "true"
                        }
                    ]
                };
                var output = ModalModelFactory.createFromStructure(artifact_values, structure);
                expect($window.moment).toHaveBeenCalledWith("2015-06-02T18:09:43+03:00", $window.moment.ISO_8601);
                expect($window.moment().format).toHaveBeenCalledWith("YYYY-MM-DD HH:mm:ss");
                expect(output[609].field_id).toEqual(609);
            });
        });

        describe("Given a tracker structure object containing a selectbox and a multiselectbox field", function() {
            it("and that those fields didn't have a default value, when I create the model from the structure, then a map of objects containing the fields' id and an empty bind_value_ids array will be returned", function() {
                var structure = {
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
                var output = ModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    87:  { field_id: 87, bind_value_ids: [] },
                    860: { field_id: 860, bind_value_ids: [] }
                });
            });

            it("and that those fields had a default value, when I create the model from the structure, then a map of objects containing the fields' id and an array containing their default value(s) will be returned", function() {
                var structure = {
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
                var output = ModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    622: { field_id: 622, bind_value_ids: [941] },
                    698: { field_id: 698, bind_value_ids: [196, 800] }
                });
            });
        });

        describe("Given a tracker structure object containing a checkbox field with 3 possible values,", function() {
            it("and given an array of artifact field values containing that field, when I create the model from the structure, then a map of objects containing an array of 3 elements including the values in the artifact field value will be returned", function() {
                var artifact_values = [
                    { field_id: 137, bind_value_ids: [498, 443] }
                ];
                var structure = {
                    fields: [
                        {
                            field_id: 137,
                            label: "orthoveratric",
                            name: "daintith",
                            type: "cb",
                            values: [
                                { id: 498, label: "uncommendable" },
                                { id: 248, label: "Aleurodes" },
                                { id: 443, label: "thinglike" }
                            ]
                        }
                    ]
                };
                var output = ModalModelFactory.createFromStructure(artifact_values, structure);
                expect(output).toEqual({
                    137: { field_id: 137, bind_value_ids: [498, null, 443] }
                });
            });

            it("and that it didn't have a default value, when I create the model from the structure, then a map of objects containing only the field's id and a bind_value_ids array filled with 3 nulls will be returned", function() {
                var structure = {
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
                var output = ModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    607: { field_id: 607, bind_value_ids: [null, null, null]}
                });
            });

            it("and that it had 2 default values, when I create the model from the structure, then a map of objects containing only the field's id and a bind_value_ids array filled with the 2 default values and a null will be returned", function() {
                var structure = {
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
                var output = ModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    910: { field_id: 910, bind_value_ids: [477, null, 848]}
                });
            });
        });

        describe("Given a tracker structure object containing a radiobutton field,", function() {
            it("and given an array of artifact field values containing that field and that field's bind_value_ids array was empty, when I create the model from the structure, then a map of objects containing only the field's id and a bind_value_ids array [100] will be returned", function() {
                var artifact_values = [
                    { field_id: 430, bind_value_ids: [] }
                ];
                var structure = {
                    fields: [
                        {
                            field_id: 430,
                            label: "parascene",
                            name: "gap",
                            type: "rb"
                        }
                    ]
                };
                var output = ModalModelFactory.createFromStructure(artifact_values, structure);
                expect(output).toEqual({
                    430: { field_id: 430, bind_value_ids: [100] }
                });
            });

            it("and that it didn't have a default value, when I create the model from the structure, then a map of objects containing only the field's id and a bind_value_ids array [100] will be returned", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 242,
                            label: "haruspicy",
                            name: "Taraktogenos",
                            type: "rb"
                        }
                    ]
                };
                var output = ModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    242: { field_id: 242, bind_value_ids: [100] }
                });
            });

            it("and that it had a default value, when I create the model from the structure, then a map of objects containing the field's id and an array of its default values will be returned", function() {
                var structure = {
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
                var output = ModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    897: { field_id: 897, bind_value_ids: [931, 410] }
                });
            });
        });

        it("Given a tracker structure object containing an artifact links field, when I create the model from the structure, then a map of objects containing the fields' id, an empty string that will contain the list of ids to link to and an empty links array will be returned", function() {
            var structure = {
                fields: [
                    {
                        field_id: 803,
                        label: "inspectrix",
                        name: "isonomic",
                        type: "art_link"
                    }
                ]
            };
            var output = ModalModelFactory.createFromStructure([], structure);
            expect(output).toEqual({
                803: {
                    field_id: 803,
                    unformatted_links: "",
                    links: [ {id: ""} ]
                }
            });
        });
    });

    it('reorderFieldsInGoodOrder() -', function() {
        var response = {
            fields: [
                { field_id: 1, type: 'int' },
                { field_id: 2, type: 'int' },
                { field_id: 3, type: 'fieldset' },
                { field_id: 4, type: 'int' },
                { field_id: 5, type: 'column' },
                { field_id: 6, type: 'int' },
                { field_id: 7, type: 'aid' },
                { field_id: 8, type: 'atid' },
                { field_id: 9, type: 'lud' },
                { field_id: 10, type: 'burndown' },
                { field_id: 11, type: 'priority' },
                { field_id: 12, type: 'subby' },
                { field_id: 13, type: 'subon' },
                { field_id: 14, type: 'computed' },
                { field_id: 15, type: 'cross' },
                { field_id: 16, type: 'file' },
                { field_id: 17, type: 'tbl' },
                { field_id: 18, type: 'perm' }
            ],
            structure: [
                { id: 1, content: null },
                { id: 2, content: null },
                { id: 3, content: [
                    { id: 4, content: null },
                    { id: 5, content: [
                        { id: 6, content: null }
                    ]}
                ]},
                { id: 7, content: null },
                { id: 8, content: null },
                { id: 9, content: null },
                { id: 10, content: null },
                { id: 11, content: null },
                { id: 12, content: null },
                { id: 13, content: null },
                { id: 14, content: null },
                { id: 15, content: null },
                { id: 16, content: null },
                { id: 17, content: null },
                { id: 18, content: null }
            ]
        };

        expect(ModalModelFactory.reorderFieldsInGoodOrder(response)).toEqual([
            {
                field_id: 1,
                type: 'int',
                template_url: 'field-int.tpl.html'
            },
            {
                field_id: 2,
                type: 'int',
                template_url: 'field-int.tpl.html'
            },
            {
                field_id: 3,
                type: 'fieldset',
                template_url: 'field-fieldset.tpl.html',
                content: [
                    {
                        field_id: 4,
                        type: 'int',
                        template_url: 'field-int.tpl.html'
                    },
                    {
                        field_id: 5,
                        type: 'column',
                        template_url: 'field-column.tpl.html',
                        content: [
                            {
                                field_id: 6,
                                type: 'int',
                                template_url: 'field-int.tpl.html'
                            }
                        ]
                    }
                ]
            }
        ]);
    });
});
