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
    <div class="tlp-framed" v-if="has_more_than_one_editor">
        <div class="tlp-form-element">
            <label class="tlp-label">Editor</label>
            <label class="tlp-label tlp-radio">
                <input type="radio" name="editor" value="legacy" v-model="current_editor" />
                Legacy (ckeditor)
            </label>
            <label class="tlp-label tlp-radio">
                <input type="radio" name="editor" value="nextgen" v-model="current_editor" />
                Nextgen (prosemirror)
                <i class="fa-solid fa-flask" role="img" title="Beta (under construction)"></i>
            </label>
            <label v-if="has_switch_been_triggered" class="tlp-label info-message">{{
                $gettext("You have to reload the browser")
            }}</label>
        </div>
    </div>
</template>
<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import { EDITOR_CHOICE } from "@/helpers/editor-choice";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
const { has_more_than_one_editor, current_editor, has_switch_been_triggered } =
    strictInject(EDITOR_CHOICE);
</script>

<style lang="scss" scoped>
.info-message {
    color: var(--tlp-danger-dark-color);
}
</style>
