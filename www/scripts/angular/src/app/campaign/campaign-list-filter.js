angular
    .module('campaign')
    .filter('CampaignListFilter', CampaignListFilter);

CampaignListFilter.$inject = ['$filter'];

function CampaignListFilter($filter) {
    return function(list, search) {
        if (! search) {
            return list;
        }

        var keywords = search.split(' '),
            lookup   = '',
            result   = [];

        keywords.forEach(function(keyword) {
            lookup = $filter('filter')(list, {'name': keyword});
            if (lookup.length > 0) {
                result = result.concat(lookup);
            }

            lookup = $filter('filter')(list, {'status': keyword});
            if (lookup.length > 0) {
                result = result.concat(lookup);
            }
        });

        return _.sortBy(_.uniq(result, function(campaign) { return campaign.id; }), 'id').reverse();
    };
}