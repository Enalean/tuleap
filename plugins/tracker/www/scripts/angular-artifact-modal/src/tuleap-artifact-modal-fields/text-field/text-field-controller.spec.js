/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import text_field from "./text-field.js";
import angular from "angular";
import "angular-mocks";
import BaseController from "./text-field-controller.js";

describe("TextFieldController -", () => {
    let TextFieldController;

    beforeEach(() => {
        angular.mock.module(text_field);

        let $controller;

        angular.mock.inject(function(_$controller_) {
            $controller = _$controller_;
        });

        TextFieldController = $controller(BaseController, {});
        TextFieldController.field = {};
        TextFieldController.value_model = {
            value: {
                format: null,
                content: null
            }
        };
        TextFieldController.isDisabled = jasmine.createSpy("isDisabled");
    });

    describe(`$onInit()`, () => {
        it(`sets a CKEditor configuration object
            with the editor set to readOnly when the field is disabled `, () => {
            TextFieldController.isDisabled.and.returnValue(true);

            TextFieldController.$onInit();
            const config = TextFieldController.ckeditor_config;

            expect(config.toolbar).toContain(["Bold", "Italic", "Underline"]);
            expect(config.toolbar).toContain([
                "NumberedList",
                "BulletedList",
                "-",
                "Blockquote",
                "Format"
            ]);
            expect(config.toolbar).toContain(["Link", "Unlink", "Anchor", "Image"]);
            expect(config.toolbar).toContain(["Source"]);
            expect(config.height).toBeDefined();
            expect(config.readOnly).toBe(true);
        });
    });

    describe(`isTextCurrentFormat()`, () => {
        it(`returns true when value format is "text"`, () => {
            TextFieldController.value_model.value.format = "text";

            expect(TextFieldController.isTextCurrentFormat()).toBe(true);
        });
    });

    describe(`isHTMLCurrentFormat()`, () => {
        it(`returns true when value format is "html"`, () => {
            TextFieldController.value_model.value.format = "html";

            expect(TextFieldController.isHTMLCurrentFormat()).toBe(true);
        });
    });

    describe(`isRequiredAndEmpty()`, () => {
        it(`returns true when the field is required and the value content is an empty string`, () => {
            TextFieldController.field.required = true;
            TextFieldController.value_model.value.content = "";

            expect(TextFieldController.isRequiredAndEmpty()).toBe(true);
        });
    });
});
