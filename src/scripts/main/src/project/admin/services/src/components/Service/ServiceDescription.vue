<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div class="tlp-form-element">
        <label class="tlp-label" v-bind:for="id" v-translate>Description</label>
        <input
            type="text"
            class="tlp-input"
            v-bind:id="id"
            name="description"
            v-bind:placeholder="description_placeholder"
            maxlength="255"
            v-bind:value="value"
        />
    </div>
</template>
<script>
export default {
    name: "ServiceDescription",
    props: {
        value: {
            type: String,
            required: true,
        },
        id: {
            type: String,
            required: true,
        },
    },
    computed: {
        description_placeholder() {
            return this.$gettext("Awesome service to manage extra stuff");
        },
    },
};
</script>
