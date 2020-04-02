/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import model_module from "./model.js";
import angular from "angular";
import moment from "moment";
import "angular-mocks";

describe("TuleapArtifactFieldValuesService", () => {
    let FieldValuesService;
    beforeEach(() => {
        angular.mock.module(model_module);

        angular.mock.inject(function (_TuleapArtifactFieldValuesService_) {
            FieldValuesService = _TuleapArtifactFieldValuesService_;

            jest.spyOn(moment.fn, "format").mockImplementation(() => {});
        });
    });

    describe("getSelectedValues() -", () => {
        describe("Given a map of artifact field values", () => {
            it("and given a tracker containing those fields, when I get the fields' selected values, then a map containing all the fields provided and also containing default values for all the other fields of the tracker will be returned", () => {
                const artifact_values = {
                    655: { field_id: 655, value: "alumna Aurora Arpin" },
                    378: { field_id: 378, bind_value_ids: [667, 967] },
                    320: {
                        field_id: 320,
                        links: [{ id: 158 }, { id: 434 }],
                    },
                };
                const tracker = {
                    fields: [
                        {
                            field_id: 655,
                            label: "antithetically",
                            name: "arbusterol",
                            type: "string",
                            permissions: ["read", "update", "create"],
                            default_value: "yogasana",
                        },
                        {
                            field_id: 728,
                            label: "turus",
                            name: "hemicycle",
                            type: "rb",
                            permissions: ["read", "update", "create"],
                            default_value: [{ id: 422, label: "unilinear" }],
                        },
                        {
                            field_id: 378,
                            label: "overplay",
                            name: "awaredom",
                            type: "sb",
                            permissions: ["read", "update", "create"],
                            default_value: [{ id: 967, label: "intertransmission" }],
                        },
                        {
                            field_id: 320,
                            label: "rani",
                            name: "troot",
                            type: "art_link",
                            permissions: ["read", "update", "create"],
                        },
                    ],
                };

                const output = FieldValuesService.getSelectedValues(artifact_values, tracker);

                expect(output).toEqual({
                    655: {
                        field_id: 655,
                        type: "string",
                        permissions: ["read", "update", "create"],
                        value: "alumna Aurora Arpin",
                    },
                    728: {
                        field_id: 728,
                        bind_value_ids: [422],
                        type: "rb",
                        permissions: ["read", "update", "create"],
                    },
                    378: {
                        field_id: 378,
                        bind_value_ids: [667, 967],
                        type: "sb",
                        permissions: ["read", "update", "create"],
                    },
                    320: {
                        field_id: 320,
                        links: [{}],
                        unformatted_links: "158, 434",
                        type: "art_link",
                        permissions: ["read", "update", "create"],
                    },
                });
            });

            it("containing read-only fields such as aid, atid, lud, burndown, priority, subby, luby, subon, cross or tbl and given a tracker, when I get the fields' selected values, then those fields won't have a value in the returned map", function () {
                var artifact_values = {
                    280: { field_id: 280, value: 271 },
                    973: { field_id: 973, value: 436 },
                    9: { field_id: 9, value: "2015-06-10T13:38:57+02:00" },
                    316: {
                        field_id: 316,
                        value: {
                            duration: 85,
                            capacity: 79,
                            points: [11.52, 87.5, 70.65],
                        },
                    },
                    188: { field_id: 188, value: 691 },
                    183: { field_id: 183, value: "Juli Devens" },
                    586: { field_id: 586, value: "Gizmo Gremlin" },
                    89: { field_id: 89, value: "2015-06-10T13:26:51+02:00" },
                    906: {
                        field_id: 906,
                        value: [
                            {
                                ref: "story #973",
                                url:
                                    "https://onychotrophy.com/wealden/organing?a=pharmacometer&b=terribleness#viscid",
                            },
                        ],
                    },
                };
                var tracker = {
                    fields: [
                        { field_id: 280, type: "aid" },
                        { field_id: 973, type: "atid" },
                        { field_id: 9, type: "lud" },
                        { field_id: 316, type: "burndown" },
                        { field_id: 188, type: "priority" },
                        { field_id: 183, type: "subby" },
                        { field_id: 586, type: "luby" },
                        { field_id: 89, type: "subon" },
                        { field_id: 906, type: "cross" },
                    ],
                };
                var output = FieldValuesService.getSelectedValues(artifact_values, tracker);
                expect(output).toEqual({
                    280: { field_id: 280, type: "aid" },
                    973: { field_id: 973, type: "atid" },
                    9: { field_id: 9, type: "lud" },
                    316: { field_id: 316, type: "burndown" },
                    188: { field_id: 188, type: "priority" },
                    183: { field_id: 183, type: "subby" },
                    586: { field_id: 586, type: "luby" },
                    89: { field_id: 89, type: "subon" },
                    906: { field_id: 906, type: "cross" },
                });
            });
        });

        describe("Given a tracker containing a string field,", function () {
            it("and that it didn't have a default value, when I get the fields' selected values, then a map of objects containing the field's id and a null value will be returned", function () {
                var tracker = {
                    fields: [
                        {
                            field_id: 870,
                            label: "Mammilloid",
                            name: "coquelicot",
                            permissions: ["read", "update", "create"],
                            type: "string",
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues({}, tracker);
                expect(output).toEqual({
                    870: {
                        field_id: 870,
                        permissions: ["read", "update", "create"],
                        type: "string",
                        value: null,
                    },
                });
            });

            it("and that it had a default value, when I get the fields' selected values, then a map of objects containing the field's id and its default value will be returned", function () {
                var tracker = {
                    fields: [
                        {
                            field_id: 175,
                            label: "cardiopneumograph",
                            name: "idolatrize",
                            permissions: ["read", "update", "create"],
                            type: "string",
                            default_value: "Despina Pistorius chronoisothermal",
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues([], tracker);
                expect(output).toEqual({
                    175: {
                        field_id: 175,
                        permissions: ["read", "update", "create"],
                        type: "string",
                        value: "Despina Pistorius chronoisothermal",
                    },
                });
            });
        });

        describe("Given a tracker containing a text field,", function () {
            it("and given a map of artifact field values containing that field, when I get the fields' selected values, then a map of objects containing the formatted artifact value will be returned", function () {
                var artifact_values = {
                    901: {
                        field_id: 901,
                        format: "HTML",
                        type: "text",
                        value: "<p><b>Cleta</b> Goetsch bicipital <em>xylophagid</em></p>",
                    },
                };
                var tracker = {
                    fields: [
                        {
                            field_id: 901,
                            label: "holard",
                            name: "flueless",
                            permissions: ["read", "update", "create"],
                            type: "text",
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues(artifact_values, tracker);
                expect(output).toEqual({
                    901: {
                        field_id: 901,
                        type: "text",
                        permissions: ["read", "update", "create"],
                        value: {
                            content: "<p><b>Cleta</b> Goetsch bicipital <em>xylophagid</em></p>",
                            format: "HTML",
                        },
                    },
                });
            });

            it("and that it didn't have a default value, when I get the fields' selected values, then a map of objects containing the field's id, the 'text' format and a null value", function () {
                var tracker = {
                    fields: [
                        {
                            field_id: 336,
                            label: "heritage",
                            name: "forbidder",
                            permissions: ["read", "update", "create"],
                            type: "text",
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues({}, tracker);
                expect(output).toEqual({
                    336: {
                        field_id: 336,
                        permissions: ["read", "update", "create"],
                        type: "text",
                        value: {
                            content: null,
                            format: "text",
                        },
                    },
                });
            });

            it("and that it had a default value, when I get the fields' selected values, then a map of objects containing the field's id, the default format and the default value", function () {
                var tracker = {
                    fields: [
                        {
                            field_id: 349,
                            label: "excoriator",
                            name: "phratrial",
                            permissions: ["read", "update", "create"],
                            type: "text",
                            default_value: {
                                format: "HTML",
                                content: "<p>quartane <b>Christel</b> Kalchik roentgentherapy</p>",
                            },
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues({}, tracker);
                expect(output).toEqual({
                    349: {
                        field_id: 349,
                        type: "text",
                        permissions: ["read", "update", "create"],
                        value: {
                            content: "<p>quartane <b>Christel</b> Kalchik roentgentherapy</p>",
                            format: "HTML",
                        },
                    },
                });
            });
        });

        describe("Given a tracker containing an int field and a float field,", function () {
            it("and that those fields didn't have a default value, when I get the fields' selected values, then a map of objects containing only the fields' id and a null value will be returned", function () {
                var tracker = {
                    fields: [
                        {
                            field_id: 685,
                            label: "raiiform",
                            name: "loft",
                            permissions: ["read", "update", "create"],
                            type: "int",
                        },
                        {
                            field_id: 775,
                            label: "phalacrocoracine",
                            name: "unvariant",
                            permissions: ["read", "update", "create"],
                            type: "float",
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues({}, tracker);
                expect(output).toEqual({
                    685: {
                        field_id: 685,
                        type: "int",
                        permissions: ["read", "update", "create"],
                        value: null,
                    },
                    775: {
                        field_id: 775,
                        type: "float",
                        permissions: ["read", "update", "create"],
                        value: null,
                    },
                });
            });

            it("and that those fields had a default value, when I get the fields' selected values, then a map of objects containing only the fields' id and their default value will be returned", function () {
                var tracker = {
                    fields: [
                        {
                            field_id: 163,
                            label: "urinocryoscopy",
                            name: "priestless",
                            permissions: ["read", "update", "create"],
                            type: "float",
                            default_value: "68.8596",
                        },
                        {
                            field_id: 220,
                            label: "formel",
                            name: "hodograph",
                            permissions: ["read", "update", "create"],
                            type: "int",
                            default_value: "236",
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues({}, tracker);
                expect(output).toEqual({
                    163: {
                        field_id: 163,
                        type: "float",
                        permissions: ["read", "update", "create"],
                        value: 68.8596,
                    },
                    220: {
                        field_id: 220,
                        type: "int",
                        permissions: ["read", "update", "create"],
                        value: 236,
                    },
                });
                expect(typeof output[163].value).toBe("number");
                expect(typeof output[220].value).toBe("number");
            });
        });

        describe("Given a tracker containing a date field", function () {
            it("without the time displayed and given a map of artifact field values containing that field, when I get the fields' selected values, then a map of objects containing the formatted artifact value will be returned", function () {
                var artifact_values = {
                    824: { field_id: 824, value: "2015-05-29T00:00:00+02:00" },
                };
                var tracker = {
                    fields: [
                        {
                            field_id: 824,
                            label: "nondrying",
                            name: "indisciplined",
                            permissions: ["read", "update", "create"],
                            type: "date",
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues(artifact_values, tracker);
                expect(moment.fn.format).toHaveBeenCalledWith("YYYY-MM-DD");
                expect(output[824].field_id).toEqual(824);
                expect(output[824].permissions).toEqual(["read", "update", "create"]);
            });

            it("with the time displayed and given a map of artifact field values containing that field, when I get the fields' selected values, then a map of objects containing the formatted artifact value will be returned", function () {
                var artifact_values = {
                    609: { field_id: 609, value: "2015-06-02T18:09:43+03:00" },
                };
                var tracker = {
                    fields: [
                        {
                            field_id: 609,
                            label: "",
                            name: "",
                            permissions: ["read", "update", "create"],
                            type: "date",
                            is_time_displayed: "true",
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues(artifact_values, tracker);
                expect(moment.fn.format).toHaveBeenCalledWith("YYYY-MM-DD HH:mm");
                expect(output[609].field_id).toEqual(609);
                expect(output[609].permissions).toEqual(["read", "update", "create"]);
            });
        });

        describe("Given a tracker containing a selectbox field", function () {
            it("and given a map of artifact field values containing that field, when I get the fields' selected values, then a map of objects containing the artifact values will be returned", function () {
                var artifact_values = {
                    613: {
                        field_id: 613,
                        bind_value_ids: [557],
                    },
                };
                var tracker = {
                    fields: [
                        {
                            field_id: 613,
                            label: "heritor",
                            name: "theow",
                            permissions: ["read", "update", "create"],
                            type: "sb",
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues(artifact_values, tracker);
                expect(output).toEqual({
                    613: {
                        field_id: 613,
                        bind_value_ids: [557],
                        type: "sb",
                        permissions: ["read", "update", "create"],
                    },
                });
            });

            it("and that it didn't have a default value, when I get the fields' selected values, then a map of objects containing the field's id and bind_value_ids array [100] will be returned", function () {
                var tracker = {
                    fields: [
                        {
                            field_id: 87,
                            label: "monarchist",
                            name: "artophorion",
                            permissions: ["read", "update", "create"],
                            type: "sb",
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues({}, tracker);
                expect(output).toEqual({
                    87: {
                        field_id: 87,
                        bind_value_ids: [100],
                        type: "sb",
                        permissions: ["read", "update", "create"],
                    },
                });
            });

            it("and that it had a default value, when I get the fields' selected values, then a map of objects containing the field's id and a bind_value_ids array containing its default value will be returned", function () {
                var tracker = {
                    fields: [
                        {
                            field_id: 622,
                            label: "perfectionize",
                            name: "boatmaster",
                            permissions: ["read", "update", "create"],
                            type: "sb",
                            default_value: [{ id: 941, label: "hair" }],
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues({}, tracker);
                expect(output).toEqual({
                    622: {
                        field_id: 622,
                        bind_value_ids: [941],
                        type: "sb",
                        permissions: ["read", "update", "create"],
                    },
                });
            });

            it("and that it had a default value that wasn't in the available transitions values, when I get the fields' selected values, then a map of objects containing the field's id and a bind_value_ids array containing the first available transition value will be returned", function () {
                var tracker = {
                    fields: [
                        {
                            field_id: 90,
                            label: "polycythemic",
                            name: "university",
                            permissions: ["read", "update", "create"],
                            type: "sb",
                            default_value: [{ id: 807, label: "uniflow" }],
                            values: [
                                { id: 412, label: "entosphenoid" },
                                { id: 182, label: "trisul" },
                            ],
                            has_transitions: true,
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues({}, tracker);
                expect(output).toEqual({
                    90: {
                        field_id: 90,
                        bind_value_ids: [412],
                        type: "sb",
                        permissions: ["read", "update", "create"],
                    },
                });
            });
        });

        describe("Given a tracker containing a multiselectbox field", function () {
            it("and given a map of artifact field values containing that field, when I get the fields' selected values, then a map of objects containing the artifact value will be returned", function () {
                var artifact_values = {
                    383: {
                        field_id: 383,
                        bind_value_ids: [971, 679],
                    },
                };
                var tracker = {
                    fields: [
                        {
                            field_id: 383,
                            label: "hospodar",
                            name: "babyship",
                            permissions: ["read", "update", "create"],
                            type: "msb",
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues(artifact_values, tracker);
                expect(output).toEqual({
                    383: {
                        field_id: 383,
                        bind_value_ids: [971, 679],
                        type: "msb",
                        permissions: ["read", "update", "create"],
                    },
                });
            });

            it("and that it didn't have a default value, when I get the fields' selected values, then a map of objects containing the field's id and a bind_value_ids array [100] will be returned", function () {
                var tracker = {
                    fields: [
                        {
                            field_id: 860,
                            label: "gorilline",
                            name: "beefer",
                            permissions: ["read", "update", "create"],
                            type: "msb",
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues({}, tracker);
                expect(output).toEqual({
                    860: {
                        field_id: 860,
                        bind_value_ids: [100],
                        type: "msb",
                        permissions: ["read", "update", "create"],
                    },
                });
            });

            it("and that it had a default value, when I get the fields' selected values, then a map of objects containing the field's id and a bind_value_ids array filled with the 2 default values will be returned", function () {
                var tracker = {
                    fields: [
                        {
                            field_id: 698,
                            label: "totaquin",
                            name: "sputumous",
                            permissions: ["read", "update", "create"],
                            type: "msb",
                            default_value: [
                                { id: 196, label: "Polythalamia" },
                                { id: 800, label: "teleanemograph" },
                            ],
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues({}, tracker);
                expect(output).toEqual({
                    698: {
                        field_id: 698,
                        bind_value_ids: [196, 800],
                        type: "msb",
                        permissions: ["read", "update", "create"],
                    },
                });
            });
        });

        describe("Given a tracker containing a checkbox field with 3 possible values,", function () {
            it("and given a map of artifact field values containing that field, when I get the fields' selected values, then a map of objects containing an array of 3 elements including the values in the artifact field value will be returned", function () {
                var artifact_values = {
                    137: { field_id: 137, type: "cb", bind_value_ids: [498, 443] },
                };
                var tracker = {
                    fields: [
                        {
                            field_id: 137,
                            label: "orthoveratric",
                            name: "daintith",
                            permissions: ["read", "update", "create"],
                            type: "cb",
                            values: [
                                { id: 498, label: "uncommendable", is_hidden: false },
                                { id: 248, label: "Aleurodes", is_hidden: false },
                                { id: 443, label: "thinglike", is_hidden: false },
                            ],
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues(artifact_values, tracker);
                expect(output).toEqual({
                    137: {
                        field_id: 137,
                        bind_value_ids: [498, null, 443],
                        type: "cb",
                        permissions: ["read", "update", "create"],
                    },
                });
            });

            it("and that it didn't have a default value, when I get the fields' selected values, then a map of objects containing only the field's id and a bind_value_ids array filled with 3 nulls will be returned", function () {
                var tracker = {
                    fields: [
                        {
                            field_id: 607,
                            label: "visit",
                            name: "Narcobatidae",
                            permissions: ["read", "update", "create"],
                            type: "cb",
                            values: [
                                { id: 842, label: "mussal", is_hidden: false },
                                { id: 733, label: "Nepenthaceae", is_hidden: false },
                                { id: 833, label: "Vaticanize", is_hidden: false },
                            ],
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues({}, tracker);
                expect(output).toEqual({
                    607: {
                        field_id: 607,
                        bind_value_ids: [null, null, null],
                        type: "cb",
                        permissions: ["read", "update", "create"],
                    },
                });
            });

            it("and that it had 2 default values, when I get the fields' selected values, then a map of objects containing the field's id and a bind_value_ids array filled with the 2 default values and a null will be returned", function () {
                var tracker = {
                    fields: [
                        {
                            field_id: 910,
                            label: "transpirable",
                            name: "levolimonene",
                            permissions: ["read", "update", "create"],
                            type: "cb",
                            values: [
                                { id: 477, label: "Reuel", is_hidden: false },
                                { id: 440, label: "espalier", is_hidden: false },
                                { id: 848, label: "overtrust", is_hidden: false },
                            ],
                            default_value: [
                                { id: 477, label: "crestfallen", is_hidden: false },
                                { id: 848, label: "Afrikanderism", is_hidden: false },
                            ],
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues({}, tracker);
                expect(output).toEqual({
                    910: {
                        field_id: 910,
                        bind_value_ids: [477, null, 848],
                        type: "cb",
                        permissions: ["read", "update", "create"],
                    },
                });
            });
        });

        describe("Given a tracker containing a radiobutton field,", function () {
            it("and given a map of artifact field values containing that field and that field's bind_value_ids array was empty, when I get the fields' selected values, then a map of objects containing the field's id and a bind_value_ids array [100] will be returned", function () {
                var artifact_values = {
                    430: { field_id: 430, type: "rb", bind_value_ids: [] },
                };
                var tracker = {
                    fields: [
                        {
                            field_id: 430,
                            label: "parascene",
                            name: "gap",
                            permissions: ["read", "update", "create"],
                            type: "rb",
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues(artifact_values, tracker);
                expect(output).toEqual({
                    430: {
                        field_id: 430,
                        bind_value_ids: [100],
                        type: "rb",
                        permissions: ["read", "update", "create"],
                    },
                });
            });

            it("and that it didn't have a default value, when I get the fields' selected values, then a map of objects containing the field's id and a bind_value_ids array [100] will be returned", function () {
                var tracker = {
                    fields: [
                        {
                            field_id: 242,
                            label: "haruspicy",
                            name: "Taraktogenos",
                            permissions: ["read", "update", "create"],
                            type: "rb",
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues({}, tracker);
                expect(output).toEqual({
                    242: {
                        field_id: 242,
                        bind_value_ids: [100],
                        type: "rb",
                        permissions: ["read", "update", "create"],
                    },
                });
            });

            it("and that it had a default value, when I get the fields' selected values, then a map of objects containing the field's id and an array of its default values will be returned", function () {
                var tracker = {
                    fields: [
                        {
                            field_id: 897,
                            label: "healless",
                            name: "veiling",
                            permissions: ["read", "update", "create"],
                            type: "rb",
                            default_value: [
                                { id: 931, label: "parisyllabic", is_hidden: false },
                                { id: 410, label: "prosodiacal", is_hidden: false },
                            ],
                        },
                    ],
                };
                var output = FieldValuesService.getSelectedValues({}, tracker);
                expect(output).toEqual({
                    897: {
                        field_id: 897,
                        bind_value_ids: [931, 410],
                        type: "rb",
                        permissions: ["read", "update", "create"],
                    },
                });
            });
        });

        it("Given a tracker containing an artifact links field, when I get the fields' selected values, then a map of objects containing the fields' id, an empty string that will contain the list of ids to link to and an empty links array will be returned", function () {
            var tracker = {
                fields: [
                    {
                        field_id: 803,
                        label: "inspectrix",
                        name: "isonomic",
                        permissions: ["read", "update", "create"],
                        type: "art_link",
                    },
                ],
            };
            var output = FieldValuesService.getSelectedValues({}, tracker);
            expect(output).toEqual({
                803: {
                    field_id: 803,
                    type: "art_link",
                    permissions: ["read", "update", "create"],
                    unformatted_links: "",
                    links: [{ id: "" }],
                },
            });
        });
    });

    describe("Given a tracker containing a permissions field,", function () {
        it("and given a map of artifact field values containing that field, when I get the fields' selected values, then a map of objects containing the artifact's granted groups and is_used_by_default will be returned", function () {
            var artifact_values = {
                904: {
                    field_id: 904,
                    granted_groups_ids: ["2", "101_3", "103"],
                },
            };
            var tracker = {
                fields: [
                    {
                        field_id: 904,
                        label: "unrenownedly",
                        name: "recolonize",
                        permissions: ["read", "update", "create"],
                        type: "perm",
                        values: {
                            is_used_by_default: false,
                        },
                    },
                ],
            };
            var output = FieldValuesService.getSelectedValues(artifact_values, tracker);
            expect(output).toEqual({
                904: {
                    field_id: 904,
                    type: "perm",
                    permissions: ["read", "update", "create"],
                    value: {
                        is_used_by_default: false,
                        granted_groups: ["2", "101_3", "103"],
                    },
                },
            });
        });

        it("when I get the fields' selected values, then a map of objects containing the field's id and value with an empty granted_groups array and is_used_by_default will be returned", function () {
            var tracker = {
                fields: [
                    {
                        field_id: 662,
                        label: "disprobabilization",
                        name: "melanosed",
                        permissions: ["read", "update", "create"],
                        type: "perm",
                        values: {
                            is_used_by_default: true,
                        },
                    },
                ],
            };
            var output = FieldValuesService.getSelectedValues({}, tracker);
            expect(output).toEqual({
                662: {
                    field_id: 662,
                    type: "perm",
                    permissions: ["read", "update", "create"],
                    value: {
                        is_used_by_default: true,
                        granted_groups: [],
                    },
                },
            });
        });
    });

    describe("Given a tracker containing a file field,", () => {
        it(`and given a map of artifact field values containing that field,
            when I get the fields' selected values,
            then a map of objects containing the artifact's value will be returned`, () => {
            const artifact_values = {
                103: {
                    field_id: 103,
                    file_descriptions: [{ id: 4 }, { id: 9 }],
                    type: "file",
                },
            };
            const tracker = {
                fields: [
                    {
                        field_id: 103,
                        label: "sevenbark",
                        name: "Aglypha",
                        permissions: ["read", "update", "create"],
                        type: "file",
                    },
                ],
            };
            const output = FieldValuesService.getSelectedValues(artifact_values, tracker);
            expect(output).toEqual({
                103: {
                    field_id: 103,
                    file_descriptions: [{ id: 4 }, { id: 9 }],
                    type: "file",
                    images_added_by_text_fields: [],
                    temporary_files: [
                        {
                            file: {},
                            description: "",
                        },
                    ],
                    permissions: ["read", "update", "create"],
                    value: [4, 9],
                },
            });
        });

        it(`when I get the fields' selected values,
            then a map of objects containing the fields' id
            and an empty value array will be returned`, () => {
            const tracker = {
                fields: [
                    {
                        field_id: 542,
                        label: "Afrasia",
                        name: "gridelin",
                        permissions: ["read", "update", "create"],
                        type: "file",
                    },
                ],
            };
            const output = FieldValuesService.getSelectedValues({}, tracker);
            expect(output).toEqual({
                542: {
                    field_id: 542,
                    type: "file",
                    images_added_by_text_fields: [],
                    temporary_files: [
                        {
                            file: {},
                            description: "",
                        },
                    ],
                    permissions: ["read", "update", "create"],
                    value: [],
                },
            });
        });
    });

    describe("Given a tracker containing a computed field,", function () {
        it("and given a map of artifact field values containing that field, when I get the field's selected values, then a map of objects containing the fields' id and its value will be returned", function () {
            const artifact_values = {
                665: {
                    field_id: 665,
                    is_autocomputed: false,
                    manual_value: 5,
                    type: "computed",
                    value: null,
                },
            };
            const tracker = {
                fields: [
                    {
                        field_id: 665,
                        label: "propolis",
                        name: "chrysobull",
                        permissions: ["read", "update", "create"],
                        type: "computed",
                    },
                ],
            };
            const output = FieldValuesService.getSelectedValues(artifact_values, tracker);
            expect(output).toEqual({
                665: {
                    field_id: 665,
                    is_autocomputed: false,
                    manual_value: 5,
                    permissions: ["read", "update", "create"],
                    type: "computed",
                },
            });
        });

        it("when I get the fields' selected values, then a map of objects containing the fields' id, the is_autocomputed property set to true, and manual_value set to null will be returned", function () {
            const tracker = {
                fields: [
                    {
                        field_id: 304,
                        label: "pommey",
                        name: "peepy",
                        permissions: ["read", "update", "create"],
                        type: "computed",
                        default_value: null,
                    },
                ],
            };
            const output = FieldValuesService.getSelectedValues({}, tracker);
            expect(output).toEqual({
                304: {
                    field_id: 304,
                    is_autocomputed: true,
                    permissions: ["read", "update", "create"],
                    type: "computed",
                    manual_value: null,
                },
            });
        });

        it("when I get the fields' default values and a default value is set, then a map of objects containing the fields' id, the is_autocomputed property set to false, and manual_value set to the float value will be returned", function () {
            const tracker = {
                fields: [
                    {
                        field_id: 304,
                        label: "pommey",
                        name: "peepy",
                        permissions: ["read", "update", "create"],
                        type: "computed",
                        default_value: {
                            type: "manual_value",
                            value: 4.2,
                        },
                    },
                ],
            };
            const output = FieldValuesService.getSelectedValues({}, tracker);
            expect(output).toEqual({
                304: {
                    field_id: 304,
                    is_autocomputed: false,
                    permissions: ["read", "update", "create"],
                    type: "computed",
                    manual_value: 4.2,
                },
            });
        });

        it("when I get the fields' default values and a default value is not set, then a map of objects containing the fields' id, the is_autocomputed property set to true, and manual_value set to null will be returned", function () {
            const tracker = {
                fields: [
                    {
                        field_id: 304,
                        label: "pommey",
                        name: "peepy",
                        permissions: ["read", "update", "create"],
                        type: "computed",
                        default_value: null,
                    },
                ],
            };
            const output = FieldValuesService.getSelectedValues({}, tracker);
            expect(output).toEqual({
                304: {
                    field_id: 304,
                    is_autocomputed: true,
                    permissions: ["read", "update", "create"],
                    type: "computed",
                    manual_value: null,
                },
            });
        });
    });

    describe("Given a tracker containing an openlist field,", () => {
        it("and given a map of artifact field values containing that field, when I get the fields' selected values, then a map of objects containing the artifact's bind_value_objects will be returned", () => {
            const artifact_values = {
                319: {
                    field_id: 319,
                    bind_value_objects: [
                        {
                            id: 689,
                            label: "periscopism",
                            is_hidden: false,
                        },
                        {
                            id: 145,
                            label: "distinguisher",
                            is_hidden: false,
                        },
                    ],
                    bind_value_ids: ["periscopism", "distinguisher"],
                },
            };
            const tracker = {
                fields: [
                    {
                        field_id: 319,
                        bindings: {
                            type: "static",
                        },
                        label: "sneeshing",
                        name: "developoid",
                        permissions: ["read", "update", "create"],
                        type: "tbl",
                        values: [
                            { id: 689, label: "periscopism", is_hidden: false },
                            { id: 145, label: "distinguisher", is_hidden: false },
                        ],
                    },
                ],
            };
            const output = FieldValuesService.getSelectedValues(artifact_values, tracker);
            expect(output).toEqual({
                319: {
                    field_id: 319,
                    type: "tbl",
                    permissions: ["read", "update", "create"],
                    bindings: { type: "static" },
                    value: {
                        bind_value_objects: [
                            {
                                id: 689,
                                label: "periscopism",
                                is_hidden: false,
                            },
                            {
                                id: 145,
                                label: "distinguisher",
                                is_hidden: false,
                            },
                        ],
                    },
                },
            });
        });

        it("and that it didn't have a default value, when I get the fields' selected values, then a map of objects containing the field will be returned", () => {
            const tracker = {
                fields: [
                    {
                        field_id: 378,
                        bindings: {
                            type: "static",
                        },
                        label: "nonfriction",
                        name: "ablactation",
                        permissions: ["read", "update", "create"],
                        type: "tbl",
                        values: [
                            { id: 216, label: "phenaceturic", is_hidden: false },
                            { id: 801, label: "undershrieve", is_hidden: false },
                        ],
                    },
                ],
            };
            const output = FieldValuesService.getSelectedValues({}, tracker);
            expect(output).toEqual({
                378: {
                    field_id: 378,
                    type: "tbl",
                    permissions: ["read", "update", "create"],
                    bindings: { type: "static" },
                    value: {
                        bind_value_objects: [],
                    },
                },
            });
        });

        it("and that it had 2 default values, when I get the fields' selected values, then a map of objects containing the field's id and bind_value_objects array filled with the 2 default values will be returned", () => {
            const tracker = {
                fields: [
                    {
                        field_id: 667,
                        bindings: {
                            type: "static",
                        },
                        label: "nonfriction",
                        name: "ablactation",
                        permissions: ["read", "update", "create"],
                        type: "tbl",
                        values: [
                            { id: 378, label: "Linda", is_hidden: false },
                            { id: 544, label: "squamosomaxillary", is_hidden: false },
                        ],
                        default_value: [
                            { id: 378, label: "Linda", is_hidden: false },
                            { id: 544, label: "squamosomaxillary", is_hidden: false },
                        ],
                    },
                ],
            };
            const output = FieldValuesService.getSelectedValues({}, tracker);
            expect(output).toEqual({
                667: {
                    field_id: 667,
                    type: "tbl",
                    permissions: ["read", "update", "create"],
                    bindings: { type: "static" },
                    value: {
                        bind_value_objects: [
                            { id: 378, label: "Linda", is_hidden: false },
                            { id: 544, label: "squamosomaxillary", is_hidden: false },
                        ],
                    },
                },
            });
        });
    });
});
