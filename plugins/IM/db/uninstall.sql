##
## Sql Uninstall Script for IM plugin
##

# Remove IM Admin  user and group.
DELETE FROM groups WHERE group_id='99';
DELETE FROM user   WHERE user_id ='99';
DELETE FROM service WHERE group_id='99';
DELETE FROM user_group WHERE user_id='99';

DELETE FROM reference_group 
WHERE reference_id IN (SELECT id FROM reference WHERE service_short_name='IM');

DELETE FROM reference WHERE service_short_name='IM';
DELETE FROM service WHERE short_name='IM';

DELETE FROM user_access WHERE user_id = 99;
