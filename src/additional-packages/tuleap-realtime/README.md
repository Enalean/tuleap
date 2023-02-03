# [DEV ONLY] How to generate ssl certificates

## Step 1: Generate your certificate
```
$ cd tuleap-realtime
$ mkdir ssl
$ cd ssl
$ openssl genrsa -out key.pem 2048
$ openssl req -new -key key.pem -out csr.pem
$ openssl x509 -req -days 800 -in csr.pem -signkey key.pem -out cert.pem
```

## Step 2: Trust your certificate on the server side

If you're using CentOS 5:

- Add generated certificate to the trusted certificate lists on your server, here:
```
$ vi /etc/pki/tls/certs/ca-bundles.crt
```
- Reboot httpd:
```
$ service httpd restart
```

If you're using CentOS 6: please check Tuleap documentation.


## Step 3: Trust your certificate on the client side

Add the certificate to your browser.


## Step 4: Run the docker image with the folder tuleap-realtime

If you want to start the node.js server directly without config file:

```
$ docker run --rm -v "$PWD/":/nodeapp -p 4443:4443 enalean/node-dev-simple
```

If you want to start the node.js server with a config file:

```
$ docker run --rm -v "$PWD/":/nodeapp --entrypoint=bash -ti -p 4443:4443 enalean/node-dev-simple
# node server.js --config='etc/tuleap-realtime/config.json'
```