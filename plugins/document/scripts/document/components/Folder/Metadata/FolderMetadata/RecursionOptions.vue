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
    <section class="document-metadata-recursion-option-section">
        <h2 class="tlp-modal-subtitle" v-translate>Recursion option</h2>
        <select
            class="tlp-select"
            id="recursion-option"
            name="recursion"
            v-bind:value="value"
            v-on:input="$emit('input', $event.target.value)"
        >
            <option value="" v-bind:selected="! shouldApplyRecursion"></option>
            <option value="none" v-translate v-bind:selected="shouldApplyRecursion">Apply new values on this folder only</option>
            <option value="folders" v-translate>Apply new values on this folder and its subfolders only</option>
            <option value="all_items" v-translate>Apply new values on this folder and all its content</option>
        </select>
    </section>
</template>
<script>
export default {
    name: "RecursionOptions",
    props: {
        shouldApplyRecursion: Boolean,
        value: String
    }
};
</script>
