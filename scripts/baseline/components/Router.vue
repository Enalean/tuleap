<template>
    <component v-bind:is="route.component" v-bind:project_id="project_id"/>
</template>

<script>
import routes from "./routes";

export default {
    name: "Router",

    props: {
        project_id: { required: false, type: Number }
    },

    data() {
        return {
            current_route: window.location.pathname
        };
    },

    computed: {
        route() {
            const captured_route = this.current_route.match(/^\/plugins\/baseline\/.*$/);

            if (!captured_route) {
                return routes.not_found;
            }

            return routes.home;
        }
    },

    watch: {
        route(route) {
            window.history.pushState({}, route.title, this.current_route);
        }
    },

    mounted() {
        window.addEventListener("popstate", this.handlePopState);
    },

    destroyed() {
        window.removeEventListener("popstate", this.handlePopState);
    },

    methods: {
        handlePopState() {
            this.current_route = window.location.pathname;
        }
    }
};
</script>
