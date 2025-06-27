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
  -->

<template>
    <div
        class="tlp-dropdown-menu-item tlp-dropdown-menu-item-submenu"
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
                v-on:click="print_using_template(template)"
                class="tlp-dropdown-menu-item"
                role="menuitem"
                data-test="pdf-template-button"
                v-bind:title="template.description"
            >
                {{ template.label }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { createDropdown } from "@tuleap/tlp-dropdown";
import { PDF_TEMPLATES_COLLECTION } from "@/pdf/pdf-templates-collection";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { PdfTemplate } from "@tuleap/print-as-pdf";

const pdf_templates = strictInject(PDF_TEMPLATES_COLLECTION);

const { $gettext } = useGettext();
const export_in_pdf = $gettext("Export document in PDF");
const submenu_label = $gettext("Available templates");

const trigger = ref<HTMLElement | null>(null);
const submenu = ref<HTMLElement | null>(null);

defineProps<{
    print_using_template(template: PdfTemplate): void;
}>();

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
