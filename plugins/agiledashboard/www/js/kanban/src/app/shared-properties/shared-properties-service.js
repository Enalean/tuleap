(function () {
    angular
        .module('shared-properties')
        .service('SharedPropertiesService', SharedPropertiesService);

    function SharedPropertiesService() {
        var property = {
            name: name
        };

        return {
            getName: getName,
            setName: setName
        };

        function getName() {
            return property.name;
        }

        function setName(name) {
            property.name = name;
        }
    }
})();