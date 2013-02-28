INSERT
    INTO service(
            `group_id`,
            `label`,
            `description`,
            `short_name`,
            `link`,
            `is_active`,
            `is_used`,
            `scope`,
            `rank`
        )
    VALUES( 
        100,
        'plugin_mediawiki:service_lbl_key',
        'plugin_mediawiki:service_desc_key',
        'plugin_mediawiki',
        '/plugins/mediawiki/wiki/$projectname',
        1,
        1,
        'system',
        160
    );

INSERT
    INTO service(
        `group_id`,
        `label`,
        `description`,
        `short_name`,
        `link`,
        `is_active`,
        `is_used`,
        `scope`,
        `rank`
        )
    SELECT DISTINCT
        service.group_id ,
        'plugin_mediawiki:service_lbl_key' ,
        'plugin_mediawiki:service_desc_key' ,
        'plugin_mediawiki',
        CONCAT('/plugins/mediawiki/wiki/',
        groups.unix_group_name),
        1 ,
        0 ,
        'system',
        160
    FROM service
    JOIN groups
        ON (groups.group_id = service.group_id)
    WHERE service.group_id != 100;