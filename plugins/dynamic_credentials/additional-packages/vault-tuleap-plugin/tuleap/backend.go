package tuleap

import (
	"context"
	"crypto/tls"
	"github.com/hashicorp/vault/sdk/framework"
	"github.com/hashicorp/vault/sdk/logical"
	"net/http"
)

func FactoryProvider(insecureTLS bool) logical.Factory {
	return func(ctx context.Context, conf *logical.BackendConfig) (logical.Backend, error) {
		b := Backend(insecureTLS)
		if err := b.Setup(ctx, conf); err != nil {
			return nil, err
		}
		return b, nil
	}
}

func Backend(insecureTLS bool) *backend {
	var b backend
	b.Backend = &framework.Backend{
		Help: backendHelp,
		Paths: []*framework.Path{
			pathListConfigure(&b),
			pathConfigure(&b),
			pathListRoles(&b),
			pathRoles(&b),
			pathCredsCreate(&b),
		},
		PathsSpecial: &logical.Paths{
			SealWrapStorage: []string{
				"config/",
			},
		},
		Secrets: []*framework.Secret{
			secretCreds(&b),
		},
		BackendType: logical.TypeLogical,
	}
	b.client = &APIClient{
		HTTPClient: http.Client{
			Transport: &http.Transport{
				TLSClientConfig: &tls.Config{InsecureSkipVerify: insecureTLS},
			},
		},
	}
	return &b
}

type backend struct {
	*framework.Backend

	client Client
}

const backendHelp = `
The Tuleap backend dynamically generates site administrator users.
`
