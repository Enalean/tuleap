package tuleap

import (
	"context"
	"crypto/ed25519"
	"encoding/base64"
	"github.com/hashicorp/vault/sdk/logical"
	"reflect"
	"strings"
	"testing"
)

func TestTuleap_CRUDConfig(t *testing.T) {
	b, storage := getTestBackend(t)

	dataConfig3 := map[string]interface{}{"allowed_roles": "role0,role1"}

	testStepCreateConfig(t, b, storage, "test", map[string]interface{}{})
	testStepCreateConfig(t, b, storage, "test2", map[string]interface{}{"allowed_roles": "*"})
	testStepCreateConfig(t, b, storage, "test3", dataConfig3)
	testStepReadConfig(t, b, storage, "test3", dataConfig3)
	testStepListConfig(t, b, storage, []string{"test", "test2", "test3"})
	testStepDeleteConfig(t, b, storage, "test2")
	testStepReadConfig(t, b, storage, "test2", nil)
	testStepListConfig(t, b, storage, []string{"test", "test3"})
}

func testStepCreateConfig(t *testing.T, b logical.Backend, storage logical.Storage, name string, data map[string]interface{}) {
	response, err := b.HandleRequest(context.Background(), &logical.Request{
		Operation: logical.UpdateOperation,
		Storage:   storage,
		Path:      "config/" + name,
		Data:      data,
	})

	if err != nil {
		t.Error(err)
		return
	}
	if response.IsError() {
		t.Error(response.Error())
		return
	}
}

func testStepReadConfig(t *testing.T, b logical.Backend, storage logical.Storage, name string, data map[string]interface{}) {
	response, err := b.HandleRequest(context.Background(), &logical.Request{
		Operation: logical.ReadOperation,
		Storage:   storage,
		Path:      "config/" + name,
		Data:      data,
	})

	if err != nil {
		t.Error(err)
		return
	}
	if response.IsError() {
		t.Error(response.Error())
		return
	}

	if response == nil {
		if data == nil {
			return
		}
		t.Errorf("response not expected: %#v", response)
		return
	}

	joinedRoles := strings.Join(response.Data["allowed_roles"].([]string), ",")
	if joinedRoles != data["allowed_roles"] {
		t.Errorf("allowed roles does not match: %s %s", joinedRoles, data["allowed_roles"])
		return
	}

	decodedPublicKey, err := base64.StdEncoding.DecodeString(response.Data["public_key"].(string))
	if err != nil {
		t.Error(err)
		return
	}

	if len(decodedPublicKey) != ed25519.PublicKeySize {
		t.Errorf("public key size should be %d, got %d", ed25519.PublicKeySize, len(decodedPublicKey))
		return
	}
}

func testStepListConfig(t *testing.T, b logical.Backend, storage logical.Storage, names []string) {
	response, err := b.HandleRequest(context.Background(), &logical.Request{
		Operation: logical.ListOperation,
		Storage:   storage,
		Path:      "config/",
	})

	if err != nil {
		t.Error(err)
		return
	}
	if response.IsError() {
		t.Error(response.Error())
		return
	}

	respKeys := response.Data["keys"].([]string)
	if !reflect.DeepEqual(respKeys, names) {
		t.Errorf("does not match: %#v %#v", respKeys, names)
		return
	}
}

func testStepDeleteConfig(t *testing.T, b logical.Backend, storage logical.Storage, name string) {
	response, err := b.HandleRequest(context.Background(), &logical.Request{
		Operation: logical.DeleteOperation,
		Storage:   storage,
		Path:      "config/" + name,
	})

	if err != nil {
		t.Error(err)
		return
	}
	if response.IsError() {
		t.Error(response.Error())
		return
	}
}
