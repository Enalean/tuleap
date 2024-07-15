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
    <div
        v-if="has_more_than_one_template"
        class="tlp-dropdown-menu-item tlp-dropdown-menu-item-submenu"
        id="dropdown-menu-example-options-submenu-1"
        aria-haspopup="true"
        role="menuitem"
        ref="trigger"
    >
        <i class="fa-regular fa-file-pdf fa-fw" aria-hidden="true"></i>
        {{ $gettext("Export document in PDF") }}
        <div
            class="tlp-dropdown-menu tlp-dropdown-submenu tlp-dropdown-menu-side"
            role="menu"
            ref="submenu"
            v-bind:aria-label="submenu_label"
        >
            <button
                v-for="template in pdf_templates"
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
        {{ $gettext("Export document in PDF") }}
    </button>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PDF_TEMPLATES } from "@/pdf-templates-injection-key";
import type { PdfTemplate } from "@tuleap/print-as-pdf";
import { printAsPdf } from "@tuleap/print-as-pdf";
import { onMounted, ref } from "vue";
import { createDropdown } from "@tuleap/tlp-dropdown";

const pdf_templates = strictInject(PDF_TEMPLATES);

const has_more_than_one_template = pdf_templates !== null && pdf_templates.length > 1;

const { $gettext } = useGettext();
const submenu_label = $gettext("Available templates");

function printUsingFirstTemplate(): void {
    if (pdf_templates === null || pdf_templates.length === 0) {
        return;
    }

    printUsingTemplate(pdf_templates[0]);
}

function printUsingTemplate(template: PdfTemplate): void {
    const printable = document.getElementById("artidoc-print-version");
    if (!printable) {
        return;
    }

    printAsPdf(printable, template);
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
