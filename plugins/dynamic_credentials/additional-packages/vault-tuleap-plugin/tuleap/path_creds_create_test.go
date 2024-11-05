package tuleap

import (
	"context"
	"crypto/ed25519"
	"errors"
	"github.com/hashicorp/vault/sdk/logical"
	"testing"
	"time"
)

func TestTuleap_EmptyAllowedHostsListRejectEveryHost(t *testing.T) {
	role := &roleEntry{AllowedHosts: []string{}}

	if isHostAllowedForRole("example.com", role) ||
		isHostAllowedForRole("*", role) ||
		isHostAllowedForRole("", role) {
		t.Error()
	}
}

func TestTuleap_HostAreVerifiedWithTheAllowedHostList(t *testing.T) {
	role := &roleEntry{AllowedHosts: []string{"example.com", "", "tuleap.test"}}

	if isHostAllowedForRole("example.test", role) ||
		!isHostAllowedForRole("tuleap.test", role) ||
		isHostAllowedForRole("something.tuleap.test", role) {
		t.Error()
	}
}

func TestTuleap_HostAreVerifiedWithTheAllowedHostListWithGLob(t *testing.T) {
	role := &roleEntry{
		AllowedHosts:   []string{"example.com", "", "*tuleap.test"},
		AllowGlobHosts: true,
	}

	if isHostAllowedForRole("example.test", role) ||
		!isHostAllowedForRole("tuleap.test", role) ||
		!isHostAllowedForRole("something.tuleap.test", role) {
		t.Error()
	}
}

func TestTuleap_CreateCredsErrorWhenRoleIsUnknown(t *testing.T) {
	storage := &logical.InmemStorage{}

	b := Backend(false)

	req := &logical.Request{
		Storage:   storage,
		Operation: logical.UpdateOperation,
		Path:      "creds/test",
		Data:      map[string]interface{}{},
	}
	response, err := b.HandleRequest(context.Background(), req)

	if err != nil {
		t.Fatal(err)
	}
	if !response.IsError() {
		t.Fatal("Creds has been successfuly created but role does not exist")
	}
}

func TestTuleap_CreateCredsErrorWhenConfigurationIsUnknown(t *testing.T) {
	storage := &logical.InmemStorage{}

	b := Backend(false)

	req := &logical.Request{
		Storage:   storage,
		Operation: logical.UpdateOperation,
		Path:      "roles/role1",
		Data:      map[string]interface{}{"config_name": "conf1"},
	}
	_, err := b.HandleRequest(context.Background(), req)
	if err != nil {
		t.Fatal(err)
	}

	req = &logical.Request{
		Storage:   storage,
		Operation: logical.UpdateOperation,
		Path:      "creds/role1",
		Data:      map[string]interface{}{},
	}
	response, err := b.HandleRequest(context.Background(), req)

	if err != nil {
		t.Fatal(err)
	}
	if !response.IsError() {
		t.Fatal("Creds has been successfuly created but configuration does not exist")
	}
}

func TestTuleap_CreateCredsErrorWhenConfigurationDoesNotAllowTheRole(t *testing.T) {
	storage := &logical.InmemStorage{}

	b := Backend(false)

	req := &logical.Request{
		Storage:   storage,
		Operation: logical.UpdateOperation,
		Path:      "config/conf1",
		Data:      map[string]interface{}{},
	}
	_, err := b.HandleRequest(context.Background(), req)
	if err != nil {
		t.Fatal(err)
	}

	req = &logical.Request{
		Storage:   storage,
		Operation: logical.UpdateOperation,
		Path:      "roles/role1",
		Data:      map[string]interface{}{"config_name": "conf1"},
	}
	_, err = b.HandleRequest(context.Background(), req)
	if err != nil {
		t.Fatal(err)
	}

	req = &logical.Request{
		Storage:   storage,
		Operation: logical.UpdateOperation,
		Path:      "creds/role1",
		Data:      map[string]interface{}{},
	}
	_, err = b.HandleRequest(context.Background(), req)

	if err != logical.ErrPermissionDenied {
		t.Fatal("Configuration does not allow this role but permission has not been denied")
	}
}

func TestTuleap_CreateCredsErrorWhenRoleDoesNotAllowTheHost(t *testing.T) {
	storage := &logical.InmemStorage{}

	b := Backend(false)

	req := &logical.Request{
		Storage:   storage,
		Operation: logical.UpdateOperation,
		Path:      "config/conf1",
		Data:      map[string]interface{}{"allowed_roles": "*"},
	}
	_, err := b.HandleRequest(context.Background(), req)
	if err != nil {
		t.Fatal(err)
	}

	req = &logical.Request{
		Storage:   storage,
		Operation: logical.UpdateOperation,
		Path:      "roles/role1",
		Data: map[string]interface{}{
			"config_name":   "conf1",
			"allowed_hosts": "example.com",
		},
	}
	_, err = b.HandleRequest(context.Background(), req)
	if err != nil {
		t.Fatal(err)
	}

	req = &logical.Request{
		Storage:   storage,
		Operation: logical.UpdateOperation,
		Path:      "creds/role1",
		Data:      map[string]interface{}{"host": "notallowed.example.com"},
	}
	_, err = b.HandleRequest(context.Background(), req)

	if err == nil {
		t.Fatal("Role does not allow this host but permission has not been denied")
	}
}

func TestTuleap_CreateCreds(t *testing.T) {
	storage := &logical.InmemStorage{}

	b := Backend(false)
	sysView := logical.StaticSystemView{
		DefaultLeaseTTLVal: 0,
		MaxLeaseTTLVal:     240,
	}
	b.Setup(nil, &logical.BackendConfig{
		System: sysView,
	})
	mockedClient := &ClientMock{
		CreateCredentialFunc: func(signingKey ed25519.PrivateKey, host string, username string, password string, expiration time.Time) error {
			return nil
		},
	}
	b.client = mockedClient

	req := &logical.Request{
		Storage:   storage,
		Operation: logical.UpdateOperation,
		Path:      "config/conf1",
		Data:      map[string]interface{}{"allowed_roles": "*"},
	}
	_, err := b.HandleRequest(context.Background(), req)
	if err != nil {
		t.Fatal(err)
	}

	req = &logical.Request{
		Storage:   storage,
		Operation: logical.UpdateOperation,
		Path:      "roles/role1",
		Data: map[string]interface{}{
			"config_name":   "conf1",
			"allowed_hosts": "example.com",
		},
	}
	_, err = b.HandleRequest(context.Background(), req)
	if err != nil {
		t.Fatal(err)
	}

	req = &logical.Request{
		Storage:   storage,
		Operation: logical.UpdateOperation,
		Path:      "creds/role1",
		Data:      map[string]interface{}{"host": "example.com"},
	}
	response, err := b.HandleRequest(context.Background(), req)
	if err != nil {
		t.Fatal(err)
	}
	if response.Data["username"] == nil || response.Data["password"] == nil {
		t.Fatal("Username or password has not been retrieved")
	}

	mockedClientFailure := &ClientMock{
		CreateCredentialFunc: func(signingKey ed25519.PrivateKey, host string, username string, password string, expiration time.Time) error {
			return errors.New("Credential creation failure")
		},
	}
	b.client = mockedClientFailure
	req = &logical.Request{
		Storage:   storage,
		Operation: logical.UpdateOperation,
		Path:      "creds/role1",
		Data:      map[string]interface{}{"host": "example.com"},
	}
	_, err = b.HandleRequest(context.Background(), req)
	if err == nil {
		t.Fatal("Creds has been succesfuly retrieved but client failed")
	}
}
