{
  pkgs ? (import ../../../tools/utils/nix/pinned-nixpkgs.nix) { },
}:

let
  nginxPort = "8080";
  nginxConf = pkgs.writeText "nginx.conf" ''
    user nobody nobody;
    daemon off;
    error_log /dev/stdout info;
    pid /dev/null;
    events {}
    http {
      access_log /dev/stdout;
      server {
        listen ${nginxPort};
        index index.html;
        location / {
          root ${./storybook-static};
        }
        include ${pkgs.mailcap}/etc/nginx/mime.types;
        add_header Referrer-Policy "no-referrer" always;
        add_header Cross-Origin-Embedder-Policy "require-corp" always;
        add_header Cross-Origin-Resource-Policy "same-origin" always;
        add_header Cross-Origin-Opener-Policy "same-origin" always;
        add_header Vary "Sec-Fetch-Site" always;
        add_header Content-Security-Policy "default-src 'none'; script-src 'self' 'unsafe-eval'; script-src-elem 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; style-src-elem 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; media-src 'self'; frame-src 'self'; block-all-mixed-content; base-uri 'none'" always;
        sendfile on;
        tcp_nopush on;
        tcp_nodelay on;
        keepalive_timeout 65;
      }
    }
  '';
in pkgs.dockerTools.buildLayeredImage {
  name = "tlp-doc";
  tag = "latest";
  contents = [
    pkgs.fakeNss
  ];

  extraCommands = ''
    mkdir -p tmp/nginx_client_body
    mkdir -p var/log/nginx
  '';

  config = {
    Cmd = [ "${pkgs.nginx}/bin/nginx" "-c" nginxConf ];
    ExposedPorts = {
      "${nginxPort}/tcp" = {};
    };
  };
}
