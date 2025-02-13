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
    <pdf-export-menu-templates-dropdown
        v-else-if="has_more_than_one_template"
        v-bind:print_using_template="printUsingTemplate"
    />
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

    <div
        ref="error_modal"
        role="dialog"
        aria-labelledby="pdftemplate-admin-template-error-modal-label"
        class="tlp-modal tlp-modal-danger"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="pdftemplate-admin-template-error-modal-label">
                {{ $gettext("Export error") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="close_label"
            >
                <i class="fa-solid fa-xmark tlp-modal-close-icon" role="img"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <p>
                {{ $gettext("An error occurred while trying to export the document as PDF.") }}
            </p>
            <pre>{{ error_details }}</pre>
        </div>
        <div class="tlp-modal-footer">
            <button
                id="button-close"
                type="button"
                data-dismiss="modal"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
            >
                {{ close_label }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PDF_TEMPLATES_STORE } from "@/stores/pdf-templates-store";
import type { PdfTemplate } from "@tuleap/print-as-pdf";
import { printAsPdf } from "@tuleap/print-as-pdf";
import { IS_USER_ANONYMOUS } from "@/is-user-anonymous";
import PrinterVersion from "@/components/print/PrinterVersion.vue";
import { TITLE } from "@/title-injection-key";
import { createModal } from "@tuleap/tlp-modal";
import PdfExportMenuTemplatesDropdown from "./PdfExportMenuTemplatesDropdown.vue";
import { SECTIONS_STATES_COLLECTION } from "@/sections/states/sections-states-collection-injection-key";

const pdf_templates = strictInject(PDF_TEMPLATES_STORE);
const is_user_anonymous = strictInject(IS_USER_ANONYMOUS);
const states_collection = strictInject(SECTIONS_STATES_COLLECTION);
const title = strictInject(TITLE);

const has_more_than_one_template = pdf_templates.list.value.length > 1;

const { $gettext } = useGettext();

const export_in_pdf = $gettext("Export document in PDF");
const close_label = $gettext("Close");

const has_pdf_templates = pdf_templates.list.value.length > 0;

const is_option_disabled = computed(
    (): boolean =>
        is_user_anonymous ||
        !has_pdf_templates ||
        states_collection.has_at_least_one_section_in_edit_mode.value,
);
const getDisabledOptionTitle = (): string => {
    if (states_collection.has_at_least_one_section_in_edit_mode.value) {
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

const error_modal = ref<HTMLElement | undefined>(undefined);
const error_details = ref("");

function printUsingTemplate(template: PdfTemplate): void {
    const printable = document.getElementById("artidoc-print-version");
    if (!printable) {
        return;
    }

    pdf_templates.setSelectedPdfTemplate(template);

    setTimeout(() => {
        printAsPdf(printable, template, { DOCUMENT_TITLE: title }).mapErr((fault) => {
            if (!error_modal.value) {
                return;
            }

            error_details.value = fault.toString();

            createModal(error_modal.value, {
                destroy_on_hide: true,
            }).show();
        });
    });
}
</script>
