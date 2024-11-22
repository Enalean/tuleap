# Realtime

## Introduction

Tuleap Realtime brings interactivity when users are viewing the same
screen at the same time. For example in Kanban, when one user move a
card from one column to another, then the card is automatically moved
for every users that are on the same Kanban.

## Run the Node.js server

### If you don't use the rpm package

Install dependencies and built it:

```
$ cd src/additional-packages/tuleap-realtime/
$ pnpm install
$ pnpm run build
```

Run the Node.js server inside your web container:

```
$ make bash-web
# systemctl stop tuleap-realtime
# source /var/lib/tuleap/tuleap-realtime-key
# sudo -u tuleaprt PRIVATE_KEY="$PRIVATE_KEY" /usr/share/tuleap/src/additional-packages/tuleap-realtime/dist/tuleap-realtime
```

### If you use the rpm package

To build the RPM:

```
$ nix-build ./src/additional-packages/tuleap-realtime.nix
```

To install the RPM inside your web container:

```
# rpm -Uvh --nodeps <package_name>.rpm
```

To start the server:

```
# systemctl start tuleap-realtime
```
