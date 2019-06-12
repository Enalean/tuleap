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

import localVue from "../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import RichTextEditor from "./RichTextEditor.vue";
import CKEDITOR from "ckeditor";

let format, value, disabled, required, editor;

function getInstance() {
    return shallowMount(RichTextEditor, {
        localVue,
        propsData: {
            id: "unique-id",
            format,
            value,
            disabled,
            required
        }
    });
}

describe(`RichTextEditor`, () => {
    beforeEach(() => {
        editor = jasmine.createSpyObj("editor", ["on", "destroy"]);
        CKEDITOR.replace = jasmine.createSpy("CKEditor.replace").and.returnValue(editor);
        disabled = false;
        format = "text";
        value = "";
    });

    describe(`when the editor's format is "html"`, () => {
        beforeEach(() => {
            format = "html";
        });
        describe(`mounted()`, () => {
            it(`will instantiate CKEditor`, () => {
                getInstance();
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

                const wrapper = getInstance();
                triggerChange();

                expect(wrapper.emitted().input[0]).toEqual(["caramba"]);
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

                const wrapper = getInstance();
                triggerMode();
                triggerEditableInput();

                expect(editable.attachListener).toHaveBeenCalledWith(
                    jasmine.anything(),
                    "input",
                    jasmine.any(Function)
                );
                expect(wrapper.emitted().input[0]).toEqual(["noniodized"]);
            });
        });

        describe(`beforeDestroy()`, () => {
            it(`if the editor was created, then it will destroy the editor`, () => {
                const wrapper = getInstance();
                wrapper.destroy();

                expect(editor.destroy).toHaveBeenCalled();
            });
        });

        describe(`and I switched the format to "text"`, () => {
            it(`will destroy the editor`, () => {
                const wrapper = getInstance();
                wrapper.setProps({ format: "text" });

                expect(editor.destroy).toHaveBeenCalled();
            });
        });
    });

    describe(`when the field's format is "text"`, () => {
        beforeEach(() => {
            format = "text";
        });

        describe(`mounted()`, () => {
            it(`will NOT instantiate CKEditor`, () => {
                getInstance();
                expect(CKEDITOR.replace).not.toHaveBeenCalled();
            });
        });

        describe(`and I switched the format to "html"`, () => {
            it(`will instantiate CKEditor`, () => {
                const wrapper = getInstance();
                wrapper.setProps({ format: "html" });

                expect(CKEDITOR.replace).toHaveBeenCalled();
            });
        });

        describe(`and I wrote text in the textarea`, () => {
            it(`will dispatch an "input" event with the new content`, () => {
                const wrapper = getInstance();
                wrapper.vm.content = "flattening";

                expect(wrapper.emitted("input")[0]).toEqual(["flattening"]);
            });
        });
    });

    it(`when the format is anything else, it throws`, () => {
        const wrapper = getInstance();
        expect(wrapper.vm.$options.props.format.validator("markdown")).toBe(false);
    });

    describe(`disabled`, () => {
        let wrapper;
        beforeEach(() => {
            disabled = true;
            wrapper = getInstance();
        });

        it(`will compute CKEditor's readOnly configuration from the "disabled" prop`, () => {
            expect(wrapper.vm.ckeditor_config.readOnly).toBe(true);
        });

        it(`will set the textarea to disabled`, () => {
            expect(wrapper.attributes("disabled")).toBe("disabled");
        });
    });

    describe(`required`, () => {
        let wrapper;
        beforeEach(() => {
            required = true;
            wrapper = getInstance();
        });

        it(`will set the textarea to required`, () => {
            expect(wrapper.attributes("required")).toBe("required");
        });
    });
});
