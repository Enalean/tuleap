/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { Shortcut, ShortcutsGroup } from "@tuleap/keyboard-shortcuts";
import { addShortcutsGroup } from "@tuleap/keyboard-shortcuts";

import type { GettextProvider } from "../type";
import { BOTTOM, NEXT, PREVIOUS, TOP } from "../type";
import { callNavigationShortcut } from "./handle-navigation-shortcut";
import { clickOnDatashortcutElement } from "./click-on-datashortcut-element";

export function setupDocumentShortcuts(gettext_provider: GettextProvider): void {
    const move_to_bottom_file: Shortcut = {
        keyboard_inputs: "ctrl+k,ctrl+down",
        displayed_inputs: "Ctrl+k,Ctrl+↓",
        description: gettext_provider.$gettext("Select bottom item"),
        handle: () => {
            callNavigationShortcut(document, BOTTOM);
        },
    };

    const move_to_top_file: Shortcut = {
        keyboard_inputs: "ctrl+j,ctrl+up",
        displayed_inputs: "Ctrl+j,Ctrl+↑",
        description: gettext_provider.$gettext("Select top item"),
        handle: () => {
            callNavigationShortcut(document, TOP);
        },
    };

    const move_to_previous_file: Shortcut = {
        keyboard_inputs: "j,up",
        displayed_inputs: "j,↑",
        description: gettext_provider.$gettext("Select previous item"),
        handle: () => {
            callNavigationShortcut(document, PREVIOUS);
        },
    };

    const move_to_next_file: Shortcut = {
        keyboard_inputs: "k,down",
        displayed_inputs: "k,↓",
        description: gettext_provider.$gettext("Select next item"),
        handle: () => {
            callNavigationShortcut(document, NEXT);
        },
    };

    const toggle_folder: Shortcut = {
        keyboard_inputs: "l,right",
        displayed_inputs: "l,→",
        description: gettext_provider.$gettext("Toggle selected folder open and closed"),
        handle: () => {
            clickOnDatashortcutElement(document, "[data-shortcut-folder-toggle]");
        },
    };

    const search_document: Shortcut = {
        keyboard_inputs: "f",
        description: gettext_provider.$gettext("Focus the document search bar"),
        handle: () => {
            clickOnDatashortcutElement(document, "[data-shortcut-search-document]");
        },
    };

    const create_new_document: Shortcut = {
        keyboard_inputs: "n",
        description: gettext_provider.$gettext("Create a new document"),
        handle: () => {
            clickOnDatashortcutElement(document, "[data-shortcut-create-document]");
        },
    };

    const create_new_folder: Shortcut = {
        keyboard_inputs: "b",
        description: gettext_provider.$gettext("Create a new folder"),
        handle: () => {
            clickOnDatashortcutElement(document, "[data-shortcut-create-folder]");
        },
    };

    const create_document_new_version: Shortcut = {
        keyboard_inputs: "u",
        description: gettext_provider.$gettext("Create a new version of document"),
        handle: () => {
            clickOnDatashortcutElement(document, "[data-shortcut-new-version]");
        },
    };

    const lock_unlock_document: Shortcut = {
        keyboard_inputs: "l",
        description: gettext_provider.$gettext("Lock and unlock document"),
        handle: () => {
            clickOnDatashortcutElement(document, "[data-shortcut-lock-document]");
        },
    };

    const update_document_properties: Shortcut = {
        keyboard_inputs: "e",
        description: gettext_provider.$gettext("Update properties"),
        handle: () => {
            clickOnDatashortcutElement(document, "[data-shortcut-update-properties]");
        },
    };

    const delete_document: Shortcut = {
        keyboard_inputs: "delete",
        displayed_inputs: gettext_provider.$pgettext("keyboard key", "Delete"),
        description: gettext_provider.$gettext("Delete"),
        handle: () => {
            clickOnDatashortcutElement(document, "[data-shortcut-delete]");
        },
    };

    const cut_document: Shortcut = {
        keyboard_inputs: "ctrl+x",
        displayed_inputs: "Ctrl+x",
        description: gettext_provider.$gettext("Cut"),
        handle: () => {
            clickOnDatashortcutElement(document, "[data-shortcut-cut]");
        },
    };

    const copy_document: Shortcut = {
        keyboard_inputs: "ctrl+c",
        displayed_inputs: "Ctrl+c",
        description: gettext_provider.$gettext("Copy"),
        handle: () => {
            clickOnDatashortcutElement(document, "[data-shortcut-copy]");
        },
    };

    const paste_document: Shortcut = {
        keyboard_inputs: "ctrl+v",
        displayed_inputs: "Ctrl+v",
        description: gettext_provider.$gettext("Paste"),
        handle: () => {
            clickOnDatashortcutElement(document, "[data-shortcut-paste]");
        },
    };

    const download_folder: Shortcut = {
        keyboard_inputs: "z",
        description: gettext_provider.$gettext("Download as zip"),
        handle: () => {
            clickOnDatashortcutElement(document, "[data-shortcut-download-zip]");
        },
    };

    const open_document_notifications: Shortcut = {
        keyboard_inputs: "m",
        description: gettext_provider.$gettext("Open notifications settings"),
        handle: () => {
            clickOnDatashortcutElement(document, "[data-shortcut-notifications]");
        },
    };

    const open_document_history: Shortcut = {
        keyboard_inputs: "h",
        description: gettext_provider.$gettext("Open history"),
        handle: () => {
            clickOnDatashortcutElement(document, "[data-shortcut-history]");
        },
    };

    const open_document_permissions: Shortcut = {
        keyboard_inputs: "p",
        description: gettext_provider.$gettext("Open permissions settings"),
        handle: () => {
            clickOnDatashortcutElement(document, "[data-shortcut-permissions]");
        },
    };

    const open_document_approval_tables: Shortcut = {
        keyboard_inputs: "v",
        description: gettext_provider.$gettext("Open approval tables"),
        handle: () => {
            clickOnDatashortcutElement(document, "[data-shortcut-approval-tables]");
        },
    };

    const document_navigation_shortcuts_group: ShortcutsGroup = {
        title: gettext_provider.$gettext("Navigation in Documents service"),
        shortcuts: [
            move_to_top_file,
            move_to_bottom_file,
            move_to_previous_file,
            move_to_next_file,
            toggle_folder,
            search_document,
        ],
    };

    const document_dropdown_shortcuts_group: ShortcutsGroup = {
        title: gettext_provider.$gettext("Actions in Documents service"),
        details: gettext_provider.$gettext(
            "Shortcuts will apply to the selected document or folder. If there is no selected item they will apply to the current folder.",
        ),
        shortcuts: [
            create_new_document,
            create_new_folder,
            create_document_new_version,
            lock_unlock_document,
            update_document_properties,
            delete_document,
            cut_document,
            copy_document,
            paste_document,
            download_folder,
            open_document_notifications,
            open_document_history,
            open_document_permissions,
            open_document_approval_tables,
        ],
    };

    addShortcutsGroup(document, document_navigation_shortcuts_group);
    addShortcutsGroup(document, document_dropdown_shortcuts_group);
}
