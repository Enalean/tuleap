describe("TuleapArtifactModalModelFactory createFromStructure() - ", function() {
    var TuleapArtifactModalModelFactory, $window;
    beforeEach(function() {
        module('tuleap.artifact-modal');

        inject(function(_TuleapArtifactModalModelFactory_, _$window_) {
            TuleapArtifactModalModelFactory = _TuleapArtifactModalModelFactory_;
            $window = _$window_;

            $window.moment = jasmine.createSpy("moment").andReturn({
                ISO_8601: "ISO_8601",
                format: jasmine.createSpy("format")
            });
        });
    });

    describe("createFromStructure() -", function() {
        describe("Given an array of artifact field values", function() {
            it("and given a tracker structure object containing those fields, when I create the model from the structure, then a map containing all the fields provided and also containing default values for all the other fields of the structure will be returned", function() {
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
                            permissions: ["read", "update", "create"],
                            default_value: "yogasana"
                        }, {
                            field_id: 728,
                            label: "turus",
                            name: "hemicycle",
                            type: "rb",
                            permissions: ["read", "update", "create"],
                            default_value: [422]
                        }, {
                            field_id: 378,
                            label: "overplay",
                            name: "awaredom",
                            type: "sb",
                            permissions: ["read", "update", "create"],
                            default_value: [967]
                        }, {
                            field_id: 320,
                            label: "rani",
                            name: "troot",
                            type: "art_link",
                            permissions: ["read", "update", "create"]
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure(artifact_values, structure);
                expect(output).toEqual({
                    655: {
                        field_id: 655,
                        type: "string",
                        permissions: ["read", "update", "create"],
                        value: "alumna Aurora Arpin"
                    },
                    728: {
                        field_id: 728,
                        bind_value_ids: [422],
                        type: "rb",
                        permissions: ["read", "update", "create"]
                    },
                    378: {
                        field_id: 378,
                        bind_value_ids: [667, 967],
                        type: "sb",
                        permissions: ["read", "update", "create"]
                    },
                    320: {
                        field_id: 320,
                        links: [
                            { id: 158 },
                            { id: 434 }
                        ],
                        type: "art_link",
                        permissions: ["read", "update", "create"]
                    }
                });
            });

            it("containing read-only fields such as aid, atid, lud, burndown, priority, subby, subon, computed, cross, tbl or perm and given a tracker structure object, when I create the model from the structure, then those fields won't have a value in the returned map", function() {
                var artifact_values = [
                    { field_id: 280, value: 271 },
                    { field_id: 973, value: 436 },
                    { field_id: 9, value: "2015-06-10T13:38:57+02:00" },
                    { field_id: 316, value: {
                        "duration": 85,
                        "capacity": 79,
                        "points": [11.52, 87.50, 70.65]
                    } },
                    { field_id: 188, value: 691 },
                    { field_id: 183, value: "Juli Devens" },
                    { field_id: 89, value: "2015-06-10T13:26:51+02:00" },
                    { field_id: 365, value: 123 },
                    { field_id: 906, value: [
                        {
                            ref: "story #973",
                            url: "https://onychotrophy.com/wealden/organing?a=pharmacometer&b=terribleness#viscid"
                        }
                    ]},
                    { field_id: 754, value: "" },
                    { field_id: 3, value: "" }
                ];
                var structure = {
                    fields: [
                        { field_id: 280, type: "aid" },
                        { field_id: 973, type: "atid" },
                        { field_id: 9, type: "lud" },
                        { field_id: 316, type: "burndown" },
                        { field_id: 188, type: "priority" },
                        { field_id: 183, type: "subby" },
                        { field_id: 89, type: "subon" },
                        { field_id: 365, type: "computed" },
                        { field_id: 906, type: "cross" },
                        { field_id: 754, type: "tbl" },
                        { field_id: 3, type: "perm" }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure(artifact_values, structure);
                expect(output).toEqual({
                    280: { field_id: 280, type: "aid" },
                    973: { field_id: 973, type: "atid" },
                    9: { field_id: 9, type: "lud" },
                    316: { field_id: 316, type: "burndown" },
                    188: { field_id: 188, type: "priority" },
                    183: { field_id: 183, type: "subby" },
                    89: { field_id: 89, type: "subon" },
                    365: { field_id: 365, type: "computed" },
                    906: { field_id: 906, type: "cross" },
                    754: { field_id: 754, type: "tbl" },
                    3: { field_id: 3, type: "perm" }
                });
            });

            it("containing a file field, and given said artifact had two attached files including an image, and given a tracker structure object, when I create the model from the structure, then the file field will have a file_descriptions attribute containing two objects and the attached image object's display_as_image attribute will be true", function() {
                var artifact_values = [
                    {
                        field_id: 719,
                        file_descriptions: [
                            {
                                type: "image/png",
                                somekey: "somevalue"
                            }, {
                                type: "text/xml",
                                someotherkey: "differentvalue"
                            }
                        ]
                    }
                ];
                var structure = {
                    fields: [
                        { field_id: 719, type: "file" }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure(artifact_values, structure);
                expect(output).toEqual({
                    719: { field_id: 719, type: "file" }
                });
                expect(structure).toEqual({
                    fields: [
                        {
                            field_id: 719,
                            type: "file",
                            file_descriptions: [
                                {
                                    type: "image/png",
                                    display_as_image: true,
                                    somekey: "somevalue"
                                }, {
                                    type: "text/xml",
                                    display_as_image: false,
                                    someotherkey: "differentvalue"
                                }
                            ]
                        }
                    ]
                });
            });
        });

        describe("Given a tracker structure object containing a string field,", function() {
            it("and that it didn't have a default value, when I create the model from the structure, then a map of objects containing the field's id and a null value will be returned", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 870,
                            label: "Mammilloid",
                            name: "coquelicot",
                            permissions: ["read update create"],
                            type: "string"
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    870: {
                        field_id: 870,
                        permissions: ["read update create"],
                        type: 'string',
                        value: null
                    }
                });
            });

            it("and that it had a default value, when I create the model from the structure, then a map of objects containing the field's id and its default value will be returned", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 175,
                            label: "cardiopneumograph",
                            name: "idolatrize",
                            permissions: ["read update create"],
                            type: "string",
                            default_value: "Despina Pistorius chronoisothermal"
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    175: {
                        field_id: 175,
                        permissions: ["read update create"],
                        type: 'string',
                        value: "Despina Pistorius chronoisothermal"
                    }
                });
            });
        });

        describe("Given a tracker structure object containing a text field,", function() {
            it("and given an array of artifact field values containing that field, when I create the model from the structure, then a map of objects containing the formatted artifact value will be returned", function() {
                var artifact_values = [
                    {
                        field_id: 901,
                        format: "HTML",
                        type: "text",
                        value: "<p><b>Cleta</b> Goetsch bicipital <em>xylophagid</em></p>"
                    }
                ];
                var structure = {
                    fields: [
                        {
                            field_id: 901,
                            label: "holard",
                            name: "flueless",
                            permissions: ["read update create"],
                            type: "text"
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure(artifact_values, structure);
                expect(output).toEqual({
                    901: {
                        field_id: 901,
                        type: "text",
                        permissions: ["read update create"],
                        value: {
                            content: "<p><b>Cleta</b> Goetsch bicipital <em>xylophagid</em></p>",
                            format: "HTML"
                        }
                    }
                });
            });

            it("and that it didn't have a default value, when I create the model from the structure, then a map of objects containing the field's id, the 'text' format and a null value", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 336,
                            label: "heritage",
                            name: "forbidder",
                            permissions: ["read update create"],
                            type: "text"
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    336: {
                        field_id: 336,
                        permissions: ["read update create"],
                        type: "text",
                        value: {
                            content: null,
                            format: "text"
                        }
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
                            permissions: ["read update create"],
                            type: "text",
                            default_value: {
                                format: "HTML",
                                content: "<p>quartane <b>Christel</b> Kalchik roentgentherapy</p>"
                            }
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    349: {
                        field_id: 349,
                        type: "text",
                        permissions: ["read update create"],
                        value: {
                            content: "<p>quartane <b>Christel</b> Kalchik roentgentherapy</p>",
                            format: "HTML"
                        }
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
                            permissions: ["read update create"],
                            type: "int"
                        }, {
                            field_id: 775,
                            label: "phalacrocoracine",
                            name: "unvariant",
                            permissions: ["read update create"],
                            type: "float"
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    685: {
                        field_id: 685,
                        type: 'int',
                        permissions: ["read update create"],
                        value: null
                    },
                    775: {
                        field_id: 775,
                        type: 'float',
                        permissions: ["read update create"],
                        value: null
                    }
                });
            });

            it("and that those fields had a default value, when I create the model from the structure, then a map of objects containing only the fields' id and their default value will be returned", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 163,
                            label: "urinocryoscopy",
                            name: "priestless",
                            permissions: ["read update create"],
                            type: "float",
                            default_value: "68.8596"
                        }, {
                            field_id: 220,
                            label: "formel",
                            name: "hodograph",
                            permissions: ["read update create"],
                            type: "int",
                            default_value: "236"
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    163: {
                        field_id: 163,
                        type: 'float',
                        permissions: ["read update create"],
                        value: 68.8596
                    },
                    220: {
                        field_id: 220,
                        type: 'int',
                        permissions: ["read update create"],
                        value: 236
                    }
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
                            permissions: ["read update create"],
                            type: "date"
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure(artifact_values, structure);
                expect($window.moment).toHaveBeenCalledWith("2015-05-29T00:00:00+02:00", $window.moment.ISO_8601);
                expect($window.moment().format).toHaveBeenCalledWith("YYYY-MM-DD");
                expect(output[824].field_id).toEqual(824);
                expect(output[824].permissions).toEqual(["read update create"]);
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
                            permissions: ["read update create"],
                            type: "date",
                            is_time_displayed: "true"
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure(artifact_values, structure);
                expect($window.moment).toHaveBeenCalledWith("2015-06-02T18:09:43+03:00", $window.moment.ISO_8601);
                expect($window.moment().format).toHaveBeenCalledWith("YYYY-MM-DD HH:mm:ss");
                expect(output[609].field_id).toEqual(609);
                expect(output[609].permissions).toEqual(["read update create"]);
            });
        });

        describe("Given a tracker structure object containing a selectbox field", function() {
            it("and given an array of artifact field values containing that field, when I create the model from the structure, then a map of objects containing the artifact values will be returned", function() {
                var artifact_values = [
                    {
                        field_id: 613,
                        bind_value_ids: [557]
                    }
                ];
                var structure = {
                    fields: [
                        {
                            field_id: 613,
                            label: "heritor",
                            name: "theow",
                            permissions: ["read update create"],
                            type: "sb"
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure(artifact_values, structure);
                expect(output).toEqual({
                    613: {
                        field_id: 613,
                        bind_value_ids: [557],
                        type: "sb",
                        permissions: ["read update create"]
                    }
                });
            });

            it("and that it didn't have a default value, when I create the model from the structure, then a map of objects containing the field's id and an empty bind_value_ids array will be returned", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 87,
                            label: "monarchist",
                            name: "artophorion",
                            permissions: ["read update create"],
                            type: "sb"
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    87:  {
                        field_id: 87,
                        bind_value_ids: [],
                        type: "sb",
                        permissions: ["read update create"]
                    }
                });
            });

            it("and that it had a default value, when I create the model from the structure, then a map of objects containing the field's id and a bind_value_ids array containing its default value will be returned", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 622,
                            label: "perfectionize",
                            name: "boatmaster",
                            permissions: ["read update create"],
                            type: "sb",
                            default_value: 941
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    622: {
                        field_id: 622,
                        bind_value_ids: [941],
                        type: "sb",
                        permissions: ["read update create"]
                    }
                });
            });

            it("and there were transitions defined in the structure for this field and given an array of artifact field values containing, when I create the model from the structure, then the field's selectable values in the structure will be only the available transitions value", function() {
                var artifact_values = [
                    {
                        field_id: 764,
                        bind_value_ids: [448]
                    }
                ];
                var structure = {
                    fields: [
                        {
                            field_id: 764,
                            label: "receptacular",
                            name: "skelp",
                            permissions: ["read update create"],
                            type: "sb",
                            values: [{id: 448}, {id: 6}, {id: 23}, {id: 908}, {id: 71}]
                        }
                    ],
                    workflow: {
                        field_id: 764,
                        is_used: "1",
                        transitions: [
                            {
                                "from_id": 448,
                                "to_id": 6
                            },
                            {
                                "from_id": 448,
                                "to_id": 23
                            },
                            {
                                "from_id": 908,
                                "to_id": 71
                            }
                        ]
                    }
                };
                TuleapArtifactModalModelFactory.createFromStructure(artifact_values, structure);
                expect(structure.fields[0].values).toEqual([{id: 448}, {id: 6}, {id: 23}]);
            });

            it("and there were transitions defined in the structure for this field, when I create the model from the structure, then I will have the proper transitions values in the selectbox (means dealing with null)", function() {

                var structure = {
                    fields: [
                        {
                            field_id: 764,
                            label: "receptacular",
                            name: "skelp",
                            permissions: ["read update create"],
                            type: "sb",
                            values: [{id: 448}, {id: 6}, {id: 23}, {id: 908}, {id: 71}]
                        }
                    ],
                    workflow: {
                        field_id: 764,
                        is_used: "1",
                        transitions: [
                            {
                                "from_id": null,
                                "to_id": 448
                            },
                            {
                                "from_id": null,
                                "to_id": 908

                            },
                            {
                                "from_id": 448,
                                "to_id": 6
                            },
                            {
                                "from_id": 448,
                                "to_id": 23
                            },
                            {
                                "from_id": 908,
                                "to_id": 71
                            }
                        ]
                    }
                };
                TuleapArtifactModalModelFactory.createFromStructure([], structure);
                expect(structure.fields[0].values).toEqual([{id: 448}, {id: 908}]);
            });

            it("bound to user group, when I create the model from the structure, then the values labels are internationalized", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 764,
                            label: "receptacular",
                            name: "skelp",
                            permissions: ["read update create"],
                            type: "sb",
                            values: [{
                                id: 448,
                                label: 'group_name',
                                ugroup_reference: {
                                    label: 'Group Name'
                                }
                            }]
                        }
                    ]
                };

                TuleapArtifactModalModelFactory.createFromStructure([], structure);
                expect(structure.fields[0].values[0].label).toEqual('Group Name');
            });
        });

        describe("Given a tracker structure object containing a multiselectbox field", function() {
            it("and given an array of artifact field values containing that field, when I create the model from the structure, then a map of objects containing the artifact value will be returned", function() {
                var artifact_values = [
                    {
                        field_id: 383,
                        bind_value_ids: [971, 679]
                    }
                ];
                var structure = {
                    fields: [
                        {
                            field_id: 383,
                            label: "hospodar",
                            name: "babyship",
                            permissions: ["read update create"],
                            type: "msb"
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure(artifact_values, structure);
                expect(output).toEqual({
                    383: {
                        field_id: 383,
                        bind_value_ids: [971, 679],
                        type: "msb",
                        permissions: ["read update create"]
                    }
                });
            });

            it("and that it didn't have a default value, when I create the model from the structure, then a map of objects containing the field's id and an empty bind_value_ids array will be returned", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 860,
                            label: "gorilline",
                            name: "beefer",
                            permissions: ["read update create"],
                            type: "msb"
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    860: {
                        field_id: 860,
                        bind_value_ids: [],
                        type: "msb",
                        permissions: ["read update create"]
                    }
                });
            });

            it("and that it had a default value, when I create the model from the structure, then a map of objects containing the field's id and a bind_value_ids array filled with the 2 default values will be returned", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 698,
                            label: "totaquin",
                            name: "sputumous",
                            permissions: ["read update create"],
                            type: "msb",
                            default_value: [196, 800]
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    698: {
                        field_id: 698,
                        bind_value_ids: [196, 800],
                        type: "msb",
                        permissions: ["read update create"]
                    }
                });
            });
        });

        describe("Given a tracker structure object containing a checkbox field with 3 possible values,", function() {
            it("and given an array of artifact field values containing that field, when I create the model from the structure, then a map of objects containing an array of 3 elements including the values in the artifact field value will be returned", function() {
                var artifact_values = [
                    { field_id: 137, type: "cb", bind_value_ids: [498, 443] }
                ];
                var structure = {
                    fields: [
                        {
                            field_id: 137,
                            label: "orthoveratric",
                            name: "daintith",
                            permissions: ["read update create"],
                            type: "cb",
                            values: [
                                { id: 498, label: "uncommendable" },
                                { id: 248, label: "Aleurodes" },
                                { id: 443, label: "thinglike" }
                            ]
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure(artifact_values, structure);
                expect(output).toEqual({
                    137: {
                        field_id: 137,
                        bind_value_ids: [498, null, 443],
                        type: "cb",
                        permissions: ["read update create"]
                    }
                });
            });

            it("and that it didn't have a default value, when I create the model from the structure, then a map of objects containing only the field's id and a bind_value_ids array filled with 3 nulls will be returned", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 607,
                            label: "visit",
                            name: "Narcobatidae",
                            permissions: ["read update create"],
                            type: "cb",
                            values: [
                                { id: 842, label: "mussal"},
                                { id: 733, label: "Nepenthaceae"},
                                { id: 833, label: "Vaticanize"}
                            ]
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    607: {
                        field_id: 607,
                        bind_value_ids: [null, null, null],
                        type: "cb",
                        permissions: ["read update create"]
                    }
                });
            });

            it("and that it had 2 default values, when I create the model from the structure, then a map of objects containing the field's id and a bind_value_ids array filled with the 2 default values and a null will be returned", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 910,
                            label: "transpirable",
                            name: "levolimonene",
                            permissions: ["read update create"],
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
                var output = TuleapArtifactModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    910: {
                        field_id: 910,
                        bind_value_ids: [477, null, 848],
                        type: "cb",
                        permissions: ["read update create"]
                    }
                });
            });
        });

        describe("Given a tracker structure object containing a radiobutton field,", function() {
            it("and given an array of artifact field values containing that field and that field's bind_value_ids array was empty, when I create the model from the structure, then a map of objects containing the field's id and a bind_value_ids array [100] will be returned", function() {
                var artifact_values = [
                    { field_id: 430, type: "rb", bind_value_ids: [] }
                ];
                var structure = {
                    fields: [
                        {
                            field_id: 430,
                            label: "parascene",
                            name: "gap",
                            permissions: ["read update create"],
                            type: "rb"
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure(artifact_values, structure);
                expect(output).toEqual({
                    430: {
                        field_id: 430,
                        bind_value_ids: [100],
                        type: "rb",
                        permissions: ["read update create"]
                    }
                });
            });

            it("and that it didn't have a default value, when I create the model from the structure, then a map of objects containing the field's id and a bind_value_ids array [100] will be returned", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 242,
                            label: "haruspicy",
                            name: "Taraktogenos",
                            permissions: ["read update create"],
                            type: "rb"
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    242: {
                        field_id: 242,
                        bind_value_ids: [100],
                        type: "rb",
                        permissions: ["read update create"]
                    }
                });
            });

            it("and that it had a default value, when I create the model from the structure, then a map of objects containing the field's id and an array of its default values will be returned", function() {
                var structure = {
                    fields: [
                        {
                            field_id: 897,
                            label: "healless",
                            name: "veiling",
                            permissions: ["read update create"],
                            type: "rb",
                            default_value: [931,410]
                        }
                    ]
                };
                var output = TuleapArtifactModalModelFactory.createFromStructure([], structure);
                expect(output).toEqual({
                    897: {
                        field_id: 897,
                        bind_value_ids: [931, 410],
                        type: "rb",
                        permissions: ["read update create"]
                    }
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
                        permissions: ["read update create"],
                        type: "art_link"
                    }
                ]
            };
            var output = TuleapArtifactModalModelFactory.createFromStructure([], structure);
            expect(output).toEqual({
                803: {
                    field_id: 803,
                    type: "art_link",
                    permissions: ["read update create"],
                    unformatted_links: "",
                    links: [ {id: ""} ]
                }
            });
        });
    });

    describe("reorderFieldsInGoodOrder() -", function() {
        var response, creation_mode;

        beforeEach(function() {
            response = {
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
                structure: []
            };
        });

        it("Given the modal was opened in creation mode, when I reorder the fields, then the form tree given by the tracker structure will be filled with the complete fields, augmented with the correct template_url and will be returned", function() {
            response.structure = [
                { id: 1, content: null },
                { id: 2, content: null },
                { id: 3, content: [
                    { id: 4, content: null },
                    { id: 5, content: [
                        { id: 6, content: null }
                    ]}
                ]}
            ];
            creation_mode = true;
            var output = TuleapArtifactModalModelFactory.reorderFieldsInGoodOrder(response, creation_mode);
            expect(output).toEqual([
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

        it("Given the modal was opened in creation mode, when I reorder the fields, then the awkward fields for creation mode (e.g. burndown, subby, subon) will be omitted from the returned form object", function() {
            response.structure = [
                { id: 1, content: null },
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
            ];
            creation_mode = true;
            var output = TuleapArtifactModalModelFactory.reorderFieldsInGoodOrder(response, creation_mode);
            expect(output).toEqual([
                {
                    field_id: 1,
                    type: "int",
                    template_url: 'field-int.tpl.html'
                }
            ]);
        });

        it("Given the modal was opened in edition mode, when I reorder the fields, then the awkward fields for creation mode WILL NOT be omitted from the returned form object", function() {
            response.structure = [
                { id: 1, content: null },
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
            ];
            creation_mode = false;
            var output = TuleapArtifactModalModelFactory.reorderFieldsInGoodOrder(response, creation_mode);
            expect(output).toEqual([
                {
                    field_id: 1,
                    type: "int",
                    template_url: 'field-int.tpl.html'
                }, {
                    field_id: 7,
                    type: "aid",
                    template_url: 'field-aid.tpl.html'
                }, {
                    field_id: 8,
                    type: "atid",
                    template_url: 'field-atid.tpl.html'
                }, {
                    field_id: 9,
                    type: "lud",
                    template_url: 'field-lud.tpl.html'
                }, {
                    field_id: 10,
                    type: "burndown",
                    template_url: 'field-burndown.tpl.html'
                }, {
                    field_id: 11,
                    type: "priority",
                    template_url: 'field-priority.tpl.html'
                }, {
                    field_id: 12,
                    type: "subby",
                    template_url: 'field-subby.tpl.html'
                }, {
                    field_id: 13,
                    type: "subon",
                    template_url: 'field-subon.tpl.html'
                }, {
                    field_id: 14,
                    type: "computed",
                    template_url: 'field-computed.tpl.html'
                }, {
                    field_id: 15,
                    type: "cross",
                    template_url: 'field-cross.tpl.html'
                }, {
                    field_id: 16,
                    type: "file",
                    template_url: 'field-file.tpl.html'
                }
            ]);
        });
    });
});
