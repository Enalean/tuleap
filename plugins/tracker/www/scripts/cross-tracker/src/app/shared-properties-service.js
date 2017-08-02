export default SharedPropertiesService;

function SharedPropertiesService() {
    var property = {
        user_id: undefined
    };

    return {
        getUserId: getUserId,
        setUserId: setUserId
    };

    function getUserId() {
        return property.user_id;
    }

    function setUserId(user_id) {
        property.user_id = user_id;
    }
}
