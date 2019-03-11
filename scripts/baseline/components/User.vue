<template>
    <span v-if="is_loading"
          class="tlp-skeleton-text"
          data-test-type="skeleton"
    >
    </span>
    <span v-else-if="is_loading_failed"
          v-bind:data-tlp-tooltip="loading_failure_message"
          class="tlp-text-danger tlp-tooltip tlp-tooltip-right"
          data-test-type="loading-error"
    >
        <i class="fa fa-exclamation-circle"></i>
    </span>
    <span v-else>
        {{ name }}
    </span>
</template>

<script>
import { getUser } from "../api/rest-querier";

export default {
    name: "User",

    props: {
        id: { required: true, type: Number }
    },

    data() {
        return {
            name: null,
            is_loading: false,
            is_loading_failed: false
        };
    },

    computed: {
        loading_failure_message() {
            return this.$gettext("Can't fetch user name");
        }
    },

    mounted() {
        this.fetchName();
    },

    methods: {
        async fetchName() {
            this.is_loading = true;
            this.is_loading_failed = false;
            this.name = null;

            try {
                const user = await getUser(this.id);
                this.name = user.username;
            } catch (e) {
                this.is_loading_failed = true;
            } finally {
                this.is_loading = false;
            }
        }
    }
};
</script>
