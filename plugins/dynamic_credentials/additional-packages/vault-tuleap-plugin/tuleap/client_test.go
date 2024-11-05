package tuleap

import (
	"crypto/ed25519"
	"net/http"
	"net/http/httptest"
	"net/url"
	"testing"
	"time"
)

type ClientMock struct {
	CreateCredentialFunc func(signingKey ed25519.PrivateKey, host string, username string, password string, expiration time.Time) error
	DeleteCredentialFunc func(signingKey ed25519.PrivateKey, host string, username string) error
}

func (mock *ClientMock) CreateCredential(signingKey ed25519.PrivateKey, host string, username string, password string, expiration time.Time) error {
	return mock.CreateCredentialFunc(signingKey, host, username, password, expiration)
}

func (mock *ClientMock) DeleteCredential(signingKey ed25519.PrivateKey, host string, username string) error {
	return mock.DeleteCredentialFunc(signingKey, host, username)
}

func TestTuleapClient_RequestsAreSuccessful(t *testing.T) {
	ts := httptest.NewTLSServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
	}))
	defer ts.Close()

	httpClient := ts.Client()
	client := &APIClient{
		HTTPClient: *httpClient,
	}
	_, signingKey, err := ed25519.GenerateKey(nil)
	if err != nil {
		t.Fatal(err)
	}

	u, err := url.Parse(ts.URL)
	if err != nil {
		t.Fatal(err)
	}

	err = client.CreateCredential(signingKey, u.Host, "username", "password", time.Now())
	if err != nil {
		t.Error(err)
	}
	err = client.DeleteCredential(signingKey, u.Host, "username")
	if err != nil {
		t.Error(err)
	}
}

func TestTuleapClient_POSTDynamicCredentialWhenTuleapInstanceFails(t *testing.T) {
	ts := httptest.NewTLSServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusInternalServerError)
	}))
	defer ts.Close()

	httpClient := ts.Client()
	client := &APIClient{
		HTTPClient: *httpClient,
	}
	_, signingKey, err := ed25519.GenerateKey(nil)
	if err != nil {
		t.Fatal(err)
	}

	u, err := url.Parse(ts.URL)
	if err != nil {
		t.Fatal(err)
	}

	err = client.CreateCredential(signingKey, u.Host, "username", "password", time.Now())
	if err == nil {
		t.Errorf("Expected creation credential to fail due to a 500 Internal Server Error response from Tuleap")
	}
}

func TestTuleapClient_ExpectFailureWhenHostCanNotBeResolved(t *testing.T) {
	client := &APIClient{
		HTTPClient: http.Client{},
	}
	_, signingKey, err := ed25519.GenerateKey(nil)
	if err != nil {
		t.Fatal(err)
	}

	err = client.CreateCredential(signingKey, "notexistingdomain.tuleap.test", "username", "password", time.Now())
	if err == nil {
		t.Errorf("Expected request failure due to an unresolvable host")
	}
}
