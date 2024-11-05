package tuleap

import (
	"context"
	"fmt"
	"github.com/hashicorp/vault/sdk/framework"
	"github.com/hashicorp/vault/sdk/logical"
	"time"
)

const SecretCredsType = "creds"

func secretCreds(b *backend) *framework.Secret {
	return &framework.Secret{
		Type: SecretCredsType,
		Fields: map[string]*framework.FieldSchema{
			"username": {
				Type:        framework.TypeString,
				Description: "Tuleap username",
			},
			"password": {
				Type:        framework.TypeString,
				Description: "Password for the Tuleap username",
			},
		},
		Revoke: b.secretCredsRevoke,
	}
}

func (b *backend) secretCredsRevoke(ctx context.Context, req *logical.Request, d *framework.FieldData) (*logical.Response, error) {
	usernameRaw, ok := req.Secret.InternalData["username"]
	if !ok {
		return nil, fmt.Errorf("secret is missing username internal data")
	}

	hostRaw, ok := req.Secret.InternalData["host"]
	if !ok {
		return nil, fmt.Errorf("secret is missing host name internal data")
	}

	roleNameRaw, ok := req.Secret.InternalData["role"]
	if !ok {
		return nil, fmt.Errorf("no role name was provided")
	}

	role, err := b.getRole(ctx, req.Storage, roleNameRaw.(string))
	if err != nil {
		return nil, err
	}
	if role == nil {
		return nil, fmt.Errorf("error during revoke: could not find role with name %s", roleNameRaw)
	}

	config, err := b.getConfiguration(ctx, req.Storage, role.ConfigName)
	if err != nil {
		return nil, err
	}
	if config == nil {
		return nil, fmt.Errorf("error during revoke: could not find configuration with name %s", role.ConfigName)
	}

	err = b.client.DeleteCredential(config.PrivateKey, hostRaw.(string), usernameRaw.(string))

	return nil, err
}

func (b *backend) getTTLForRoleSecret(role *roleEntry) time.Duration {
	ttl := role.DefaultTTL
	if ttl == 0 || (role.MaxTTL > 0 && ttl > role.MaxTTL) {
		ttl = role.MaxTTL
	}
	maxBackendTtl := b.System().MaxLeaseTTL()
	if ttl == 0 || ttl > maxBackendTtl {
		ttl = maxBackendTtl
	}
	return ttl
}
