export default CommitsConfig;

CommitsConfig.$inject = ["$stateProvider"];

function CommitsConfig($stateProvider) {
    $stateProvider.state("commits", {
        url: "/commits",
        parent: "pull-request",
        views: {
            "commits@pull-request": {
                template: '<div commits id="commits"></div>',
            },
        },
    });
}
