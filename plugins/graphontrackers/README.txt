## Dependencies
- PHP with GD
install php-gd rpm from RHEL.

- Jpgraph
install jpgraph-1.20.5-1.codendi.noarch.rpm provided by codendi.

## Configuration
- Copy /usr/share/codendi/plugins/graphontrackers/etc/graphontrackers.inc.dist to /etc/codendi/plugins/graphontrackers/etc/graphontrackers.inc
- Customize your graphontrackers.inc with following variable:
// Jpgraph Path
$graphontrackers_jpgraph_prefix = "/usr/share/jpgraph";

