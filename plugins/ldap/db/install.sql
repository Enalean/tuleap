DROP TABLE IF EXISTS plugin_ldap_svn_repository;
CREATE TABLE plugin_ldap_svn_repository (
    group_id int(11) NOT NULL,
    ldap_auth TINYINT NOT NULL default 0,
    INDEX idx_group_id(group_id)
);

DROP TABLE IF EXISTS plugin_ldap_user;
CREATE TABLE plugin_ldap_user (
    user_id int(11) NOT NULL default 0,
    login_confirmation_date int(11) NOT NULL default 0,
    ldap_uid VARCHAR(255) NOT NULL default '',
    PRIMARY KEY  (user_id),
    INDEX idx_ldap_uid(ldap_uid(10))
);

DROP TABLE IF EXISTS plugin_ldap_ugroup;
CREATE TABLE plugin_ldap_ugroup (
    ugroup_id int(11) NOT NULL default 0,
    ldap_group_dn VARCHAR(255) NOT NULL default 0,
    synchro_policy VARCHAR(255) NOT NULL default 'never',
    bind_option varchar(255) NOT NULL default 'bind',
    PRIMARY KEY  (ugroup_id),
    UNIQUE (ugroup_id, ldap_group_dn)
);

DROP TABLE IF EXISTS plugin_ldap_project_group;
CREATE TABLE plugin_ldap_project_group (
    group_id int(11) NOT NULL default 0,
    ldap_group_dn VARCHAR(255) NOT NULL default 0,
    synchro_policy VARCHAR(255) NOT NULL default 'never',
    bind_option varchar(255) NOT NULL default 'bind',
    PRIMARY KEY  (group_id, ldap_group_dn)
);
DROP TABLE IF EXISTS plugin_ldap_suspended_user;
CREATE TABLE plugin_ldap_suspended_user (
    user_id int(11) NOT NULL,
    deletion_date int(11) NOT NULL
);
