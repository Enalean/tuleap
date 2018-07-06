export default {
    setFilteredRepositories(state, filtered_repositories) {
        state.filtered_repositories = filtered_repositories;
    },
    emptyFilteredRepositories(state) {
        state.filtered_repositories = [];
    },
    pushFilteredRepositories(state, filtered_repositories) {
        state.filtered_repositories.push(...filtered_repositories);
    }
};
