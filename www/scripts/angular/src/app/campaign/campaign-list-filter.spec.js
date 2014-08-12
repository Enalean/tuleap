describe('CampaignListFilter', function() {
    beforeEach(module('ui.router'));
    beforeEach(module('campaign'));

    var list = [
        {id: 1, label: 'Valid 7.11', status: 'First status'},
        {id: 2, label: 'Valid 7.11', status: 'Second status'},
        {id: 3, label: 'Valid 8', status: 'First status'},
        {id: 4, label: 'Valid 8 beta', status: 'First status'},
        {id: 5, label: 'Valid status', status: 'First status'},
        {id: 6, label: 'Valid 9', status: 'Plop'}
    ];

    it('it has a CampaignListFilter filter', inject(function($filter) {
        expect($filter('CampaignListFilter')).not.toBeNull();
    }));

    it('it filters on campaign label', inject(function($filter) {
        expect($filter('CampaignListFilter')(list, 'beta')).toEqual([
            {id: 4, label: 'Valid 8 beta', status: 'First status'}
        ]);
    }));

    it('it filters on campaign status', inject(function($filter) {
        expect($filter('CampaignListFilter')(list, 'First')).toEqual([
            {id: 5, label: 'Valid status', status: 'First status'},
            {id: 4, label: 'Valid 8 beta', status: 'First status'},
            {id: 3, label: 'Valid 8', status: 'First status'},
            {id: 1, label: 'Valid 7.11', status: 'First status'}
        ]);
    }));

    it('it filters on both status', inject(function($filter) {
        expect($filter('CampaignListFilter')(list, 'status')).toEqual([
            {id: 5, label: 'Valid status', status: 'First status'},
            {id: 4, label: 'Valid 8 beta', status: 'First status'},
            {id: 3, label: 'Valid 8', status: 'First status'},
            {id: 2, label: 'Valid 7.11', status: 'Second status'},
            {id: 1, label: 'Valid 7.11', status: 'First status'}
        ]);
    }));
});