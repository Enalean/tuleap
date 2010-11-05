<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This is a web based WebDAV client added as a plugin into the WebDAV server
 */
class BrowserPlugin extends Sabre_DAV_Browser_Plugin {

    /**
     * Shows the delete form
     *
     * @param mixed $file
     *
     * @return void
     */
    function deleteForm($file) {
        echo '<form name="deleteform" method="post" action="">
        <input type="hidden" name="action" value="delete" />
        <input type="hidden" name="node" value="'.$file['href'].'" />
        <td><button type="submit" style="background:white; border:0;" value="delete"><img src="https://'.$GLOBALS['sys_https_host'].'/themes/Dawn/images/ic/trash.png"></button>';
        echo '</td></form>';
    }

    /**
     * Shows the rename form
     *
     * @param mixed $file
     *
     * @return void
     */
    function renameForm($file) {
        echo '<form method="post" action="">
        <input type="hidden" name="action" value="rename" />
        <input type="hidden" name="node" value="'.$file['href'].'" />
        <td><input type="text" name="name" />
        <button type="submit" style="background:white; border:0;" value="rename"><img src="https://'.$GLOBALS['sys_https_host'].'/themes/Dawn/images/ic/edit.png"></button></td>
        </form>';
    }

    /**
     * Shows the move form (not implemented yet)
     *
     * @param mixed $file
     * @param array $destinations
     *
     * @return void
     */
    function moveForm($file, $destinations) {
        echo '<form method="post" action="">
        <input type="hidden" name="action" value="move" />
        <td><select name="select">
        <OPTION VALUE="" SELECTED="yes">';
        foreach (array_keys($destinations) as $key) {
            echo '<OPTGROUP LABEL="'.$key.'">';
            foreach ($destinations[$key] as $destination) {
                echo '<OPTION VALUE="'.$destination.'">'.basename($destination).'</OPTION>';
            }
        }
        echo '</select>
        <input type="hidden" name="node" value="'.$file['href'].'" />
        <button type="submit" style="background:white; border:0;" value="move"><img src="https://'.$GLOBALS['sys_https_host'].'/themes/Dawn/images/ic/admin.png"></button></td>
        </form>';
    }

    /**
     * Shows the create package/release form
     *
     * @return void
     */
    function mkcolForm() {
        echo '<form method="post" action="">
        <input type="hidden" name="action" value="mkcol" />
        '.$GLOBALS["Language"]->getText("plugin_webdav_html", "name").' : <input type="text" name="name" />
        <button type="submit" style="background:white; border:0;" value="create"><img src="https://'.$GLOBALS['sys_https_host'].'/themes/Dawn/images/ic/add.png"></button>
        </form>';
    }

    /**
     * Returns the list of packages we can move the release into
     *
     * @param Array $release
     *
     * @return Array
     */
    function getReleaseDestinations($release) {
        $project = $this->server->tree->getNodeForPath(dirname(dirname($release['href'])));
        $packages = $project->getChildren();
        $destinations = array();
        foreach ($packages as $package) {
            $destinationPath = $project->getName().'/'.$package->getName();
            if ($destinationPath != dirname($release['href'])) {
                $destinations['Package'][] = $destinationPath;
            }
        }
        return $destinations;
    }

    /**
     * Returns the list of releases we can move the file into
     *
     * @param Array $file
     *
     * @return Array
     */
    function getFileDestinations($file) {
        $project = $this->server->tree->getNodeForPath(dirname(dirname(dirname($file['href']))));
        $packages = $project->getChildren();
        $destinations = array();
        foreach ($packages as $package) {
            $releases = $package->getChildren();
            foreach ($releases as $release) {
                $destinationPath = $project->getName().'/'.$package->getName().'/'.$release->getName();
                if ($destinationPath != dirname($file['href'])) {
                    $destinations[$package->getName()][] = $destinationPath;
                }
            }
        }
        return $destinations;
    }

    /**
     * Handles POST requests for tree operations
     *
     * @param String $method
     *
     * @return Boolean
     */
    public function httpPOSTHandler($method) {

        if ($method!='POST') {
            return true;
        }
        if (isset($_POST['action'])) {
            switch($_POST['action']) {

                case 'mkcol' :
                    if (isset($_POST['name']) && trim($_POST['name'])) {
                        // Using basename() because we won't allow slashes
                        list(, $folderName) = Sabre_DAV_URLUtil::splitPath(trim($_POST['name']));
                        $this->server->createDirectory($this->server->getRequestUri() . '/' . $folderName);
                    }
                    break;
                case 'put' :
                    if ($_FILES) {
                        $file = current($_FILES);
                    } else {
                        break;
                    }
                    $newName = trim($file['name']);
                    list(, $newName) = Sabre_DAV_URLUtil::splitPath(trim($file['name']));
                    if (isset($_POST['name']) && trim($_POST['name'])) {
                        $newName = trim($_POST['name']);
                    }

                    // Making sure we only have a 'basename' component
                    list(, $newName) = Sabre_DAV_URLUtil::splitPath($newName);

                    if (is_uploaded_file($file['tmp_name'])) {
                        $parent = $this->server->tree->getNodeForPath(trim($this->server->getRequestUri(), '/'));
                        $parent->createFile($newName, fopen($file['tmp_name'], 'r'));
                    }
                    break;
                case 'delete' :
                    if ($_POST['node']) {
                        $node = $this->server->tree->getNodeForPath($_POST['node']);
                        $node->delete();
                    }
                    break;
                case 'rename' :
                    if ($_POST['node']) {
                        $node = $this->server->tree->getNodeForPath($_POST['node']);
                        $name = $_POST['name'];
                        $node->setName($name);
                    }
                    break;
                case 'move' :
                    if ($_POST['node'] && $_POST['select'] && $_POST['select']!= '') {
                        $node        = $this->server->tree->getNodeForPath($_POST['node']);
                        $destination = $this->server->tree->getNodeForPath($_POST['select']);
                        $node->move($destination);
                    }
                    break;
            }
        }
        $this->server->httpResponse->setHeader('Location', $this->server->httpRequest->getUri());
        return false;

    }

    /**
     * Rewriting for SabreDAV Browser plugin
     *
     * @param String $path
     *
     * @return String
     *
     * @see plugins/webdav/lib/Sabre/DAV/Browser/Sabre_DAV_Browser_Plugin#generateDirectoryIndex($path)
     */
    public function generateDirectoryIndex($path) {

        $node = $this->server->tree->getNodeForPath($path);
        $class = get_class($node);

        echo $GLOBALS['HTML']->pv_header(array('title'=>' WebDAV : '.$node->getName()));

        ob_start();

        echo"<h3>".$node->getName()."</h3>";

        echo '';

        echo "<table>
        <tr><th>".$GLOBALS['Language']->getText('plugin_webdav_html', 'name')."</th><th>Type</th>";
        if ($class == 'WebDAVFRS' && $node->userCanWrite()) {
            echo "<th>".$GLOBALS['Language']->getText('plugin_webdav_html', 'delete')."</th><th>".$GLOBALS['Language']->getText('plugin_webdav_html', 'rename')."</th>";
        }
        if ($class == 'WebDAVFRSPackage') {
            echo "<th>".$GLOBALS['Language']->getText('plugin_webdav_html', 'last_modified')."</th>";
            if ($node->userCanWrite()) {
                echo "<th>".$GLOBALS['Language']->getText('plugin_webdav_html', 'delete')."</th><th>".$GLOBALS['Language']->getText('plugin_webdav_html', 'rename')."</th>";/*<th>".$GLOBALS['Language']->getText('plugin_webdav_html', 'move')."</th>";*/
            }
        }
        if ($class == 'WebDAVFRSRelease') {
            echo "<th>".$GLOBALS['Language']->getText('plugin_webdav_html', 'size')."</th><th>".$GLOBALS['Language']->getText('plugin_webdav_html', 'last_modified')."</th>";
            if ($node->userCanWrite()) {
                echo "<th>".$GLOBALS['Language']->getText('plugin_webdav_html', 'delete')."</th>";/*<th>".$GLOBALS['Language']->getText('plugin_webdav_html', 'move')."</th>";*/
            }
        }
        echo "</tr><tr><td colspan=\"6\"><hr /></td></tr>";

        $files = $this->server->getPropertiesForPath(
            $path, array(
            '{DAV:}resourcetype',
            '{DAV:}getcontenttype',
            '{DAV:}getcontentlength',
            '{DAV:}getlastmodified',
        ), 1
        );

        foreach ($files as $file) {

            // Link to the parent directory
            if ($file['href']==$path) {
                echo str_replace("%", "%25", "<td><a href=\"".$this->server->getBaseUri().dirname($path)."\"><b>..<b></a></td>");
                continue;
            }

            $name = basename($file['href']);

            if (isset($file[200]['{DAV:}resourcetype'])) {
                $type = $file[200]['{DAV:}resourcetype']->getValue();
                if ($type=='{DAV:}collection') {
                    $type = $GLOBALS["Language"]->getText("plugin_webdav_html", "directory");
                } elseif ($type=='') {
                    if (isset($file[200]['{DAV:}getcontenttype'])) {
                        $type = $file[200]['{DAV:}getcontenttype'];
                    } else {
                        $type = $GLOBALS["Language"]->getText("plugin_webdav_html", "unknown");
                    }
                } elseif (is_array($type)) {
                    $type = implode(', ', $type);
                }
            }
            $type = $this->escapeHTML($type);
            $size = isset($file[200]['{DAV:}getcontentlength'])?(int)$file[200]['{DAV:}getcontentlength']:'';
            $lastmodified = isset($file[200]['{DAV:}getlastmodified'])?date(DATE_ATOM, $file[200]['{DAV:}getlastmodified']->getTime()):'';

            $fullPath = '/' . trim($this->server->getBaseUri() . ($path?$this->escapeHTML($path) . '/':'') . $name, '/');

            echo str_replace("%", "%25", "<tr><td><a href=\"{$fullPath}\">{$name}</a></td>");
            echo "<td>{$type}</td>";
            if ($class == 'WebDAVFRS' && $node->userCanWrite()) {
                $this->deleteForm($file);
                $this->renameForm($file);
            }
            if ($class == 'WebDAVFRSPackage') {
                echo "<td>{$lastmodified}</td>";
                if ($node->userCanWrite()) {
                    $this->deleteForm($file);
                    $this->renameForm($file);
                    $destinations = $this->getReleaseDestinations($file);
                    //$this->moveForm($file, $destinations);
                }
            }
            if ($class == 'WebDAVFRSRelease') {
                echo "<td>{$size}</td>";
                echo "<td>{$lastmodified}</td>";
                if ($node->userCanWrite()) {
                    $this->deleteForm($file);
                    $destinations = $this->getFileDestinations($file);
                    //$this->moveForm($file, $destinations);
                }
            }
            echo "</tr>";

        }

        echo "<tr><td colspan=\"6\"><hr /></td></tr>
        <tr><td>";

        if ($this->enablePost) {
            if ($class == 'WebDAVFRS' && $node->userCanWrite()) {
                echo '<h4>'.$GLOBALS["Language"]->getText("plugin_webdav_html", "create_package").' :</h4>';
                $this->mkcolForm();
            }
            if ($class == 'WebDAVFRSPackage' && $node->userCanWrite()) {
                echo '<h4>'.$GLOBALS["Language"]->getText("plugin_webdav_html", "create_release").' :</h4>';
                $this->mkcolForm();
            }
            if ($class == 'WebDAVFRSRelease' && $node->userCanWrite()) {
                echo '<h4>'.$GLOBALS["Language"]->getText("plugin_webdav_html", "upload_file").' :</h4>
                <form method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="put" />
                '.$GLOBALS["Language"]->getText("plugin_webdav_html", "name").' ('.$GLOBALS["Language"]->getText("plugin_webdav_html", "optional").') : <input type="text" name="name" /><br />
                '.$GLOBALS["Language"]->getText("plugin_webdav_html", "file").' : <input type="file" name="file" />
                <button type="submit" style="background:white; border:0;" value="upload"><img src="https://'.$GLOBALS['sys_https_host'].'/themes/Dawn/images/ic/tick.png"></button>
                </form>';
            }
            echo '</td></tr>';
        }

        echo"</table>";

        echo $GLOBALS['HTML']->pv_footer(array());

        return ob_get_clean();

    }
}

?>