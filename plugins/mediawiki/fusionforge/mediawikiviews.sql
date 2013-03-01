create view group_plugin as (select service.service_id group_plugin_id,service.group_id,plugin.id plugin_id,service.short_name from service,plugin where service.short_name=plugin.name and service.is_active=1 and service.is_used=1 and service.group_id != 100);
create view plugins as (select id plugin_id, name plugin_name, name plugin_desc from plugin );
