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
    <div class="tlp-dropdown" v-if="should_display_dropdown">
        <button
            type="button"
            v-bind:title="trigger_title"
            class="tlp-button-primary tlp-button-ellipsis"
            ref="trigger"
        >
            <i class="fa-solid fa-ellipsis" role="img"></i>
        </button>
        <div class="tlp-dropdown-menu" role="menu">
            <pdf-export-menu-item v-if="should_display_pdf_menu_item" />
            <configuration-modal v-if="can_user_edit_document" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import { createDropdown } from "@tuleap/tlp-dropdown";
import { ref, watch } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import ConfigurationModal from "@/components/configuration/ConfigurationModal.vue";
import { PDF_TEMPLATES } from "@/pdf-templates-injection-key";
import PdfExportMenuItem from "@/components/export/pdf/PdfExportMenuItem.vue";

const { $gettext } = useGettext();

const trigger = ref<HTMLElement | null>(null);

const trigger_title = $gettext("Open contextual menu");

let dropdown: Dropdown | null = null;

const can_user_edit_document = strictInject(CAN_USER_EDIT_DOCUMENT);
const pdf_templates = strictInject(PDF_TEMPLATES);

const should_display_pdf_menu_item = pdf_templates !== null && pdf_templates.length > 0;

const should_display_dropdown = should_display_pdf_menu_item || can_user_edit_document;

watch(trigger, () => {
    if (dropdown === null && trigger.value) {
        dropdown = createDropdown(trigger.value);
    }
});
</script>

<style lang="scss" scoped>
@media print {
    .tlp-dropdown {
        display: none;
    }
}
</style>
