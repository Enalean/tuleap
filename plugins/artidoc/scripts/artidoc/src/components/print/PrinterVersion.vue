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
                        <div class="document-page">
                            <h1 class="document-title">
                                {{ title }}
                            </h1>
                        </div>
                        <div class="document-page">
                            <aside>
                                <table-of-contents />
                            </aside>
                        </div>
                        <div class="document-page">
                            <section class="document-content">
                                <ol>
                                    <li
                                        v-for="section in saved_sections"
                                        v-bind:key="section.id"
                                        class="document-section"
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
        <div
            class="document-header"
            v-if="pdf_templates.selected_template.value"
            v-dompurify-html="pdf_templates.selected_template.value.header_content"
        ></div>
        <div
            class="document-footer"
            v-if="pdf_templates.selected_template.value"
            v-dompurify-html="pdf_templates.selected_template.value.footer_content"
        ></div>
    </div>
</template>

<script setup lang="ts">
import TableOfContents from "@/components/toc/TableOfContents.vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";
import SectionPrinterVersion from "@/components/print/SectionPrinterVersion.vue";
import { TITLE } from "@/title-injection-key";
import { PDF_TEMPLATES_STORE } from "@/stores/pdf-templates-store";

const { saved_sections } = strictInject(SECTIONS_STORE);
const pdf_templates = strictInject(PDF_TEMPLATES_STORE);
const title = strictInject(TITLE);
</script>

<style lang="scss" scoped>
#artidoc-print-version {
    display: none;
}
</style>
