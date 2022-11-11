package main

import (
	caddycmd "github.com/caddyserver/caddy/v2/cmd"
	_ "github.com/dunglas/mercure/caddy"
)

func main() {
	caddycmd.Main()
}
