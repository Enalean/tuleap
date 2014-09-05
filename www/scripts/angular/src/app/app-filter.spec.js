describe('InPropertiesFilter', function() {
    beforeEach(module('testing'));

    var properties = ['label', 'status'],
        list = [
            {id: 1, label: 'Valid 7.11', status: 'First status'},
            {id: 2, label: 'Valid 7.11', status: 'Second status'},
            {id: 3, label: 'Valid 8', status: 'First status'},
            {id: 4, label: 'Valid 8 beta', status: 'First status'},
            {id: 5, label: 'Valid status', status: 'First status'},
            {id: 6, label: 'Valid 9', status: 'Plop'}
        ];

    it('it has a InPropertiesFilter filter', inject(function($filter) {
        expect($filter('InPropertiesFilter')).not.toBeNull();
    }));

    it('it filters on campaign label', inject(function($filter) {
        expect($filter('InPropertiesFilter')(list, 'beta', properties)).toContain(
            {id: 4, label: 'Valid 8 beta', status: 'First status'}
        );
    }));

    it('it filters on campaign status', inject(function($filter) {
        expect($filter('InPropertiesFilter')(list, 'First', properties)).toContain(
            {id: 1, label: 'Valid 7.11', status: 'First status'}
        );
        expect($filter('InPropertiesFilter')(list, 'First', properties)).toContain(
            {id: 3, label: 'Valid 8', status: 'First status'}
        );
        expect($filter('InPropertiesFilter')(list, 'First', properties)).toContain(
            {id: 4, label: 'Valid 8 beta', status: 'First status'}
        );
        expect($filter('InPropertiesFilter')(list, 'First', properties)).toContain(
            {id: 5, label: 'Valid status', status: 'First status'}
        );
    }));

    it('it filters on both status', inject(function($filter) {
        expect($filter('InPropertiesFilter')(list, 'status', properties)).toContain(
            {id: 1, label: 'Valid 7.11', status: 'First status'}
        );
        expect($filter('InPropertiesFilter')(list, 'status', properties)).toContain(
            {id: 2, label: 'Valid 7.11', status: 'Second status'}
        );
        expect($filter('InPropertiesFilter')(list, 'status', properties)).toContain(
            {id: 3, label: 'Valid 8', status: 'First status'}
        );
        expect($filter('InPropertiesFilter')(list, 'status', properties)).toContain(
            {id: 4, label: 'Valid 8 beta', status: 'First status'}
        );
        expect($filter('InPropertiesFilter')(list, 'status', properties)).toContain(
            {id: 5, label: 'Valid status', status: 'First status'}
        );
    }));
});