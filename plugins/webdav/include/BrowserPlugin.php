<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * This is a web based WebDAV client added as a plugin into the WebDAV server
 */
class BrowserPlugin extends Sabre\DAV\Browser\Plugin //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private $purifier;

    public function __construct()
    {
        parent::__construct();
        $this->purifier = Codendi_HTMLPurifier::instance();
    }

    /**
     * Shows the delete form
     *
     * @param mixed $file
     *
     * @return void
     */
    public function deleteForm($file)
    {
        echo '<form name="deleteform" method="post" action="">
        <input type="hidden" name="action" value="delete" />
        <input type="hidden" name="node" value="' . $this->purifier->purify($file['href']) . '" />
        <td><button type="submit" style="background:white; border:0;" value="delete">' . _('Delete') . '</button>';
        echo '</td></form>';
    }

    /**
     * Shows the rename form
     *
     * @param mixed $file
     *
     * @return void
     */
    public function renameForm($file)
    {
        echo '<form method="post" action="">
        <input type="hidden" name="action" value="rename" />
        <input type="hidden" name="node" value="' . $this->purifier->purify($file['href']) . '" />
        <td><input type="text" name="name" />
        <button type="submit" style="background:white; border:0;" value="rename">' . _('Edit') . '</button></td>
        </form>';
    }

    /**
     * Shows the create package/release form
     *
     * @return void
     */
    public function mkcolForm()
    {
        echo '<form method="post" action="">
        <input type="hidden" name="action" value="mkcol" />
        ' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'name')) . ' : <input type="text" name="name" />
        <button type="submit" style="background:white; border:0;" value="create">' . _('Add') . '</button>
        </form>';
    }

    /**
     * Handles POST requests for tree operations
     *
     * @return bool
     */
    #[\Override]
    public function httpPOST(RequestInterface $request, ResponseInterface $response)
    {
        if ($request->getMethod() != 'POST') {
            return true;
        }
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'mkcol':
                    if (isset($_POST['name']) && trim($_POST['name'])) {
                        // Using basename() because we won't allow slashes
                        list(, $folderName) = \Sabre\Uri\split(trim($_POST['name']));
                        $this->server->createDirectory($this->server->getRequestUri() . '/' . $folderName);
                    }
                    break;
                case 'put':
                    if ($_FILES) {
                        $file = current($_FILES);
                    } else {
                        break;
                    }
                    $newName         = trim($file['name']);
                    list(, $newName) = \Sabre\Uri\split(trim($file['name']));
                    if (isset($_POST['name']) && trim($_POST['name'])) {
                        $newName = trim($_POST['name']);
                    }

                    // Making sure we only have a 'basename' component
                    list(, $newName) = \Sabre\Uri\split($newName);

                    if (is_uploaded_file($file['tmp_name'])) {
                        $parent = $this->server->tree->getNodeForPath(trim($this->server->getRequestUri(), '/'));
                        $parent->createFile($newName, fopen($file['tmp_name'], 'r'));
                    }
                    break;
                case 'delete':
                    if ($_POST['node']) {
                        $node = $this->server->tree->getNodeForPath($_POST['node']);
                        $node->delete();
                    }
                    break;
                case 'rename':
                    if ($_POST['node']) {
                        $node = $this->server->tree->getNodeForPath($_POST['node']);
                        $name = $_POST['name'];
                        $node->setName($name);
                    }
                    break;
                case 'move':
                    if ($_POST['node'] && $_POST['select'] && $_POST['select'] != '') {
                        $node        = $this->server->tree->getNodeForPath($_POST['node']);
                        $destination = $this->server->tree->getNodeForPath($_POST['select']);
                        $node->move($destination);
                    }
                    break;
            }
        }
        $response->setHeader('Location', $request->getUrl());
        $response->setStatus(302);
        return false;
    }

    /**
     * Rewriting for SabreDAV Browser plugin
     *
     * @param String $path
     *
     * @return String
     */
    #[\Override]
    public function generateDirectoryIndex($path)
    {
        $node = $this->server->tree->getNodeForPath($path);

        ob_start();

        echo '<h3>' . $this->purifier->purify($node->getName()) . '</h3>';

        echo '';

        echo '<table>
        <tr><th>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'name')) . '</th><th>Type</th>';
        if ($node instanceof WebDAVFRS && $node->userCanWrite()) {
            echo '<th>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'delete')) . '</th><th>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'rename')) . '</th>';
        }
        if ($node instanceof WebDAVFRSPackage) {
            echo '<th>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'last_modified')) . '</th>';
            if ($node->userCanWrite()) {
                echo '<th>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'delete')) . '</th><th>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'rename')) . '</th>';
            }
        }
        if ($node instanceof WebDAVFRSRelease) {
            echo '<th>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'size')) . '</th><th>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'last_modified')) . '</th>';
            if ($node->userCanWrite()) {
                echo '<th>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'delete')) . '</th>';
            }
        }
        if ($node instanceof WebDAVDocmanFolder) {
            echo '<th>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'size')) . '</th><th>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'last_modified')) . '</th>';
            if ($node->userCanWrite()) {
                echo '<th>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'delete')) . '</th><th>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'rename')) . '</th>';
            }
        }
        echo '</tr><tr><td colspan="6"><hr /></td></tr>';

        $files = $this->server->getPropertiesForPath(
            $path,
            [
                '{DAV:}resourcetype',
                '{DAV:}getcontenttype',
                '{DAV:}getcontentlength',
                '{DAV:}getlastmodified',
            ],
            1
        );

        if ($path) {
            list($parentUri) = \Sabre\Uri\split($path);
            $fullPath        = \Sabre\HTTP\encodePath($this->server->getBaseUri() . $parentUri);
            echo "<tr><td><a href=\"{$this->purifier->purify($fullPath)}\">..</a></td></tr>";
        }

        foreach ($files as $file) {
            // This is the current directory, we can skip it
            if (rtrim($file['href'], '/') == $path) {
                continue;
            }

            list(, $name) = \Sabre\Uri\split($file['href']);
            $type         = null;

            if (isset($file[200]['{DAV:}resourcetype'])) {
                $type = $file[200]['{DAV:}resourcetype']->getValue();
                // resourcetype can have multiple values
                if (! is_array($type)) {
                    $type = [$type];
                }
                foreach ($type as $k => $v) {
                    // Some name mapping is preferred
                    if ($v == '{DAV:}collection') {
                        $type[$k] = $GLOBALS['Language']->getText('plugin_webdav_html', 'directory');
                    } elseif ($v == '') {
                        if (isset($file[200]['{DAV:}getcontenttype'])) {
                            $type[$k] = $file[200]['{DAV:}getcontenttype'];
                        } else {
                            $type[$k] = $GLOBALS['Language']->getText('plugin_webdav_html', 'unknown');
                        }
                    }
                }
                $type = implode(', ', $type);
            }
            $type         = $this->escapeHTML($type);
            $size         = isset($file[200]['{DAV:}getcontentlength']) ? (int) $file[200]['{DAV:}getcontentlength'] : '';
            $lastmodified = isset($file[200]['{DAV:}getlastmodified']) ? $file[200]['{DAV:}getlastmodified']->getTime()->format(DATE_ATOM) : '';

            $fullPath = '/' . trim($this->server->getBaseUri() . ($path ? $this->purifier->purify($path) . '/' : '') . $this->purifier->purify($name), '/');

            echo str_replace('%', '%25', "<tr><td><a href=\"{$this->purifier->purify($fullPath)}\">{$this->purifier->purify($name)}</a></td>");
            echo "<td>{$type}</td>";
            if ($node instanceof WebDAVFRS && $node->userCanWrite()) {
                $this->deleteForm($file);
                $this->renameForm($file);
            }
            if ($node instanceof WebDAVFRSPackage) {
                echo "<td>{$lastmodified}</td>";
                if ($node->userCanWrite()) {
                    $this->deleteForm($file);
                    $this->renameForm($file);
                }
            }
            if ($node instanceof WebDAVFRSRelease) {
                echo "<td>{$size}</td>";
                echo "<td>{$lastmodified}</td>";
                if ($node->userCanWrite()) {
                    $this->deleteForm($file);
                }
            }
            if ($node instanceof WebDAVDocmanFolder) {
                echo "<td>{$size}</td>";
                echo "<td>{$lastmodified}</td>";
                if ($node->userCanWrite()) {
                    $this->deleteForm($file);
                    $this->renameForm($file);
                }
            }
            echo '</tr>';
        }

        echo '<tr><td colspan="6"><hr /></td></tr>
        <tr><td>';

        if ($this->enablePost) {
            if ($node instanceof WebDAVFRS && $node->userCanWrite()) {
                echo '<h4>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'create_package')) . ' :</h4>';
                $this->mkcolForm();
            }
            if ($node instanceof WebDAVFRSPackage && $node->userCanWrite()) {
                echo '<h4>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'create_release')) . ' :</h4>';
                $this->mkcolForm();
            }
            if ($node instanceof WebDAVFRSRelease && $node->userCanWrite()) {
                echo '<h4>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'upload_file')) . ' :</h4>
                <form method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="put" />
                ' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'name')) . ' (' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'optional')) . ') : <input type="text" name="name" /><br />
                ' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'file')) . ' : <input type="file" name="file" />
                <button type="submit" style="background:white; border:0;" value="upload"><img src="/themes/Dawn/images/ic/tick.png"></button>
                </form>';
            }
            if ($node instanceof WebDAVDocmanFolder) {
                if ($node->userCanWrite()) {
                    echo '<h4>Create a new folder :</h4>';
                    $this->mkcolForm();
                    echo '<h4>' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'upload_file')) . ' :</h4>
                    <form method="post" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="put" />
                    ' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'name')) . ' (' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'optional')) . ') : <input type="text" name="name" /><br />
                    ' . $this->purifier->purify($GLOBALS['Language']->getText('plugin_webdav_html', 'file')) . ' : <input type="file" name="file" />
                    <button type="submit" style="background:white; border:0;" value="upload"><img src="/themes/Dawn/images/ic/tick.png"></button>
                    </form>';
                }
            }
            echo '</td></tr>';
        }

        echo '</table>';

        echo $GLOBALS['HTML']->pv_footer();

        return ob_get_clean();
    }
}
