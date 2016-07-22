<?php
/**
  * Copyright (c) Enalean, 2013. All rights reserved
  *
  * This file is a part of Tuleap.
  *
  * Tuleap is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or
  * (at your option) any later version.
  *
  * Tuleap is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */
require_once('common/plugin/Plugin.class.php');
require_once('autoload.php');
require_once 'constants.php';

class BoomerangPlugin extends Plugin {

    const RENDERER_TYPE = 'plugin_boomerang';

    /**
     * Plugin constructor
     */
    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_SYSTEM);
    }

    public function getHooksAndCallbacks() {
        if (defined('CARDWALL_EVENT_DISPLAYED')) {
            $this->_addHook(CARDWALL_EVENT_DISPLAYED);
        }
        $this->_addHook('site_admin_option_hook', 'siteAdminHooks', false);
        $this->_addHook('cssfile');
        return parent::getHooksAndCallbacks();
    }

    public function cssfile($params) {
        // Only show the stylesheet if we're actually in the Boomerang plugin page.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="css/barChart.css" />';
        }
    }

    public function process(Codendi_Request $request){
        switch ($request->get('action')) {
            case 'provide_datas':
                header('Content-type : text/csv');
                echo file_get_contents($this->getCacheFolder() . 'data.csv');
                break;
            case 'beacon':
                $this->processBoomerangDatas($request);
                break;
            default:
                require_once 'common/templating/TemplateRendererFactory.class.php';
                $header_params = array(
                    'title' => 'Boomerang'
                );
                site_header($header_params);
                $renderer = TemplateRendererFactory::build()->getRenderer(BOOMERANG_BASE_DIR.'/../templates');
                $presenter = new PerfDataPresenter();
                $renderer->renderToPage('perf-data', $presenter);
                site_footer(null);
                break;
        }
    }

    public function cardwall_event_displayed($params) {
        $token = $this->getCSRFToken();
        $params['html'] .= '<script src=/plugins/boomerang/js/boomerang-minified-bw.js></script>' . PHP_EOL;
        $params['html'] .= '
            <script type="text/javascript">
                BOOMR.init({
                    beacon_url: "/plugins/boomerang/?action=beacon&'.$token->getTokenName().'='.$token->getToken().'"
                });
            </script>
        ' . PHP_EOL;
    }

    /**
     * Obtain ArchiveDeletedItemsPluginInfo instance
     *
     * @return ArchiveDeletedItemsPluginInfo
     */
    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'BoomerangPluginInfo')) {
            $this->pluginInfo = new BoomerangPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    private function getCSRFToken() {
        return new CSRFSynchronizerToken('/plugins/boomerang/?action=beacon');
    }

    private function getCacheFolder() {
        return $GLOBALS['sys_data_dir'] . '/boomerang/';
    }

    private function makeArrayFromStringHashTable($string_hash_map) {
        $array_temp = split(',', $string_hash_map);
        $hash_table = array();
        foreach ($array_temp as $line) {
            $line_content = split('\|', $line);
            $hash_table[$line_content[0]] = $line_content[1];
        }
        return $hash_table;
    }

    private function processBoomerangDatas(Codendi_Request $request) {
        $csrf = $this->getCSRFToken();
        $csrf->check();

        $cache_folder =  $this->getCacheFolder();
        if (!file_exists($cache_folder)) {
            mkdir($cache_folder, 0755, TRUE);
        }

        $page_load_time             = $request->getValidated('t_done','uint',0);

        $dom_content_load_time      = 0;
        $boomerang_other_measures   = $this->makeArrayFromStringHashTable($request->getValidated('t_other','string',''));
        if(array_key_exists('boomr_fb', $boomerang_other_measures)) {
            $dom_content_load_time = (int)$boomerang_other_measures['boomr_fb'];
            $dom_content_load_time = ($dom_content_load_time > 0) ? $dom_content_load_time : 0 ;
        }

        parse_str(
            parse_url(
                $request->getValidated('u','string',''),
                PHP_URL_QUERY
            ),
            $url_parameters
        );
        $group_id = array_key_exists('group_id',$url_parameters) ? $url_parameters['group_id'] : null;
        $project_manager = ProjectManager::instance();
        if(!$project_manager->getProject($group_id)) {
            $group_id = 0;
        }

        $datas = array(
            "page_loading"  => $page_load_time,
            "dom_loading"   => $dom_content_load_time
        );
        if($datas["page_loading"] == 0 || $datas["dom_loading"] == 0 || $group_id == 0) {
            exit();
        }
        $boomerangDatasProcessor = new BoomerangDatasProcessor($cache_folder, $datas, $group_id);
        $boomerangDatasProcessor->handle();
    }

    /**
	 * for hook administration :display an URL to access Boomerang statistics..
	 * @param array $params:contains the data which comes from the envent listened.
	 */
    function siteAdminHooks($params) {
       global $Language;
       $link_title= $GLOBALS['Language']->getText('plugin_boomerang','link_boomerang_admin_title');
       echo '<li><a href="'.$this->getPluginPath().'/">'.$link_title.'</a></li>';
    }
}

?>