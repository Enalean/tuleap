<?php
/**
 * This is a web based WebDAV client added as a plugin into the WebDAV server
 */
class BrowserPlugin extends Sabre_DAV_Browser_Plugin {

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

        ob_start();
        echo "<html>
<head>
  <title>Index for " . $this->escapeHTML($path) . "/ - WebDAV </title>
  <style type=\"text/css\"> body { Font-family: arial}</style>
</head>
<body>
  <h1>Index for " . $this->escapeHTML($path) . "/</h1>
  <table>
    <tr><th>Name</th><th>Type</th><th>Size</th><th>Last modified</th></tr>
    <tr><td colspan=\"4\"><hr /></td></tr>";

        $files = $this->server->getPropertiesForPath(
            $path, array(
            '{DAV:}resourcetype',
            '{DAV:}getcontenttype',
            '{DAV:}getcontentlength',
            '{DAV:}getlastmodified',
        ), 1
        );

        foreach ($files as $file) {

            // This is the current directory, we can skip it
            if ($file['href']==$path) {
                continue;
            }

            $name = $this->escapeHTML(basename($file['href']));

            if (isset($file[200]['{DAV:}resourcetype'])) {
                $type = $file[200]['{DAV:}resourcetype']->getValue();
                if ($type=='{DAV:}collection') {
                    $type = 'Directory';
                } elseif ($type=='') {
                    if (isset($file[200]['{DAV:}getcontenttype'])) {
                        $type = $file[200]['{DAV:}getcontenttype'];
                    } else {
                        $type = 'Unknown';
                    }
                } elseif (is_array($type)) {
                    $type = implode(', ', $type);
                }
            }
            $type = $this->escapeHTML($type);
            $size = isset($file[200]['{DAV:}getcontentlength'])?(int)$file[200]['{DAV:}getcontentlength']:'';
            $lastmodified = isset($file[200]['{DAV:}getlastmodified'])?date(DATE_ATOM, $file[200]['{DAV:}getlastmodified']->getTime()):'';

            $fullPath = '/' . trim($this->server->getBaseUri() . ($path?$this->escapeHTML($path) . '/':'') . $name, '/');

            echo "<tr>
<td><a href=\"{$fullPath}\">{$name}</a></td>
<td>{$type}</td>
<td>{$size}</td>
<td>{$lastmodified}</td>
</tr>";

        }

        echo "<tr><td colspan=\"4\"><hr /></td></tr>";
        echo"</table>
</body>
</html>";

        return ob_get_clean();

    }
}

?>