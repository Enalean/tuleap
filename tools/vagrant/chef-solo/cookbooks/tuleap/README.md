Description
===========

Installs and configures Tuleap

Attributes
==========

See the `attributes/default.rb` for default values.

* `node['tuleap']['php_base']` - Available PHP versions: `php`, `php53`
* `node['tuleap']['install_dir']` - Where sources are located: `/usr/share/codendi`
* `node['tuleap']['yum_repo']` - Either `stable` (use stable packages like 5.6), `dev` (use dev packages like 5.6.99.3) or `local` (use locally built packages)
* `node['tuleap']['packaging_user']` - The user that will be used to build package: `tuleap-dev`
* `node['tuleap']['manifest_dir']` - The mount point where the manifest used to build packages is located on the box: `/mnt/tuleap/manifest`
* `node['tuleap']['source_dir']` - The mount point where tuleap source are located on the box: `/mnt/tuleap/tuleap`
* `node['tuleap']['org_name']` - Used during installation: `AcmeCorporation`

