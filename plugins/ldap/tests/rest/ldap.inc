<?php

$sys_ldap_server = 'ldap://ldap';
$sys_ldap_dn     = 'dc=tuleap,dc=local';

$sys_ldap_uid   = 'uid';
$sys_ldap_eduid = 'employeeNumber';
$sys_ldap_cn    = 'cn';
$sys_ldap_mail  = 'mail';
$sys_ldap_people_dn     = 'ou=people,dc=tuleap,dc=local';
$sys_ldap_search_user='(|(uid=%words%)(cn=%words%)(mail=%words%))';
$sys_ldap_tooltip_search_user='(|(uid=%words%*)(cn=%words%*)(mail=%words%*))';
$sys_ldap_default_user_status = 'P';
$sys_ldap_svn_auth = '0';
$sys_ldap_daily_sync = '0';
$sys_ldap_daily_sync_retention_period = '';
$sys_ldap_user_management = '1';
$sys_ldap_grp_enabled   = '1';

$sys_ldap_grp_dn        = 'ou=groups,dc=tuleap,dc=local';
$sys_ldap_grp_cn        = 'cn';
$sys_ldap_grp_member    = 'uniquemember';
