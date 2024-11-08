package tuleap

import (
	"context"
	"crypto/ed25519"
	"encoding/base64"
	"fmt"
	"github.com/hashicorp/vault/sdk/framework"
	"github.com/hashicorp/vault/sdk/logical"
)

func pathListConfigure(b *backend) *framework.Path {
	return &framework.Path{
		Pattern: "config/?$",
		Operations: map[logical.Operation]framework.OperationHandler{
			logical.ListOperation: &framework.PathOperation{
				Callback: b.pathConfigurationList,
			},
		},
		HelpSynopsis:    pathListConfigurationsHelpSyn,
		HelpDescription: pathListConfigurationsHelpDesc,
	}
}

func (b *backend) pathConfigurationList(ctx context.Context, req *logical.Request, d *framework.FieldData) (*logical.Response, error) {
	entries, err := req.Storage.List(ctx, "config/")
	if err != nil {
		return nil, err
	}

	return logical.ListResponse(entries), nil
}

func pathConfigure(b *backend) *framework.Path {
	return &framework.Path{
		Pattern: "config/" + framework.GenericNameRegex("name"),
		Fields: map[string]*framework.FieldSchema{
			"name": {
				Type:        framework.TypeString,
				Description: "Name of the configuration.",
			},
			"allowed_roles": {
				Type: framework.TypeCommaStringSlice,
				Description: `Comma separated string or array of the role names
allowed to get creds from this configuration. If empty no
roles are allowed. If "*" all roles are allowed.`,
			},
		},
		Operations: map[logical.Operation]framework.OperationHandler{
			logical.ReadOperation: &framework.PathOperation{
				Callback: b.pathConfigurationRead,
			},
			logical.UpdateOperation: &framework.PathOperation{
				Callback: b.pathConfigurationCreate,
			},
			logical.DeleteOperation: &framework.PathOperation{
				Callback: b.pathConfigurationDelete,
			},
		},
		HelpSynopsis:    pathConfigHelpSyn,
		HelpDescription: pathConfigHelpDesc,
	}
}

func (b *backend) pathConfigurationRead(ctx context.Context, req *logical.Request, data *framework.FieldData) (*logical.Response, error) {
	config, err := b.getConfiguration(ctx, req.Storage, data.Get("name").(string))
	if err != nil {
		return nil, err
	}
	if config == nil {
		return nil, nil
	}

	publicKey := config.PrivateKey.Public().(ed25519.PublicKey)

	return &logical.Response{
		Data: map[string]interface{}{
			"allowed_roles": config.AllowedRoles,
			"public_key":    base64.StdEncoding.EncodeToString(publicKey),
		},
	}, nil
}

func (b *backend) pathConfigurationCreate(ctx context.Context, req *logical.Request, data *framework.FieldData) (*logical.Response, error) {
	name := data.Get("name").(string)

	_, privateKey, err := ed25519.GenerateKey(nil)
	if err != nil {
		return nil, err
	}

	storageEntry, err := logical.StorageEntryJSON("config/"+name, &configurationEntry{
		AllowedRoles: data.Get("allowed_roles").([]string),
		PrivateKey:   privateKey,
	})
	if err != nil {
		return nil, err
	}
	if err := req.Storage.Put(ctx, storageEntry); err != nil {
		return nil, err
	}
	return nil, nil
}

func (b *backend) pathConfigurationDelete(ctx context.Context, req *logical.Request, data *framework.FieldData) (*logical.Response, error) {
	err := req.Storage.Delete(ctx, "config/"+data.Get("name").(string))
	if err != nil {
		return nil, err
	}
	return nil, nil
}

func (b *backend) getConfiguration(ctx context.Context, s logical.Storage, name string) (*configurationEntry, error) {
	entry, err := s.Get(ctx, "config/"+name)
	if err != nil {
		return nil, fmt.Errorf("failed to read configuration: %s", err)
	}
	if entry == nil {
		return nil, nil
	}

	var config configurationEntry
	if err := entry.DecodeJSON(&config); err != nil {
		return nil, err
	}

	return &config, nil
}

type configurationEntry struct {
	AllowedRoles []string `json:"allowed_roles_list" mapstructure:"allowed_roles"`
	PrivateKey   ed25519.PrivateKey
}

const pathListConfigurationsHelpSyn = `List the existing configurations in this backend`
const pathListConfigurationsHelpDesc = `Configuration will be listed by the configuration name.`
const pathConfigHelpSyn = `Configure connection details to Tuleap instances.`
const pathConfigHelpDesc = `This path lets you manage the configurations that can be created to access Tuleap instances
with this backend.`
