<template>
    <component
        v-bind:is="route.component"
        v-bind:project_id="project_id"
        v-bind:baseline_id="baseline_id"
    />
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
            const captured_route_path = this.current_route.match(
                /^\/plugins\/baseline\/baselines\/(\d+)|(\/plugins\/baseline\/.+)$/
            );

            if (!captured_route_path) {
                return routes.not_found;
            }

            if (captured_route_path[1]) {
                return { ...routes.baseline, params: { id: captured_route_path[1] } };
            }

            return routes.home;
        },

        baseline_id() {
            if (this.route.component !== routes.baseline.component) {
                return null;
            }

            return Number(this.route.params.id);
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
