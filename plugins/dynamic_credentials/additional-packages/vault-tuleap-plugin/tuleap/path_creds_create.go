package tuleap

import (
	"context"
	"fmt"
	"github.com/hashicorp/go-uuid"
	"github.com/hashicorp/vault/sdk/framework"
	"github.com/hashicorp/vault/sdk/helper/strutil"
	"github.com/hashicorp/vault/sdk/logical"
	"github.com/ryanuber/go-glob"
	"strings"
	"time"
)

func pathCredsCreate(b *backend) *framework.Path {
	return &framework.Path{
		Pattern: "creds/" + framework.GenericNameRegex("name"),
		Fields: map[string]*framework.FieldSchema{
			"name": {
				Type:        framework.TypeString,
				Description: "Name of the role.",
			},
			"host": {
				Type:        framework.TypeString,
				Description: `host the credentials should be generated for.`,
			},
		},
		Operations: map[logical.Operation]framework.OperationHandler{
			logical.UpdateOperation: &framework.PathOperation{
				Callback: b.pathCredsCreate,
			},
		},
		HelpSynopsis:    pathCredsCreateReadHelpSyn,
		HelpDescription: pathCredsCreateReadHelpDesc,
	}
}

func (b *backend) pathCredsCreate(ctx context.Context, req *logical.Request, data *framework.FieldData) (*logical.Response, error) {
	name := data.Get("name").(string)

	role, err := b.getRole(ctx, req.Storage, name)
	if err != nil {
		return nil, err
	}
	if role == nil {
		return logical.ErrorResponse(fmt.Sprintf("unknown role: %s", name)), nil
	}

	config, err := b.getConfiguration(ctx, req.Storage, role.ConfigName)
	if err != nil {
		return nil, err
	}
	if config == nil {
		return logical.ErrorResponse(fmt.Sprintf("unknown configuration: %s", role.ConfigName)), nil
	}

	if !strutil.StrListContains(config.AllowedRoles, "*") && !strutil.StrListContainsGlob(config.AllowedRoles, name) {
		return nil, logical.ErrPermissionDenied
	}

	host := data.Get("host").(string)
	if !isHostAllowedForRole(host, role) {
		return nil, fmt.Errorf("host is not allowed for this role")
	}

	uuidVal, err := uuid.GenerateUUID()
	if err != nil {
		return nil, err
	}
	username := fmt.Sprintf(tuleapDynamicCredentialUsernameFormat, uuidVal)

	password, err := uuid.GenerateUUID()
	if err != nil {
		return nil, err
	}

	ttl := b.getTTLForRoleSecret(role)
	expiration := time.Now().Add(ttl)
	err = b.client.CreateCredential(config.PrivateKey, host, username, password, expiration)
	if err != nil {
		return nil, err
	}

	resp := b.Secret(SecretCredsType).Response(map[string]interface{}{
		"username": username,
		"password": password,
	}, map[string]interface{}{
		"username": username,
		"role":     name,
		"host":     host,
	})
	resp.Secret.TTL = b.getTTLForRoleSecret(role)

	return resp, nil
}

func isHostAllowedForRole(host string, role *roleEntry) bool {
	for _, currHost := range role.AllowedHosts {
		if currHost == "" {
			continue
		}

		if currHost == host {
			return true
		}

		if role.AllowGlobHosts &&
			strings.Contains(currHost, "*") &&
			glob.Glob(currHost, host) {
			return true
		}
	}

	return false
}

const pathCredsCreateReadHelpSyn = `Request Tuleap credentials for a certain role and host.`
const pathCredsCreateReadHelpDesc = `
This path reads Tuleap credentials for a certain role. The
Tuleap credentials will be generated on demand and will be automatically
revoked when the lease is up.
`
const tuleapDynamicCredentialUsernameFormat = "forge__dynamic_credential-%s"
