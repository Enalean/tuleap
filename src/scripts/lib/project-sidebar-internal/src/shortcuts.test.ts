/**
 * Copyright (c) 2022-Present Enalean
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

import { getAvailableShortcuts, getAvailableShortcutsFromToolsConfiguration } from "./shortcuts";
import { example_config } from "./project-sidebar-example-config";

describe("Project Sidebar shortcuts", () => {
    it("finds shortcuts from project sidebar config attribute", () => {
        const doc = document.implementation.createHTMLDocument();
        const project_sidebar_element = doc.createElement("tuleap-project-sidebar");
        project_sidebar_element.setAttribute("config", JSON.stringify(example_config));
        doc.body.appendChild(project_sidebar_element);

        expect(getAvailableShortcuts(doc.body)?.length).toBeGreaterThan(0);
    });

    it("does not find shortcuts when the sidebar is not present", () => {
        const doc = document.implementation.createHTMLDocument();

        expect(getAvailableShortcuts(doc.body)).toStrictEqual(null);
    });

    it("does not find shortcuts when the sidebar does not have a configuration", () => {
        const doc = document.implementation.createHTMLDocument();
        doc.body.appendChild(doc.createElement("tuleap-project-sidebar"));

        expect(getAvailableShortcuts(doc.body)).toStrictEqual(null);
    });

    it("does not find shortcuts when there is no tools", () => {
        expect(getAvailableShortcutsFromToolsConfiguration([])).toStrictEqual(null);
    });

    it("associates shortcuts with an action", () => {
        const doc = document.implementation.createHTMLDocument();
        const first_tool = buildToolElementLookAlike(doc, "something");
        jest.spyOn(first_tool, "focus").mockImplementation();
        const git_service = buildToolElementLookAlike(doc, "plugin_git");
        jest.spyOn(git_service, "click").mockImplementation();

        const [focus_first_tool_shortcut, click_on_git_shortcut] =
            getAvailableShortcutsFromToolsConfiguration(example_config.tools) ?? [];

        focus_first_tool_shortcut.execute(doc.body);
        expect(first_tool.focus).toHaveBeenCalled();
        click_on_git_shortcut.execute(doc.body);
        expect(git_service.click).toHaveBeenCalled();
    });
});

function buildToolElementLookAlike(doc: Document, name: string): HTMLElement {
    const tool_element = doc.createElement("span");
    tool_element.setAttribute("data-shortcut-sidebar", `sidebar-${name}`);
    tool_element.setAttribute("title", name);
    doc.body.appendChild(tool_element);
    return tool_element;
}
