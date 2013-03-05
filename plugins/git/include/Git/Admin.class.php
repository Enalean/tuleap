<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once GIT_BASE_DIR .'/Git/RemoteServer/GerritServerFactory.class.php';

/**
 * This handles site admin part of Git
 */
class Git_Admin {

    /** @var Git_RemoteServer_GerritServerFactory */
    private $gerrit_server_factory;

    /** @var CSRFSynchronizerToken */
    private $csrf;

    public function __construct(Git_RemoteServer_GerritServerFactory $gerrit_server_factory, CSRFSynchronizerToken $csrf) {
        $this->gerrit_server_factory = $gerrit_server_factory;
        $this->csrf                  = $csrf;
    }

    public function process(Codendi_Request $request) {
        $request_gerrit_servers = $request->get('gerrit_servers');
        if (is_array($request_gerrit_servers)) {
            $this->csrf->check();
            $gerrit_servers = $this->getGerritServers();
            $this->updateServers($request_gerrit_servers, $gerrit_servers);
        }
    }

    public function display() {
        $servers = $this->getGerritServers();
        $title = $GLOBALS['Language']->getText('plugin_git', 'descriptor_name');
        $GLOBALS['HTML']->header(array('title' => $title, 'selected_top_tab' => 'admin'));
        $html  = '';
        $html .= '<h1>'. $title .'</h1>';
        $html .= '<div class="alert-message block-message warning">';
        $html .= 'This feature is not finished yet. Use it only if you know what you are doing!';
        $html .= '</div>';
        $html .= '<form method="POST" action="">';
        $html .= $this->csrf->fetchHTMLInput();
        $html .= '<h2>'. 'Admin gerrit servers' .'</h2>';
        $html .= '<dl>';
        foreach ($servers as $server) {
            $html .= $this->getInputForm($server->getHost(), $server);
        }
        $html .= '</dl>';
        $html .= '<p><input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /></p>';
        $html .= '</form>';
        echo $html;

        $GLOBALS['HTML']->footer(array());
    }

    /**
     * @return string
     */
    private function getInputForm($title, Git_RemoteServer_GerritServer $server) {
        $hp    = Codendi_HTMLPurifier::instance();
        $id    = (int)$server->getId();
        $title = 'Add new gerrit server';
        if ($id) {
            $title = $server->getHost();;
        }

        $html  = '';
        $html .= '<dt><h3>'. $title .'</h3></dt>';
        $html .= '<dd>';
        $html .= '<table><tbody>';
        $fields = array(array('Host:',          'host',          $server->getHost()), 
                        array('HTTP Port:',     'http_port',     $server->getHTTPPort()),
                        array('SSH Port:',      'ssh_port',      $server->getSSHPort()),
                        array('Login:',         'login',         $server->getLogin()),
                        array('Identity File:', 'identity_file', $server->getIdentityFile()));
        foreach ($fields as $field) {
            $html .= '<td><label>'. $field[0].'<br /><input type="text" name="gerrit_servers['. $id .']['.$field[1].']" value="'. $hp->purify($field[2]) .'" /></label></td>';
        }
        if ($id && ! $this->gerrit_server_factory->isServerUsed($server)) {
            $html .= '<td><label>'. 'Delete?' .'<br /><input type="checkbox" name="gerrit_servers['. $id .'][delete]" value="1" /></label></td>';
        }
        $html .= '</tbody></table>';
        $html .= '</dd>';
        return $html;
    }

    /**
     * @return array of Git_RemoteServer_GerritServer
     */
    private function getGerritServers() {
        $servers = $this->gerrit_server_factory->getServers();
        $servers["0"] = new Git_RemoteServer_GerritServer(0, '', '', '', '', '', '');
        return $servers;
    }

    private function updateServers(array $request_gerrit_servers, array $gerrit_servers) {
        foreach ($request_gerrit_servers as $id => $settings) {
            if (empty($gerrit_servers[$id])) {
                continue;
            }
            if (! empty($settings['delete'])) {
                $this->gerrit_server_factory->delete($gerrit_servers[$id]);
                continue;
            }

            $host          = isset($settings['host'])          ? $settings['host']          : '';
            $ssh_port      = isset($settings['ssh_port'])      ? $settings['ssh_port']      : '';
            $http_port     = isset($settings['http_port'])     ? $settings['http_port']     : '';
            $login         = isset($settings['login'])         ? $settings['login']         : '';
            $identity_file = isset($settings['identity_file']) ? $settings['identity_file'] : '';
            if ($host &&
                $host != $gerrit_servers[$id]->getHost() ||
                $ssh_port != $gerrit_servers[$id]->getSSHPort() ||
                $http_port != $gerrit_servers[$id]->getHTTPPort() ||
                $login != $gerrit_servers[$id]->getLogin() ||
                $identity_file != $gerrit_servers[$id]->getIdentityFile()
            ) {
                $gerrit_servers[$id]
                    ->setHost($host)
                    ->setSSHPort($ssh_port)
                    ->setHTTPPort($http_port)
                    ->setLogin($login)
                    ->setIdentityFile($identity_file);
                $this->gerrit_server_factory->save($gerrit_servers[$id]);
            }
        }
    }
}
?>
