# Profiling with XDebug

Edit `/etc/opt/remi/php74/php.d/xdebug.ini` and add those lines:

``` properties
; Enable xdebug extension module
zend_extension=/usr/lib64/php/modules/xdebug.so

xdebug.max_nesting_level=200

xdebug.var_display_max_depth=3
xdebug.profiler_enable_trigger=1
xdebug.profiler_output_dir="/tmp/workspace/cachegrind"
xdebug.profiler_output_name="cachegrind.out.%s.%r"
```

How to use it:

-   When you add `XDEBUG_PROFILE=1` as a request parameter (e.g.
    `http://..../?stuff&XDEBUG_PROFILE=1`) it will generate a profile
    info into `profiler_output_dir`
-   With kcachegrind (on your host) you can analyse the generated trace
    and find hotspots
