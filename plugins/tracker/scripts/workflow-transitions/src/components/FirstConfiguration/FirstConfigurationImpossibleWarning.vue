<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <section class="tlp-pane-section">
        <p class="tlp-alert-warning" v-dompurify-html="message"></p>
    </section>
</template>

<script>
import { mapGetters } from "vuex";

export default {
    name: "FirstConfigurationImpossibleWarning",
    computed: {
        ...mapGetters(["current_tracker_id"]),
        edit_fields_url() {
            return encodeURI(
                `/plugins/tracker/?tracker=${this.current_tracker_id}&func=admin-formElements`,
            );
        },
        message() {
            let translated = this
                .$gettext(`There is no selectbox bound to static values in your tracker! Please
            <a href="%{ url }">add one</a> before configuring the workflow.`);
            return this.$gettextInterpolate(translated, { url: this.edit_fields_url });
        },
    },
};
</script>
