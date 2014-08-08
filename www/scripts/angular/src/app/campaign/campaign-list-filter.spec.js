describe('CampaignListFilter', function() {
    beforeEach(module('ui.router'));
    beforeEach(module('campaign'));

    var list = [
        {id: 1, name: 'Valid 7.11', status: 'First status'},
        {id: 2, name: 'Valid 7.11', status: 'Second status'},
        {id: 3, name: 'Valid 8', status: 'First status'},
        {id: 4, name: 'Valid 8 beta', status: 'First status'},
        {id: 5, name: 'Valid status', status: 'First status'},
        {id: 6, name: 'Valid 9', status: 'Plop'}
    ];

    it('it has a CampaignListFilter filter', inject(function($filter) {
        expect($filter('CampaignListFilter')).not.toBeNull();
    }));

    it('it filters on campaign name', inject(function($filter) {
        expect($filter('CampaignListFilter')(list, 'beta')).toEqual([
            {id: 4, name: 'Valid 8 beta', status: 'First status'}
        ]);
    }));

    it('it filters on campaign status', inject(function($filter) {
        expect($filter('CampaignListFilter')(list, 'First')).toEqual([
            {id: 5, name: 'Valid status', status: 'First status'},
            {id: 4, name: 'Valid 8 beta', status: 'First status'},
            {id: 3, name: 'Valid 8', status: 'First status'},
            {id: 1, name: 'Valid 7.11', status: 'First status'}
        ]);
    }));

    it('it filters on both status', inject(function($filter) {
        expect($filter('CampaignListFilter')(list, 'status')).toEqual([
            {id: 5, name: 'Valid status', status: 'First status'},
            {id: 4, name: 'Valid 8 beta', status: 'First status'},
            {id: 3, name: 'Valid 8', status: 'First status'},
            {id: 2, name: 'Valid 7.11', status: 'Second status'},
            {id: 1, name: 'Valid 7.11', status: 'First status'}
        ]);
    }));
});