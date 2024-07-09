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
    <button type="button" v-on:click="onClick" class="tlp-dropdown-menu-item" role="menuitem">
        <i class="fa-regular fa-file-pdf fa-fw" aria-hidden="true"></i>
        {{ $gettext("Export document in PDF") }}
    </button>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import print from "print-js";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PDF_TEMPLATES } from "@/pdf-templates-injection-key";

const pdf_templates = strictInject(PDF_TEMPLATES);

const { $gettext } = useGettext();

function onClick(): void {
    const printable = document.getElementById("artidoc-print-version");
    if (!printable) {
        return;
    }

    if (pdf_templates === null || pdf_templates.length === 0) {
        return;
    }

    const selected_pdf_template = pdf_templates[0];

    print({
        printable,
        type: "html",
        scanStyles: false,
        style: selected_pdf_template.style,
    });
}
</script>
