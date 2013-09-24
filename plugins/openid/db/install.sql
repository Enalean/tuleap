DROP TABLE IF EXISTS plugin_openid_user_mapping;
CREATE TABLE plugin_openid_user_mapping (
    user_id int(11) NOT NULL,
    connexion_string text NOT NULL,
    INDEX idx_openid_mapping_userid(user_id),
    INDEX idx_openid_mapping_mixed(connexion_string(45), user_id)
);
