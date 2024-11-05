package tuleap

import (
	"context"
	"github.com/hashicorp/vault/sdk/logical"
	"reflect"
	"testing"
)

func TestTuleap_CRUDRoles(t *testing.T) {
	b, storage := getTestBackend(t)

	dataRole2 := map[string]interface{}{
		"config_name":      "config",
		"max_ttl":          3600.,
		"default_ttl":      3600.,
		"allowed_hosts":    []string{"example.com", "*.example.test"},
		"allow_glob_hosts": true,
	}
	dataRole3 := map[string]interface{}{
		"config_name":      "config",
		"default_ttl":      0.,
		"max_ttl":          0.,
		"allowed_hosts":    []string{},
		"allow_glob_hosts": false,
	}

	testStepCreateRole(t, b, storage, "role", map[string]interface{}{"config_name": "config"})
	testStepCreateRole(t, b, storage, "role2", dataRole2)
	testStepCreateRole(t, b, storage, "role3", map[string]interface{}{"config_name": "config"})
	testStepReadRole(t, b, storage, "role2", dataRole2)
	testStepReadRole(t, b, storage, "role3", dataRole3)
	testStepListRoles(t, b, storage, []string{"role", "role2", "role3"})
	testStepDeleteRole(t, b, storage, "role2")
	testStepReadRole(t, b, storage, "role2", nil)
	testStepListRoles(t, b, storage, []string{"role", "role3"})
}

func testStepCreateRole(t *testing.T, b logical.Backend, storage logical.Storage, name string, data map[string]interface{}) {
	response, err := b.HandleRequest(context.Background(), &logical.Request{
		Operation: logical.UpdateOperation,
		Storage:   storage,
		Path:      "roles/" + name,
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

func testStepReadRole(t *testing.T, b logical.Backend, storage logical.Storage, name string, data map[string]interface{}) {
	response, err := b.HandleRequest(context.Background(), &logical.Request{
		Operation: logical.ReadOperation,
		Storage:   storage,
		Path:      "roles/" + name,
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

	if !reflect.DeepEqual(response.Data, data) {
		t.Errorf("does not match: %#v %#v", response.Data, data)
		return
	}
}

func testStepListRoles(t *testing.T, b logical.Backend, storage logical.Storage, names []string) {
	response, err := b.HandleRequest(context.Background(), &logical.Request{
		Operation: logical.ListOperation,
		Storage:   storage,
		Path:      "roles/",
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

func testStepDeleteRole(t *testing.T, b logical.Backend, storage logical.Storage, name string) {
	response, err := b.HandleRequest(context.Background(), &logical.Request{
		Operation: logical.DeleteOperation,
		Storage:   storage,
		Path:      "roles/" + name,
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

func TestTuleap_CreateErrorWhenConfigNameIsEmpty(t *testing.T) {
	b, storage := getTestBackend(t)

	req := &logical.Request{
		Storage:   storage,
		Operation: logical.UpdateOperation,
		Path:      "roles/test",
		Data: map[string]interface{}{
			"config_name": "",
		},
	}
	response, err := b.HandleRequest(context.Background(), req)

	if err != nil {
		t.Fatal(err)
	}
	if !response.IsError() {
		t.Fatal("Role has been created but config name is empty")
	}
}
