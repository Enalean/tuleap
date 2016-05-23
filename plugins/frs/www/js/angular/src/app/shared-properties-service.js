angular
    .module('tuleap.frs')
    .service('SharedPropertiesService', SharedPropertiesService);

function SharedPropertiesService() {
    var property = {
        project_id           : null,
        release              : null,
        platform_license_info: null
    };

    return {
        getProjectId          : getProjectId,
        setProjectId          : setProjectId,
        getRelease            : getRelease,
        setRelease            : setRelease,
        getPlatformLicenseInfo: getPlatformLicenseInfo,
        setPlatformLicenseInfo: setPlatformLicenseInfo
    };

    function getProjectId() {
        return property.project_id;
    }

    function setProjectId(project_id) {
        property.project_id = project_id;
    }

    function getRelease() {
        return property.release;
    }

    function setRelease(release) {
        property.release = release;
    }

    function setPlatformLicenseInfo(platform_license_info) {
        property.platform_license_info = platform_license_info;
    }

    function getPlatformLicenseInfo() {
        return property.platform_license_info;
    }
}
