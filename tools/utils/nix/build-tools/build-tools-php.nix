{ pkgs, phpBase }:

let
  php = phpBase.withExtensions (
    { enabled, all }:
    with all;
    enabled
    ++ [
      ffi
      bcmath
      curl
      ctype
      dom
      fileinfo
      filter
      gd
      gettext
      iconv
      intl
      ldap
      mbstring
      mysqli
      mysqlnd
      opcache
      openssl
      pcntl
      pdo_mysql
      posix
      readline
      session
      simplexml
      sodium
      tokenizer
      xmlreader
      xmlwriter
      zip
      zlib
      mailparse
      imagick
      sysvsem
      redis
      xsl
    ]
  );
in
[
  php
  php.packages.composer
]
