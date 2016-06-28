Tuleap UI Framework
===================

Setup
-----

```
$ sudo gem install scss_lint
$ sudo npm install
$ gulp
```

Usage
-----

Because TLP icons are managed by FontAwesome, you need to load these two css files in <head></head>:

```html
<link rel="stylesheet" href="font-awesome.min.css">
<link rel="stylesheet" href="tlp-blue.min.css">
```

Load this javascript file before </body>:

```html
    <script type="text/javascript" src="tlp.min.js"></script>
  </body>
</html>
```

Troubleshootings
----------------

Error: watch … ENOSPC
'''''''''''''''''''''

You may need to issue this command on linux if you get ENOSPC error while launching `gulp watch`:

```
echo fs.inotify.max_user_watches=524288 | sudo tee -a /etc/sysctl.conf && sudo sysctl -p
```

https://github.com/gulpjs/gulp/issues/217
