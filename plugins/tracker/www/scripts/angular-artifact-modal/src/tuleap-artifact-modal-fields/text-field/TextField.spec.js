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

import localVue from "../../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import TextField from "./TextField.vue";
import CKEDITOR from "ckeditor";

function getInstance(props) {
    return shallowMount(TextField, {
        localVue,
        propsData: props
    });
}

const field = { field_id: 105, required: false };
let disabled, value, editor;

describe(`TextField`, () => {
    beforeEach(() => {
        editor = jasmine.createSpyObj("editor", ["on", "destroy"]);
        CKEDITOR.replace = jasmine.createSpy("CKEditor.replace").and.returnValue(editor);
        disabled = false;
        value = {
            format: "text",
            content: ""
        };
    });

    describe(`when the field's format is "html"`, () => {
        beforeEach(() => {
            value.format = "html";
        });
        describe(`mounted()`, () => {
            it(`will instantiate CKEditor`, () => {
                getInstance({ field, disabled, value });
                expect(CKEDITOR.replace).toHaveBeenCalled();
            });

            it(`and when the editor dispatched the "change" event,
                and the editor's data was different from its prop
                then it will dispatch an "input" event with the new content`, () => {
                let triggerChange;
                editor.on.and.callFake((event_name, handler) => {
                    if (event_name === "instanceReady") {
                        return handler();
                    }
                    if (event_name === "change") {
                        triggerChange = handler;
                        return;
                    }
                });
                editor.getData = () => "caramba";

                const wrapper = getInstance({ field, disabled, value });
                triggerChange();

                expect(wrapper.emitted().input[0]).toEqual([
                    {
                        format: "html",
                        content: "caramba"
                    }
                ]);
            });

            it(`and when the editor dispatched the "mode" event,
                and the editor was in "source" mode (direct HTML edition)
                and the editor's editable textarea dispatched the "input" event,
                then it will dispatch an "input" event with the new content`, () => {
                let triggerMode, triggerEditableInput;
                editor.on.and.callFake((event_name, handler) => {
                    if (event_name === "instanceReady") {
                        return handler();
                    }
                    if (event_name === "mode") {
                        triggerMode = handler;
                        return;
                    }
                });
                editor.mode = "source";
                const editable = jasmine.createSpyObj("editor.editable", ["attachListener"]);
                editable.attachListener.and.callFake((element, event_name, handler) => {
                    triggerEditableInput = handler;
                });
                editor.editable = () => editable;
                editor.getData = () => "noniodized";

                const wrapper = getInstance({ field, disabled, value });
                triggerMode();
                triggerEditableInput();

                expect(editable.attachListener).toHaveBeenCalledWith(
                    jasmine.anything(),
                    "input",
                    jasmine.any(Function)
                );
                expect(wrapper.emitted().input[0]).toEqual([
                    {
                        format: "html",
                        content: "noniodized"
                    }
                ]);
            });
        });

        describe(`beforeDestroy()`, () => {
            it(`if the editor was created, then it will destroy the editor`, () => {
                const wrapper = getInstance({ field, disabled, value });
                wrapper.destroy();

                expect(editor.destroy).toHaveBeenCalled();
            });
        });

        describe(`and I switched the format to "text"`, () => {
            it(`will destroy the editor`, () => {
                const wrapper = getInstance({ field, disabled, value });
                wrapper.vm.format = "text";

                expect(editor.destroy).toHaveBeenCalled();
            });

            it(`will dispatch an "input" event with the new format`, () => {
                const wrapper = getInstance({ field, disabled, value });
                wrapper.vm.format = "text";

                expect(wrapper.emitted("input")[0]).toEqual([
                    {
                        format: "text",
                        content: ""
                    }
                ]);
            });
        });
    });

    describe(`when the field's format is "text"`, () => {
        beforeEach(() => {
            value.format = "text";
        });

        describe(`mounted()`, () => {
            it(`will NOT instantiate CKEditor`, () => {
                getInstance({ field, disabled, value });
                expect(CKEDITOR.replace).not.toHaveBeenCalled();
            });
        });

        describe(`and I switched the format to "html"`, () => {
            it(`will instantiate CKEditor`, () => {
                const wrapper = getInstance({ field, disabled, value });
                wrapper.vm.format = "html";

                expect(CKEDITOR.replace).toHaveBeenCalled();
            });

            it(`will dispatch an "input" event with the new format`, () => {
                const wrapper = getInstance({ field, disabled, value });
                wrapper.vm.format = "html";

                expect(wrapper.emitted("input")[0]).toEqual([
                    {
                        format: "html",
                        content: ""
                    }
                ]);
            });
        });

        describe(`and I wrote text in the textarea`, () => {
            it(`will dispatch an "input" event with the new content`, () => {
                const wrapper = getInstance({ field, disabled, value });
                wrapper.vm.content = "flattening";

                expect(wrapper.emitted("input")[0]).toEqual([
                    {
                        format: "text",
                        content: "flattening"
                    }
                ]);
            });
        });
    });

    it(`will set the "error" class when the field is required
        and the content is an empty string`, () => {
        field.required = true;
        value.content = "";
        const wrapper = getInstance({ field, disabled, value });

        const form_element = wrapper.find("[data-test=form-element]");
        expect(form_element.classes("tlp-form-element-error")).toBe(true);
    });

    describe(`disabled`, () => {
        let wrapper;
        beforeEach(() => {
            disabled = true;
            wrapper = getInstance({ field, disabled, value });
        });

        it(`will set the "disabled" class according to its prop`, () => {
            const form_element = wrapper.find("[data-test=form-element]");
            expect(form_element.classes("tlp-form-element-disabled")).toBe(true);
        });

        it(`will compute CKEditor's readOnly configuration from the "disabled" prop`, () => {
            expect(wrapper.vm.ckeditor_config.readOnly).toBe(true);
        });

        it(`will set the textarea to disabled`, () => {
            const textarea = wrapper.find("[data-test=textarea]");
            expect(textarea.attributes("disabled")).toBe("disabled");
        });

        it(`will set the format selectbox to disabled`, () => {
            const format_selectbox = wrapper.find("[data-test=format]");
            expect(format_selectbox.attributes("disabled")).toBe("disabled");
        });
    });

    describe(`when the field is required`, () => {
        let wrapper;
        beforeEach(() => {
            field.required = true;
            wrapper = getInstance({ field, disabled, value });
        });

        it(`will set the textarea to required`, () => {
            const textarea = wrapper.find("[data-test=textarea]");
            expect(textarea.attributes("required")).toBe("required");
        });

        it(`will show a red asterisk icon next to the field label`, () => {
            expect(wrapper.contains(".fa-asterisk")).toBe(true);
        });
    });
});
