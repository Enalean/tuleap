package tuleap

import (
	"context"
	"github.com/hashicorp/vault/sdk/logical"
	"testing"
)

func TestBackend(t *testing.T) {
	config := logical.TestBackendConfig()
	config.StorageView = &logical.InmemStorage{}
	b, err := FactoryProvider(false)(context.Background(), config)
	if err != nil {
		t.Fatal(err)
	}
	if b == nil {
		t.Fail()
	}
}
