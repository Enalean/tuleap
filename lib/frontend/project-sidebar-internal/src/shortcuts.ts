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

import type { Tool } from "./configuration";
import { unserializeConfiguration } from "./configuration";

export type ToolID =
    | typeof TRACKERS
    | typeof GIT
    | typeof TESTMANAGEMENT
    | typeof DOCUMENTS
    | typeof BACKLOG
    | typeof KANBAN
    | typeof FIRST_TOOL;

export const TRACKERS = "plugin_tracker";
export const GIT = "plugin_git";
export const TESTMANAGEMENT = "plugin_testmanagement";
export const DOCUMENTS = "docman";
export const BACKLOG = "plugin_agiledashboard";
export const KANBAN = "plugin_kanban";
export const FIRST_TOOL = "first_sidebar_tool";

const SUPPORTED_SERVICE_SHORTCUTS: ReadonlyArray<
    Pick<ProjectSidebarShortcut, "name" | "keyboard_inputs">
> = [
    { name: TESTMANAGEMENT, keyboard_inputs: "g+e" },
    { name: TRACKERS, keyboard_inputs: "g+t" },
    { name: GIT, keyboard_inputs: "g+i" },
    { name: DOCUMENTS, keyboard_inputs: "g+d" },
    { name: BACKLOG, keyboard_inputs: "g+b" },
    { name: KANBAN, keyboard_inputs: "g+k" },
];

export interface ProjectSidebarShortcut {
    name: ToolID;
    label: string;
    keyboard_inputs: string;
}

interface ProjectSidebarShortcutExecutor {
    execute: (tools: HTMLElement) => void;
}

type ProjectSidebarShortcutWithHandle = ProjectSidebarShortcut & ProjectSidebarShortcutExecutor;

export function getAvailableShortcuts(doc: HTMLElement): ProjectSidebarShortcut[] | null {
    const sidebars = doc.getElementsByTagName("tuleap-project-sidebar");

    for (const sidebar of sidebars) {
        const config = unserializeConfiguration(sidebar.getAttribute("config") ?? undefined);
        if (config !== undefined) {
            return getAvailableShortcutsFromToolsConfiguration(config.tools);
        }
    }

    return null;
}

export function getAvailableShortcutsFromToolsConfiguration(
    tools: ReadonlyArray<Tool>,
): ProjectSidebarShortcutWithHandle[] | null {
    const available_tools = new Map(tools.map((tool: Tool) => [tool.shortcut_id, tool]));
    if (available_tools.size <= 0) {
        return null;
    }

    return [
        {
            name: FIRST_TOOL,
            label: "",
            keyboard_inputs: "shift+g",
            execute: focusFirstTool,
        },
        ...SUPPORTED_SERVICE_SHORTCUTS.flatMap((shortcut): ProjectSidebarShortcutWithHandle[] => {
            const tool = available_tools.get(shortcut.name);
            if (tool === undefined) {
                return [];
            }
            return [
                {
                    name: shortcut.name,
                    label: tool.label,
                    keyboard_inputs: shortcut.keyboard_inputs,
                    execute: buildGoToService(shortcut.name),
                },
            ];
        }),
    ];
}

function focusFirstTool(tools: HTMLElement): void {
    const first_sidebar_tool = tools.querySelector("[data-shortcut-sidebar]");
    if (!(first_sidebar_tool instanceof HTMLElement)) {
        return;
    }
    first_sidebar_tool.focus();
}

function buildGoToService(name: string): (tools: HTMLElement) => void {
    return (tools: HTMLElement): void => {
        const sidebar_service = tools.querySelector(`[data-shortcut-sidebar="sidebar-${name}"]`);
        if (!(sidebar_service instanceof HTMLElement) || !sidebar_service.title) {
            return;
        }
        sidebar_service.click();
    };
}
