angular
    .module('campaign')
    .service('CampaignService', CampaignService);

CampaignService.$inject = ['Restangular', '$q'];

function CampaignService(Restangular, $q) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl('/api/v1');
    });

    return {
        getCampaigns: getCampaigns,
        getAssignees: getAssignees
    };

    function getCampaigns(project_id, limit, offset) {
        var data = $q.defer();

        rest.one('projects', project_id)
            .all('campaigns')
            .getList({
                limit: limit,
                offset: offset
            })
            .then(function(response) {
                result = {
                    results: response.data,
                    total: response.headers('X-PAGINATION-SIZE')
                };

                data.resolve(result);
            });

        return data.promise;
    }

    function getAssignees(campaign_id, limit, offset) {
        return [
            {
                "id": 101,
                "uri": "users/101",
                "email": "hugo@example.com",
                "real_name": "hugo",
                "username": "hugo",
                "ldap_id": "",
                "avatar_url": "https://paelut/users/hugo/avatar.png"
            },
            {
                "id": 102,
                "uri": "users/102",
                "email": "nico@example.com",
                "real_name": "nico",
                "username": "nico",
                "ldap_id": "",
                "avatar_url": "https://paelut/users/nico/avatar.png"
            }
        ];

        // var data = $q.defer();

        // rest.one('campaigns', project_id)
        //     .all('assignees')
        //     .getList({
        //         limit: limit,
        //         offset: offset
        //     })
        //     .then(function(response) {
        //         result = {
        //             results: response.data,
        //             total: response.headers('X-PAGINATION-SIZE')
        //         };

        //         data.resolve(result);
        //     });

        // return data.promise;
    }
}