/*
 * Copyright (c) Enalean, 2017-present. All Rights Reserved.
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

import artifact_modal_module from "./tuleap-artifact-modal.js";
import angular from "angular";
import "angular-mocks";

describe("TuleapArtifactModalValidateService validateArtifactFieldsValues() -", () => {
    let ValidateService,
        creation_mode,
        followup_value_model = {};
    beforeEach(function () {
        angular.mock.module(artifact_modal_module);

        angular.mock.inject(function (_TuleapArtifactModalValidateService_) {
            ValidateService = _TuleapArtifactModalValidateService_;
        });
    });

    describe("Given the modal was opened in edition mode", function () {
        beforeEach(function () {
            creation_mode = false;
        });

        it("and given an array containing field data including read-only and create-only fields, when I validate the fields data, then an object containing only fields whose permissions include 'update' will be returned", function () {
            var input = [
                {
                    field_id: 620,
                    permissions: ["read"],
                    value: "Meg Hinkston",
                },
                {
                    field_id: 17,
                    permissions: ["create"],
                    value: "Sharen Wikstrom",
                },
                {
                    field_id: 503,
                    permissions: ["update"],
                    value: "Kasie Steppello",
                },
            ];
            var output = ValidateService.validateArtifactFieldsValues(
                input,
                creation_mode,
                followup_value_model
            );
            expect(output).toEqual([{ field_id: 503, value: "Kasie Steppello" }]);
        });

        it("and given an array containing field data including empty string, null and undefined values, when I validate the fields data, then an object containing only fields whose value is defined will be returned", function () {
            var input = [
                {
                    field_id: 422,
                    permissions: ["update"],
                    value: null,
                },
                {
                    field_id: 967,
                    permissions: ["update"],
                    value: "petrogenic",
                },
                {
                    field_id: 768,
                    permissions: ["update"],
                    value: undefined,
                },
                {
                    field_id: 847,
                    permissions: ["update"],
                    value: 1.37765,
                },
                {
                    field_id: 328,
                    permissions: ["update"],
                    value: "",
                },
                {
                    field_id: 898,
                    permissions: ["update"],
                },
            ];
            var output = ValidateService.validateArtifactFieldsValues(
                input,
                creation_mode,
                followup_value_model
            );
            expect(output).toEqual([
                { field_id: 422, value: null },
                { field_id: 967, value: "petrogenic" },
                { field_id: 847, value: 1.37765 },
                { field_id: 328, value: "" },
            ]);
        });
    });

    describe("Given the modal was opened in creation mode", function () {
        beforeEach(function () {
            creation_mode = true;
        });

        it("and given an array containing field data including read-only and update-only fields, when I validate the fields data, then an object containing only fields whose permissions include 'create' will be returned", function () {
            var input = [
                {
                    field_id: 907,
                    permissions: ["read"],
                    value: "Claud Shein",
                },
                {
                    field_id: 939,
                    permissions: ["create"],
                    value: "Tyesha Schatzman",
                },
                {
                    field_id: 597,
                    permissions: ["update"],
                    value: "Malorie Labossiere",
                },
            ];
            var output = ValidateService.validateArtifactFieldsValues(
                input,
                creation_mode,
                followup_value_model
            );
            expect(output).toEqual([{ field_id: 939, value: "Tyesha Schatzman" }]);
        });

        it("and given an array containing date, int, fload and string fields with null values, when I validate the fields data, then an object containing all the fields' values as empty strings will be returned", function () {
            var input = [
                {
                    field_id: 54,
                    type: "int",
                    permissions: ["create"],
                    value: null,
                },
                {
                    field_id: 257,
                    type: "float",
                    permissions: ["create"],
                    value: null,
                },
                {
                    field_id: 195,
                    type: "date",
                    permissions: ["create"],
                    value: null,
                },
                {
                    field_id: 461,
                    type: "string",
                    permissions: ["create"],
                    value: null,
                },
            ];
            var output = ValidateService.validateArtifactFieldsValues(
                input,
                creation_mode,
                followup_value_model
            );
            expect(output).toEqual([
                { field_id: 54, value: "" },
                { field_id: 257, value: "" },
                { field_id: 195, value: "" },
                { field_id: 461, value: "" },
            ]);
        });

        it("and given an array containing selectboxes or multiselectboxes or checkboxes fields, when I validate the fields data, then an object containing only fields whose bind_value_ids are not empty or null will be returned", function () {
            var input = [
                {
                    field_id: 87,
                    bind_value_ids: null,
                    permissions: ["create"],
                },
                {
                    field_id: 597,
                    bind_value_ids: [],
                    permissions: ["create"],
                },
                {
                    field_id: 785,
                    bind_value_ids: [787, 857],
                    permissions: ["create"],
                },
                {
                    field_id: 180,
                    permissions: ["create"],
                },
            ];
            var output = ValidateService.validateArtifactFieldsValues(
                input,
                creation_mode,
                followup_value_model
            );
            expect(output).toEqual([
                { field_id: 597, bind_value_ids: [] },
                { field_id: 785, bind_value_ids: [787, 857] },
            ]);
        });

        it("and given an array containing a checkbox field and given that its bind_value_ids contains null and undefined values, when I validate the fields, then an object containing this field with a bind_value_ids containing only non-null integers will be returned", function () {
            var input = [
                {
                    field_id: 643,
                    bind_value_ids: [undefined, 840, null, 959],
                    permissions: ["create"],
                },
            ];
            var output = ValidateService.validateArtifactFieldsValues(
                input,
                creation_mode,
                followup_value_model
            );
            expect(output).toEqual([{ field_id: 643, bind_value_ids: [840, 959] }]);
        });
    });
});
