package tuleap

import (
	"context"
	"fmt"
	"github.com/hashicorp/vault/sdk/framework"
	"github.com/hashicorp/vault/sdk/logical"
	"time"
)

func pathListRoles(b *backend) *framework.Path {
	return &framework.Path{
		Pattern: "roles/?$",
		Operations: map[logical.Operation]framework.OperationHandler{
			logical.ListOperation: &framework.PathOperation{
				Callback: b.pathRolesList,
			},
		},
		HelpSynopsis:    pathListRolesHelpSyn,
		HelpDescription: pathListRolesHelpDesc,
	}
}

func (b *backend) pathRolesList(ctx context.Context, req *logical.Request, d *framework.FieldData) (*logical.Response, error) {
	entries, err := req.Storage.List(ctx, "role/")
	if err != nil {
		return nil, err
	}

	return logical.ListResponse(entries), nil
}

func pathRoles(b *backend) *framework.Path {
	return &framework.Path{
		Pattern: "roles/" + framework.GenericNameRegex("name"),
		Fields: map[string]*framework.FieldSchema{
			"name": {
				Type:        framework.TypeString,
				Description: "Name of the role.",
			},
			"config_name": {
				Type:        framework.TypeString,
				Description: "Name of the configuration this role acts on.",
			},
			"default_ttl": {
				Type:        framework.TypeDurationSecond,
				Description: "Default ttl for role.",
			},
			"max_ttl": {
				Type:        framework.TypeDurationSecond,
				Description: "Maximum time a credential is valid for.",
			},
			"allowed_hosts": {
				Type: framework.TypeCommaStringSlice,
				Description: `If set, clients can request credentials for these hosts, including
This parameter accepts a comma-separated string or list of hosts.`,
			},
			"allow_glob_hosts": {
				Type:    framework.TypeBool,
				Default: false,
				Description: `If set, hosts specified in "allowed_hosts"
can include glob patterns, e.g. "rt*.example.com". See
the documentation for more information.`,
			},
		},
		Operations: map[logical.Operation]framework.OperationHandler{
			logical.ReadOperation: &framework.PathOperation{
				Callback: b.pathRoleRead,
			},
			logical.UpdateOperation: &framework.PathOperation{
				Callback: b.pathRoleCreate,
			},
			logical.DeleteOperation: &framework.PathOperation{
				Callback: b.pathRoleDelete,
			},
		},
		HelpSynopsis:    pathRoleHelpSyn,
		HelpDescription: pathRoleHelpDesc,
	}
}

func (b *backend) pathRoleRead(ctx context.Context, req *logical.Request, data *framework.FieldData) (*logical.Response, error) {
	role, err := b.getRole(ctx, req.Storage, data.Get("name").(string))
	if err != nil {
		return nil, err
	}
	if role == nil {
		return nil, nil
	}

	return &logical.Response{
		Data: map[string]interface{}{
			"config_name":      role.ConfigName,
			"default_ttl":      role.DefaultTTL.Seconds(),
			"max_ttl":          role.MaxTTL.Seconds(),
			"allowed_hosts":    role.AllowedHosts,
			"allow_glob_hosts": role.AllowGlobHosts,
		},
	}, nil
}

func (b *backend) pathRoleCreate(ctx context.Context, req *logical.Request, data *framework.FieldData) (*logical.Response, error) {
	name := data.Get("name").(string)

	configName := data.Get("config_name").(string)
	if configName == "" {
		return logical.ErrorResponse("empty configuration name attribute given"), nil
	}

	defaultTTLRaw := data.Get("default_ttl").(int)
	maxTTLRaw := data.Get("max_ttl").(int)
	defaultTTL := time.Duration(defaultTTLRaw) * time.Second
	maxTTL := time.Duration(maxTTLRaw) * time.Second

	entry, err := logical.StorageEntryJSON("role/"+name, &roleEntry{
		ConfigName:     configName,
		DefaultTTL:     defaultTTL,
		MaxTTL:         maxTTL,
		AllowedHosts:   data.Get("allowed_hosts").([]string),
		AllowGlobHosts: data.Get("allow_glob_hosts").(bool),
	})
	if err != nil {
		return nil, err
	}
	if err := req.Storage.Put(ctx, entry); err != nil {
		return nil, err
	}

	return nil, nil

}

func (b *backend) pathRoleDelete(ctx context.Context, req *logical.Request, data *framework.FieldData) (*logical.Response, error) {
	err := req.Storage.Delete(ctx, "role/"+data.Get("name").(string))
	if err != nil {
		return nil, err
	}
	return nil, nil
}

func (b *backend) getRole(ctx context.Context, s logical.Storage, name string) (*roleEntry, error) {
	entry, err := s.Get(ctx, "role/"+name)
	if err != nil {
		return nil, fmt.Errorf("failed to read role: %s", err)
	}
	if entry == nil {
		return nil, nil
	}

	var role roleEntry
	if err := entry.DecodeJSON(&role); err != nil {
		return nil, err
	}

	return &role, nil
}

type roleEntry struct {
	ConfigName     string        `json:"config_name" mapstructure:"config_name" structs:"config_name"`
	DefaultTTL     time.Duration `json:"default_ttl" mapstructure:"default_ttl" structs:"default_ttl"`
	MaxTTL         time.Duration `json:"max_ttl" mapstructure:"max_ttl" structs:"max_ttl"`
	AllowedHosts   []string      `json:"allowed_hosts_list" mapstructure:"allowed_hosts"`
	AllowGlobHosts bool          `json:"allow_glob_hosts" mapstructure:"allow_glob_hosts"`
}

const pathListRolesHelpSyn = `List the existing roles in this backend`
const pathListRolesHelpDesc = `Roles will be listed by the role name.`
const pathRoleHelpSyn = `
Manage the roles that can be created with this backend.
`
const pathRoleHelpDesc = `
This path lets you manage the roles that can be created with this backend.

The "config_name" parameter is required and configures the name of the configuration to use.
`
