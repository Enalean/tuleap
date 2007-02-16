<?php
require_once('pre.php');
require_once('www/admin/admin_utils.php');
require_once('common/server/ServerFactory.class.php');
session_require(array('group'=>'1','admin_flags'=>'A'));

class ServerAdmin {
    var $server_factory;
    var $title;
    function ServerAdmin() {
        $this->server_factory = new ServerFactory();
        $this->title = 'Manage Servers';
    }
    function index(&$request) {
        return $this->all($request);
    }
    function all(&$request) {
        $html = '';
        $servers = $this->server_factory->getAllServers();
        if (count($servers)) {
            $title_arr = array(
                'Name',
                'Description',
                'HTTP',
                'HTTPS',
                '',
            );
            $html .= html_build_list_table_top($title_arr);
            $row_num = 0;
            foreach($servers as $key => $nop) {
                $html .= '<tr class="'. util_get_alt_row_color($row_num++) .'">';
                $html .= '<td><a title="Edit server" href="/admin/servers/edit/'. $servers[$key]->getId() .'">'. $servers[$key]->getName() .'</a></td>';
                $html .= '<td>'. $servers[$key]->getDescription() .'</td>';
                $html .= '<td>'. $servers[$key]->getHttp() .'</td>';
                $html .= '<td>'. $servers[$key]->getHttps() .'</td>';
                $html .= '<td><a title="Delete server" href="/admin/servers/delete/'. $servers[$key]->getId() .'">'. $GLOBALS['Response']->getImage('ic/trash.png', array('alt' => 'Delete server')) .'</a></td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
        } else {
            $html .= '<p>No servers</p>';
        }
        $html .= '<p><a href="/admin/servers/add">Add a server</a></p>';
        return $html;
    }
    function delete(&$request) {
        $server =& $this->server_factory->getServerById($request->get('id'));
        if (!$server) {
            $GLOBALS['Response']->addFeedback('error', 'Server not found');
            $GLOBALS['Response']->redirect('/admin/servers/');
        }
        $this->title = 'Delete server '. $server->getName();
        $html = '';
        $html .= '<form action="/admin/servers/destroy/'. $server->getId() .'" method="POST">';
        $html .= '<div style="border:medium solid red;background:#FFC;padding:4px 10px;">';
        $html .= '<h3>Are you sure ?</h3>';
        $html .= '<div style="text-align:center">';
        $html .= '<input type="submit" name="cancel" value="'. $GLOBALS['Language']->getText('global', 'btn_cancel') .'" /> ';
        $html .= '<input type="submit" name="confirm" value="Delete!" />';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</form>';
        return $html;
    }
    function destroy(&$request) {
        $server =& $this->server_factory->getServerById($request->get('id'));
        if (!$server) {
            $GLOBALS['Response']->addFeedback('error', 'Server not found');
        } else if (!$request->exist('cancel') && $this->server_factory->delete($request->get('id'))) {
            $GLOBALS['Response']->addFeedback('info', 'Deleted');
        } else {
            $GLOBALS['Response']->addFeedback('info', 'Not deleted');
        }
        $GLOBALS['Response']->redirect('/admin/servers/');
    }
    function add(&$request) {
        $this->title = 'Add a new server';
        $html = '';
        $html .= $this->_form(new Server($request->get('server')), '/admin/servers/create');
        return $html;
    }
    function _form(&$server, $action) {
        $html  = '<form action="'. $action .'" method="POST">';
        $html .= '<table>';
        $html .= '<tr><td>Name:</td><td><input type="text" name="server[name]" value="'. htmlentities($server->getName(), ENT_QUOTES) .'" /></td></tr>';
        $html .= '<tr><td>Description:</td><td><input type="text" name="server[description]" value="'. htmlentities($server->getDescription(), ENT_QUOTES) .'" /></td></tr>';
        $html .= '<tr><td>Http:</td><td><input type="text" name="server[http]" value="'. htmlentities($server->getHttp(), ENT_QUOTES) .'" /></td></tr>';
        $html .= '<tr><td>Https:</td><td><input type="text" name="server[https]" value="'. htmlentities($server->getHttps(), ENT_QUOTES) .'" /></td></tr>';
        
        $html .= '<tr><td><input type="hidden" name="server[id]" value="'. $server->getId() .'" /></td><td><input type="submit" name="cancel" value="'. $GLOBALS['Language']->getText('global', 'btn_cancel') .'" /> <input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /></td></tr>';
        $html .= '</table>';
        $html .= '</form>';
        return $html;
    }
    function create(&$request) {
        if ($request->exist('cancel')) {
            $GLOBALS['Response']->addFeedback('info', 'Not created');
        } else if ($this->server_factory->create($request->get('server'))) {
            $GLOBALS['Response']->addFeedback('info', 'Created');
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
            $GLOBALS['Response']->addFeedback('error', 'Server not found');
            $GLOBALS['Response']->redirect('/admin/servers/');
        } else {
            $this->title = 'Edit server '. $server->getName();
            $html .= $this->_form($server, '/admin/servers/update/'.$server->getId());
        }
        return $html;
    }
    function update(&$request) {
        $server =& $this->server_factory->getServerById($request->get('id'));
        if (!$server) {
            $GLOBALS['Response']->addFeedback('error', 'Server not found');
        } else if ($request->exist('cancel')) {
            $GLOBALS['Response']->addFeedback('info', 'Not updated');
        } else if ($this->server_factory->update($request->get('server'))) {
            $GLOBALS['Response']->addFeedback('info', 'Updated');
        } else {
            return $this->edit($request);
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
