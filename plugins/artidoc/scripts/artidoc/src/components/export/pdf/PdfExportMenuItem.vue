<!--
- Copyright (c) Enalean, 2024 - present. All Rights Reserved.
-
- This file is a part of Tuleap.
-
- Tuleap is free software; you can redistribute it and/or modify
- it under the terms of the GNU General Public License as published by
- the Free Software Foundation; either version 2 of the License, or
- (at your option) any later version.
-
- Tuleap is distributed in the hope that it will be useful,
- but WITHOUT ANY WARRANTY; without even the implied warranty of
- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
- GNU General Public License for more details.
-
- You should have received a copy of the GNU General Public License
- along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
-
-->

<template>
    <button
        v-if="is_option_disabled"
        type="button"
        disabled
        class="tlp-dropdown-menu-item tlp-dropdown-menu-item-disabled"
        role="menuitem"
        v-bind:title="getDisabledOptionTitle()"
    >
        <i class="fa-regular fa-file-pdf fa-fw" aria-hidden="true"></i>
        {{ export_in_pdf }}
    </button>
    <div
        v-else-if="has_more_than_one_template"
        class="tlp-dropdown-menu-item tlp-dropdown-menu-item-submenu"
        id="dropdown-menu-example-options-submenu-1"
        aria-haspopup="true"
        role="menuitem"
        ref="trigger"
    >
        <i class="fa-regular fa-file-pdf fa-fw" aria-hidden="true"></i>
        {{ export_in_pdf }}
        <div
            class="tlp-dropdown-menu tlp-dropdown-submenu tlp-dropdown-menu-side"
            role="menu"
            ref="submenu"
            v-bind:aria-label="submenu_label"
        >
            <button
                v-for="template in pdf_templates.list.value"
                v-bind:key="template.id"
                type="button"
                v-on:click="printUsingTemplate(template)"
                class="tlp-dropdown-menu-item"
                role="menuitem"
                v-bind:title="template.description"
            >
                {{ template.label }}
            </button>
        </div>
    </div>
    <button
        v-else
        type="button"
        v-on:click="printUsingFirstTemplate"
        class="tlp-dropdown-menu-item"
        role="menuitem"
    >
        <i class="fa-regular fa-file-pdf fa-fw" aria-hidden="true"></i>
        {{ export_in_pdf }}
    </button>

    <printer-version v-if="!is_option_disabled" />
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PDF_TEMPLATES_STORE } from "@/stores/pdf-templates-store";
import type { PdfTemplate } from "@tuleap/print-as-pdf";
import { printAsPdf } from "@tuleap/print-as-pdf";
import { createDropdown } from "@tuleap/tlp-dropdown";
import { IS_USER_ANONYMOUS } from "@/is-user-anonymous";
import PrinterVersion from "@/components/print/PrinterVersion.vue";
import { EDITORS_COLLECTION } from "@/composables/useSectionEditorsCollection";
import { TITLE } from "@/title-injection-key";

const pdf_templates = strictInject(PDF_TEMPLATES_STORE);
const is_user_anonymous = strictInject(IS_USER_ANONYMOUS);
const editors_collection = strictInject(EDITORS_COLLECTION);
const title = strictInject(TITLE);

const has_more_than_one_template = pdf_templates.list.value.length > 1;

const { $gettext } = useGettext();
const submenu_label = $gettext("Available templates");
const export_in_pdf = $gettext("Export document in PDF");

const has_pdf_templates = pdf_templates.list.value.length > 0;

const is_option_disabled = computed(
    (): boolean =>
        is_user_anonymous || !has_pdf_templates || editors_collection.hasAtLeastOneEditorOpened(),
);
const getDisabledOptionTitle = (): string => {
    if (editors_collection.hasAtLeastOneEditorOpened()) {
        return $gettext("The document is being edited. Please save your work beforehand.");
    }

    return is_user_anonymous
        ? $gettext("Please log in in order to be able to export as PDF")
        : $gettext("No template was defined for export, please contact site administrator");
};

function printUsingFirstTemplate(): void {
    if (pdf_templates.list.value.length === 0) {
        return;
    }

    printUsingTemplate(pdf_templates.list.value[0]);
}

function printUsingTemplate(template: PdfTemplate): void {
    const printable = document.getElementById("artidoc-print-version");
    if (!printable) {
        return;
    }

    pdf_templates.setSelectedPdfTemplate(template);

    setTimeout(() => {
        printAsPdf(printable, template, { DOCUMENT_TITLE: title }).mapErr((fault) => {
            // eslint-disable-next-line no-console
            console.error(fault.toString());
        });
    });
}

const trigger = ref<HTMLElement | null>(null);
const submenu = ref<HTMLElement | null>(null);

onMounted(() => {
    if (trigger.value && submenu.value) {
        createDropdown(trigger.value, {
            keyboard: false,
            trigger: "hover-and-click",
            dropdown_menu: submenu.value,
        });
    }
});
</script>
