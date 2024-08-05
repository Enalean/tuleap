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
    <div id="artidoc-print-version">
        <table style="border: 0">
            <thead style="display: table-header-group; border: 0">
                <tr style="border: 0">
                    <td style="border: 0">
                        <div class="document-header-space">&nbsp;</div>
                    </td>
                </tr>
            </thead>
            <tbody>
                <tr style="border: 0">
                    <td style="border: 0">
                        <div
                            class="document-page"
                            v-if="title_page_content"
                            v-dompurify-html="title_page_content"
                        ></div>
                        <div class="document-page">
                            <aside>
                                <table-of-contents v-bind:is_print_mode="true" />
                            </aside>
                        </div>
                        <div class="document-page">
                            <section class="document-content">
                                <ol>
                                    <li
                                        v-for="section in saved_sections"
                                        v-bind:key="section.id"
                                        class="document-section"
                                        v-bind:id="`pdf-section-${section.id}`"
                                    >
                                        <section-printer-version v-bind:section="section" />
                                    </li>
                                </ol>
                            </section>
                        </div>
                    </td>
                </tr>
            </tbody>
            <tfoot style="display: table-footer-group; border: 0">
                <tr style="border: 0">
                    <td style="border: 0">
                        <div class="document-footer-space">&nbsp;</div>
                    </td>
                </tr>
            </tfoot>
        </table>
        <div class="document-header" v-if="header_content" v-dompurify-html="header_content"></div>
        <div class="document-footer" v-if="footer_content" v-dompurify-html="footer_content"></div>
    </div>
</template>

<script setup lang="ts">
import TableOfContents from "@/components/toc/TableOfContents.vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";
import SectionPrinterVersion from "@/components/print/SectionPrinterVersion.vue";
import { TITLE } from "@/title-injection-key";
import { PDF_TEMPLATES_STORE } from "@/stores/pdf-templates-store";
import { computed } from "vue";

const { saved_sections } = strictInject(SECTIONS_STORE);
const pdf_templates = strictInject(PDF_TEMPLATES_STORE);
const title = strictInject(TITLE);

const title_page_content = computed(() =>
    replaceVariables(pdf_templates.selected_template.value?.title_page_content),
);

const header_content = computed(() =>
    replaceVariables(pdf_templates.selected_template.value?.header_content),
);

const footer_content = computed(() =>
    replaceVariables(pdf_templates.selected_template.value?.footer_content),
);

function replaceVariables(html: string | undefined): string | undefined {
    if (html === undefined) {
        return undefined;
    }

    // eslint-disable-next-line no-template-curly-in-string
    return html.replace("${DOCUMENT_TITLE}", title);
}
</script>

<style lang="scss" scoped>
#artidoc-print-version {
    display: none;
}
</style>
