<?php
require_once('pre.php');
require_once('www/admin/admin_utils.php');
require_once('common/server/ServerFactory.class.php');
require_once('common/dao/ServiceDao.class.php');
require_once('Project.class.php');
session_require(array('group'=>'1','admin_flags'=>'A'));

class ServerAdmin {
    var $server_factory;
    var $title;
    function ServerAdmin() {
        $this->server_factory = new ServerFactory();
        $this->title = $GLOBALS['Language']->getText('admin_main', 'servers_admin');
    }
    function index(&$request) {
        return $this->all($request);
    }
    function all(&$request) {
        $html = '';
        $servers = $this->server_factory->getAllServers();
        if (count($servers)) {
            $title_arr = array(
                $GLOBALS['Language']->getText('admin_servers', 'all_name'),
                $GLOBALS['Language']->getText('admin_servers', 'all_description'),
                'HTTP',
                'HTTPS',
                $GLOBALS['Language']->getText('admin_servers', 'all_is_master'),
                '',
            );
            $html .= html_build_list_table_top($title_arr);
            $row_num = 0;
            foreach($servers as $key => $nop) {
                $html .= '<tr class="'. util_get_alt_row_color($row_num++) .'">';
                $html .= '<td><a title="'. $GLOBALS['Language']->getText('admin_servers', 'all_edit', array(htmlentities($servers[$key]->getName(), ENT_QUOTES))) .'" href="/admin/servers/edit/'. $servers[$key]->getId() .'">'. $servers[$key]->getId() .'. '. $servers[$key]->getName() .'</a></td>';
                $html .= '<td>'. $servers[$key]->getDescription() .'</td>';
                $html .= '<td>'. $servers[$key]->getHttp() .'</td>';
                $html .= '<td>'. $servers[$key]->getHttps() .'</td>';
                $html .= '<td style="text-align:center">'. ($servers[$key]->isMaster() ? $GLOBALS['Language']->getText('admin_servers', 'all_master') : '-') .'</td>';
                $html .= '<td><a title="'. $GLOBALS['Language']->getText('admin_servers', 'all_delete', array(htmlentities($servers[$key]->getName(), ENT_QUOTES))) .'" href="/admin/servers/delete/'. $servers[$key]->getId() .'">'. $GLOBALS['Response']->getImage('ic/trash.png', array('alt' => 'Delete server')) .'</a></td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        } else {
            $html .= '<p>No servers</p>';
        }
        $html .= '<p><a href="/admin/servers/add">'. $GLOBALS['Language']->getText('admin_servers', 'all_add') .'</a> ';
        if (count($servers)) {
            $html .= '| <a href="/admin/servers/master">'. $GLOBALS['Language']->getText('admin_servers', 'all_choose') .'</a>';
        }
        $html .= '</p>';
        return $html;
    }
    function delete(&$request) {
        $server =& $this->server_factory->getServerById($request->get('id'));
        if (!$server) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_servers', 'error_notfound'));
            $GLOBALS['Response']->redirect('/admin/servers/');
        }
        $this->title = $GLOBALS['Language']->getText('admin_servers', 'all_delete', array($server->getName()));
        $html = '';
        $html .= '<form action="/admin/servers/destroy/'. $server->getId() .'" method="POST">';
        $html .= '<div style="border:medium solid red;background:#FFC;padding:4px 10px;">';
        $html .= '<h3>'. $GLOBALS['Language']->getText('admin_servers', 'delete_R_U_sure') .'</h3>';
        //{{{ Fetch services that use this server
        $service_dao =& new ServiceDao(CodeXDataAccess::instance());
        $dar =& $service_dao->searchByServerId($server->getId());
        if ($dar) {
            $matches = array();
            $projects = array();
            $GLOBALS['Language']->loadLanguageMsg('project/project');
            $em =& EventManager::instance();
            $em->processEvent("plugin_load_language_file", null);
            while ($dar->valid()) {
                $row = $dar->current();
                $label = $row['label'];
                if ($label == "service_".$row['short_name']."_lbl_key") {
                  $label = $GLOBALS['Language']->getText('project_admin_editservice', $label);
                } else if(preg_match('/(.*):(.*)/', $label, $matches)) {
                    $label = $GLOBALS['Language']->getText($matches[1], $matches[2]);
                }
                $projects[$row['group_id']][] = $label;
                $dar->next();
            }
            if (count($projects)) {
                $html .= '<p>'. $GLOBALS['Language']->getText('admin_servers', 'delete_using', $server->getName()) .'</p>';
                $html .= '<dl>';
                foreach($projects as $project_id => $services) {
                    if ($p =& project_get_object($project_id)) {
                        $html .= '<dt><b><a title="Project admin" href="/project/admin/?group_id='. $project_id .'">'. $p->getPublicName() .'</a></b></dt>';
                        $html .= '<dd><ul>';
                        $html .= '<li>'. implode('</li><li>', $services) .'</li>';
                        $html .= '</ul></dd>';
                    }
                }
                $html .= '</dl>';
            }
        }
        //}}}
        $html .= '<div style="text-align:center">';
        $html .= '<input type="submit" name="cancel" value="'. $GLOBALS['Language']->getText('global', 'btn_cancel') .'" /> ';
        $html .= '<input type="submit" name="confirm" value="'. $GLOBALS['Language']->getText('admin_servers', 'delete') .'" />';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</form>';
        return $html;
    }
    function destroy(&$request) {
        $server =& $this->server_factory->getServerById($request->get('id'));
        if (!$server) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_servers', 'error_notfound'));
        } else if ($request->exist('cancel')) {
            //$GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('admin_servers', 'info_notdeleted', array($server->getName())));
        } else if ($this->server_factory->delete($request->get('id'))) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('admin_servers', 'info_deleted', array($server->getName())));
        } else {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('admin_servers', 'info_notdeleted', array($server->getName())));
        }
        $GLOBALS['Response']->redirect('/admin/servers/');
    }
    function add(&$request) {
        $this->title = $GLOBALS['Language']->getText('admin_servers', 'all_add');
        $html = '';
        $html .= $this->_form(new Server($request->get('server')), '/admin/servers/create');
        return $html;
    }
    function _form(&$server, $action) {
        $html  = '<form action="'. $action .'" method="POST">';
        $html .= '<table>';
        $html .= '<tr><td>'. $GLOBALS['Language']->getText('admin_servers', 'form_id') .'</td><td><input type="text" name="server[id]" value="'. $server->getID() .'" /></td></tr>';
        $html .= '<tr><td>'. $GLOBALS['Language']->getText('admin_servers', 'form_name') .'</td><td><input type="text" name="server[name]" value="'. htmlentities($server->getName(), ENT_QUOTES) .'" /></td></tr>';
        $html .= '<tr><td>'. $GLOBALS['Language']->getText('admin_servers', 'form_descr') .'</td><td><input type="text" name="server[description]" value="'. htmlentities($server->getDescription(), ENT_QUOTES) .'" /></td></tr>';
        $html .= '<tr><td>'. $GLOBALS['Language']->getText('admin_servers', 'form_http') .'</td><td><input type="text" name="server[http]" value="'. htmlentities($server->getHttp(), ENT_QUOTES) .'" /></td></tr>';
        $html .= '<tr><td>'. $GLOBALS['Language']->getText('admin_servers', 'form_https') .'</td><td><input type="text" name="server[https]" value="'. htmlentities($server->getHttps(), ENT_QUOTES) .'" /></td></tr>';
        
        $html .= '<tr><td></td><td><input type="submit" name="cancel" value="'. $GLOBALS['Language']->getText('global', 'btn_cancel') .'" /> <input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /></td></tr>';
        $html .= '</table>';
        $html .= '</form>';
        //$html .= '<p><a href="/admin/servers/">Go back to servers</a></p>';
        return $html;
    }
    function create(&$request) {
        if ($request->exist('cancel')) {
            //$GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('admin_servers', 'info_notcreated'));
        } else if ($this->server_factory->create($request->get('server'))) {
            $server = $request->get('server');
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('admin_servers', 'info_created', array($server['name'])));
        } else {
            return $this->add($request);
        }
        $GLOBALS['Response']->redirect('/admin/servers/');
    }
    function edit(&$request) {
        $html = '';
        if ($request->exist('server')) {
            $server =& new Server($request->get('server'));
        } else if ($request->exist('id')) {
            $server =& $this->server_factory->getServerById($request->get('id'));
        }
        if (!$server) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_servers', 'error_notfound'));
            $GLOBALS['Response']->redirect('/admin/servers/');
        } else {
            $this->title = $GLOBALS['Language']->getText('admin_servers', 'all_edit', array($server->getName()));
            $html .= $this->_form($server, '/admin/servers/update/'.$server->getId());
        }
        return $html;
    }
    function update(&$request) {
        $server =& $this->server_factory->getServerById($request->get('id'));
        if (!$server) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_servers', 'error_notfound'));
        } else if ($request->exist('cancel')) {
            //$GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('admin_servers', 'info_notupdated', array($server->getName())));
        } else if ($this->server_factory->update($request->get('id'), $request->get('server'))) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('admin_servers', 'info_updated', array($server->getName())));
        } else {
            return $this->edit($request);
        }
        $GLOBALS['Response']->redirect('/admin/servers/');
    }
    function master(&$request) {
        $servers = $this->server_factory->getAllServers();
        if (count($servers)) {
            $this->title = $GLOBALS['Language']->getText('admin_servers', 'all_choose');
            $html  = '<form action="/admin/servers/setmaster" method="POST">';
            $html .= 'Server: <select name="master">';
            foreach($servers as $key => $nop) {
                $selected = $servers[$key]->isMaster() ? 'selected="selected"' : '';
                $html .= '<option value="'. $servers[$key]->getId(). '" '. $selected .'>'. $servers[$key]->getName() .'</option>';
            }
            $html .= '</select>';
            $html .= '<br />';
            $html .= '<input type="submit" name="cancel" value="'. $GLOBALS['Language']->getText('global', 'btn_cancel') .'" /> ';
            $html .= '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
            return $html;
        } else {
            $GLOBALS['Response']->addFeedback('error', 'There is no server');
            $GLOBALS['Response']->redirect('/admin/servers/');
        }
    }
    function setmaster(&$request) {
        $server =& $this->server_factory->getServerById($request->get('master'));
        if (!$server) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_servers', 'error_notfound'));
        } else if ($request->exist('cancel')) {
            //$GLOBALS['Response']->addFeedback('info', 'Not updated');
        } else if ($this->server_factory->setMaster($request->get('master'))) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('admin_servers', 'info_mastered', array($server->getName())));
        } else {
            return $this->master($request);
        }
        $GLOBALS['Response']->redirect('/admin/servers/');
    }
}

//Process request
if (!isset($_GET['r']) || !$_GET['r']) {
    $_REQUEST['action'] = 'index';
} else {
    $r = explode('/', $r);
    $_REQUEST['action'] = $r[0];
    if (isset($r[1])) {
        $_REQUEST['id'] = $r[1];
    }
}
require_once('common/include/HTTPRequest.class.php');
$request =& HTTPRequest::instance();

$server_admin =& new ServerAdmin();
$method = $request->get('action');
if (method_exists($server_admin, $method)) {
    $html = $server_admin->$method($request);
    site_admin_header(array('title'=> $server_admin->title));
    echo '<h3>'. $server_admin->title .'</h3>';
    echo $html;
    site_admin_footer(array());
} else {
    die('Invalid action '. $method);
}

?>
