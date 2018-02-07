import striptags from 'striptags';

export default TuleapStripTagsFilter;

TuleapStripTagsFilter.$inject = [];

function TuleapStripTagsFilter() {
    return function(html) {
        return striptags(html);
    };
}
