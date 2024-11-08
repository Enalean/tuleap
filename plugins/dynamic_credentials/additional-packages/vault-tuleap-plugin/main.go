package main

import (
	"log"
	"os"

	"gerrit.tuleap.net/vault-tuleap-plugin/tuleap"
	"github.com/hashicorp/vault/api"
	"github.com/hashicorp/vault/sdk/plugin"
)

func main() {
	apiClientMeta := &api.PluginAPIClientMeta{}
	flags := apiClientMeta.FlagSet()
	insecureTLS := flags.Bool("insecure-tls", false, "Disable Tuleap server TLS certificate verification - insecure, use with caution!")
	flags.Parse(os.Args[1:])

	tlsConfig := apiClientMeta.GetTLSConfig()
	tlsProviderFunc := api.VaultPluginTLSProvider(tlsConfig)

	backendFactoryProviderFunc := tuleap.FactoryProvider(*insecureTLS)

	if err := plugin.Serve(&plugin.ServeOpts{
		BackendFactoryFunc: backendFactoryProviderFunc,
		TLSProviderFunc:    tlsProviderFunc,
	}); err != nil {
		log.Fatal(err)
	}
}
