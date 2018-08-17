<?php

class DatabaseForge extends DatabaseMysqli {
    function __construct($params) {
            global $wgDBtype;

            $params['schema'] = null;
            $wgDBtype = "mysql";
            parent::__construct($params);
    }

    function tableName($name, $format = 'quoted') {
            switch ($name) {
            case 'interwiki':
                    return ForgeConfig::get('sys_dbname').'.plugin_mediawiki_interwiki';
            default:
                    return parent::tableName($name, $format);
            }
    }
}