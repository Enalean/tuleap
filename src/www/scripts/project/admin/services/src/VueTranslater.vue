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

<!-- This component is a trick to allow extracting this translated string
     For now, we don't extract translations from .js files -->
<template>
    <div></div>
</template>
<script>
import Vue from "vue";
import { sanitize } from "dompurify";
import { sprintf } from "sprintf-js";
import { escaper } from "../../../../tuleap/escaper.js";

export default Vue.extend({
    name: "VueTranslater",
    methods: {
        getDeleteMessage(button) {
            return sanitize(
                sprintf(
                    this.$gettext(
                        "You are about to delete the <b>%s</b> service. Please, confirm your action"
                    ) /* javascript-format */,
                    escaper.html(button.dataset.serviceLabel)
                )
            );
        }
    }
});
</script>
