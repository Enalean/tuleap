package tuleap

import (
	"context"
	"github.com/hashicorp/vault/sdk/logical"
	"testing"
)

func getTestBackend(t *testing.T) (logical.Backend, logical.Storage) {
	config := logical.TestBackendConfig()
	config.StorageView = &logical.InmemStorage{}
	b, err := FactoryProvider(false)(context.Background(), config)
	if err != nil {
		t.Fatal(err)
	}

	return b, config.StorageView
}
