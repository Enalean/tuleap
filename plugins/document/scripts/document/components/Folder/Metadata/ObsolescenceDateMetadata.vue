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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <div class="tlp-form-element" v-if="is_obsolescence_date_metadata_used">
        <label class="tlp-label"
               for="document-new-obsolescence-date"
               v-translate
        >
            Obsolescence date
        </label>
        <div class="tlp-form-element tlp-form-element-prepend">
            <span class="tlp-prepend"><i class="fa fa-calendar"></i></span>
            <input type="text"
                   id="document-new-obsolescence-date"
                   class="tlp-input tlp-input-date"
                   size="11"
                   v-on:click.prevent="$emit('input', $event.target.value)"
                   name="input"
                   data-test="document-new-item-obsolescence-date"
                   v-bind:value="value"
                   ref="input"
            >
        </div>
    </div>
</template>

<script>
import { datePicker } from "tlp";
import { mapState } from "vuex";

export default {
    name: "ObsolescenceDateMetadata",
    props: {
        value: String
    },
    computed: {
        ...mapState(["is_obsolescence_date_metadata_used"])
    },
    mounted() {
        datePicker(this.$refs.input);
    }
};
</script>
