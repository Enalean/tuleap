<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="tlp-framed">
        <h1>{{ embedded_title }}</h1>
        <section class="tlp-pane">
            <div class="tlp-pane-container">
                <section class="tlp-pane-section" v-html="embedded_content"></section>
            </div>
        </section>
    </div>
</template>

<script>
import dompurify from "dompurify";
export default {
    name: "DisplayEmbeddedContent",
    props: {
        embedded_file: Object
    },
    computed: {
        embedded_title() {
            return this.embedded_file.title;
        },
        embedded_content() {
            if (!this.embedded_file.embedded_file_properties) {
                return;
            }

            return dompurify.sanitize(this.embedded_file.embedded_file_properties.content);
        }
    }
};
</script>
