## Dependencies
- PHP with GD
install php-gd rpm from RHEL.

- Jpgraph
install jpgraph-1.20.5-1.codex.noarch.rpm provided by codex.

## Configuration
- Copy /usr/share/codex/plugins/graphontrackers/etc/graphontrackers.inc.dist to /etc/codex/plugins/graphontrackers/etc/graphontrackers.inc
- Customize your graphontrackers.inc with following variable:
// Jpgraph Path
$graphontrackers_jpgraph_prefix = "/usr/share/jpgraph";

