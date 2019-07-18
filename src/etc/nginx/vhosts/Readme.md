Nginx + FPM for Tuleap Homepages
================================

- fpm.conf should be deployed as /etc/opt/remi/php73/php-fpm.d/homepages.conf
- nginx.conf should be added after default https server declaration in /etc/nginx/conf.d/tuleap.conf

Those 2 files are given as example and should be checked projects by projects.

Few things:
- whenever possible the php part of nginx (location ~.php) should not be enabled to
  only serve static content from homepages (for security reasons).
- the proposed fpm configuration is using a neutral user (apache) and is listening
  on a different port (9001). This way, homepage scripts cannot access to codendiadm
  sensitive data
