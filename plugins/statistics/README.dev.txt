Here is the readme for the developers...

===== I want to add my plugin in the disk usage statistics =====
Your plugin must implement the following 2 hooks:

// Stat plugin
$this->addHook('plugin_statistics_disk_usage_collect_project', 'plugin_statistics_disk_usage_collect_project', false);
$this->addHook('plugin_statistics_disk_usage_service_label',   'plugin_statistics_disk_usage_service_label',   false);

The first one to collect the disk space used per project for the service
The second one is to identify your plugin in the statistics plugin.

Example from docman:
    /**
     * Hook to collect docman disk size usage per project
     *
     * @param array $params
     */
    function plugin_statistics_disk_usage_collect_project($params) {
        $row  = $params['project_row'];
        $root = $this->getPluginInfo()->getPropertyValueForName('docman_root');
        $path = $root.'/'.strtolower($row['unix_group_name']);
        $params['DiskUsageManager']->storeForGroup($row['group_id'], 'plugin_docman', $path);
    }

    /**
     * Hook to list docman in the list of serices managed by disk stats
     * 
     * @param array $params
     */
    function plugin_statistics_disk_usage_service_label($params) {
        $params['services']['plugin_docman'] = 'Docman';
    }