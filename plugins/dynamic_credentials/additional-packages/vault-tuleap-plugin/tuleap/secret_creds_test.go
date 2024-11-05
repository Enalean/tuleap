package tuleap

import (
	"context"
	"crypto/ed25519"
	"github.com/hashicorp/vault/sdk/logical"
	"testing"
)

func TestTuleap_TTLIsNoLongerThanTheMaxBackendTTL(t *testing.T) {
	b := Backend(false)
	sysView := logical.StaticSystemView{
		DefaultLeaseTTLVal: 0,
		MaxLeaseTTLVal:     30,
	}
	b.Setup(nil, &logical.BackendConfig{
		System: sysView,
	})

	role := &roleEntry{
		DefaultTTL: 60,
		MaxTTL:     60,
	}

	if b.getTTLForRoleSecret(role) != 30 {
		t.Errorf("TTL should be capped by the max backend TTL")
	}
}

func TestTuleap_TTLIsNoLongerThanTheMaxRoleTTL(t *testing.T) {
	b := Backend(false)
	sysView := logical.StaticSystemView{
		DefaultLeaseTTLVal: 0,
		MaxLeaseTTLVal:     240,
	}
	b.Setup(nil, &logical.BackendConfig{
		System: sysView,
	})

	role := &roleEntry{
		DefaultTTL: 120,
		MaxTTL:     60,
	}

	if b.getTTLForRoleSecret(role) != 60 {
		t.Errorf("TTL should be capped by the max role TTL")
	}
}

func TestTuleap_SecretRevocationErrorWhenUsernameIsMissing(t *testing.T) {
	b := Backend(false)
	secret := &logical.Secret{
		InternalData: map[string]interface{}{},
	}
	req := &logical.Request{
		Secret: secret,
	}

	_, err := b.secretCredsRevoke(context.Background(), req, nil)

	if err == nil {
		t.Errorf("Error is expected when username is missing in the secret data")
	}
}

func TestTuleap_SecretRevocationErrorWhenHostIsMissing(t *testing.T) {
	b := Backend(false)
	secret := &logical.Secret{
		InternalData: map[string]interface{}{
			"username": "username",
		},
	}
	req := &logical.Request{
		Secret: secret,
	}

	_, err := b.secretCredsRevoke(context.Background(), req, nil)

	if err == nil {
		t.Errorf("Error is expected when host is missing in the secret data")
	}
}

func TestTuleap_SecretRevocationErrorWhenRoleNameIsMissing(t *testing.T) {
	b := Backend(false)
	secret := &logical.Secret{
		InternalData: map[string]interface{}{
			"username": "username",
			"host":     "example.com",
		},
	}
	req := &logical.Request{
		Secret: secret,
	}

	_, err := b.secretCredsRevoke(context.Background(), req, nil)

	if err == nil {
		t.Errorf("Error is expected when role name is missing in the secret data")
	}
}

func TestTuleap_SecretRevocationErrorWhenRoleCanNotBeFound(t *testing.T) {
	b := Backend(false)
	secret := &logical.Secret{
		InternalData: map[string]interface{}{
			"username": "username",
			"host":     "example.com",
			"role":     "role1",
		},
	}
	storage := &logical.InmemStorage{}
	req := &logical.Request{
		Storage: storage,
		Secret:  secret,
	}

	_, err := b.secretCredsRevoke(context.Background(), req, nil)

	if err == nil {
		t.Errorf("Error is expected when role can not be found")
	}
}

func TestTuleap_SecretRevocationErrorWhenConfigurationCanNotBeFound(t *testing.T) {
	b := Backend(false)
	storage := &logical.InmemStorage{}

	req := &logical.Request{
		Storage:   storage,
		Operation: logical.UpdateOperation,
		Path:      "roles/role1",
		Data: map[string]interface{}{
			"config_name": "conf1",
		},
	}
	_, err := b.HandleRequest(context.Background(), req)
	if err != nil {
		t.Fatal(err)
	}

	secret := &logical.Secret{
		InternalData: map[string]interface{}{
			"username": "username",
			"host":     "example.com",
			"role":     "role1",
		},
	}
	req = &logical.Request{
		Storage: storage,
		Secret:  secret,
	}
	_, err = b.secretCredsRevoke(context.Background(), req, nil)

	if err == nil {
		t.Errorf("Error is expected when configuration can not be found")
	}
}

func TestTuleap_RevokeSecrets(t *testing.T) {
	b := Backend(false)
	mockedClient := &ClientMock{
		DeleteCredentialFunc: func(signingKey ed25519.PrivateKey, host string, username string) error {
			return nil
		},
	}
	b.client = mockedClient
	storage := &logical.InmemStorage{}

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
			"config_name": "conf1",
		},
	}
	_, err = b.HandleRequest(context.Background(), req)
	if err != nil {
		t.Fatal(err)
	}

	secret := &logical.Secret{
		InternalData: map[string]interface{}{
			"username": "username",
			"host":     "example.com",
			"role":     "role1",
		},
	}
	req = &logical.Request{
		Storage: storage,
		Secret:  secret,
	}
	_, err = b.secretCredsRevoke(context.Background(), req, nil)
}
