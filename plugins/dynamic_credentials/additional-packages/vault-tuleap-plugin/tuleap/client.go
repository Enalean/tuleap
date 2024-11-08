package tuleap

import (
	"bytes"
	"crypto/ed25519"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"net/http"
	"net/url"
	"time"
)

type Client interface {
	CreateCredential(signingKey ed25519.PrivateKey, host string, username string, password string, expiration time.Time) error
	DeleteCredential(signingKey ed25519.PrivateKey, host string, username string) error
}

type APIClient struct {
	HTTPClient http.Client
}

type creationRequest struct {
	Username   string `json:"username"`
	Password   string `json:"password"`
	Expiration string `json:"expiration"`
	Signature  string `json:"signature"`
}

type deleteRequest struct {
	username  string
	signature string
}

func (c APIClient) CreateCredential(signingKey ed25519.PrivateKey, host string, username string, password string, expiration time.Time) error {
	createParametersRequest := &creationRequest{
		Username:   username,
		Password:   password,
		Expiration: expiration.UTC().Format(time.RFC3339),
	}

	c.signCreateCredentialRequest(signingKey, host, createParametersRequest)

	creationURL := url.URL{
		Scheme: "https",
		Host:   host,
		Path:   tuleapCreationEndpoint,
	}

	return c.sendRequest(createParametersRequest, creationURL, "POST")
}

func (c APIClient) DeleteCredential(signingKey ed25519.PrivateKey, host string, username string) error {
	deleteParametersRequest := &deleteRequest{username: username}

	c.signDeleteCredentialRequest(signingKey, host, deleteParametersRequest)

	deletionURL := url.URL{
		Scheme: "https",
		Host:   host,
		Path:   fmt.Sprintf(tuleapDeletionEndpoint, url.PathEscape(username)),
	}
	query := deletionURL.Query()
	query.Set("signature", deleteParametersRequest.signature)
	deletionURL.RawQuery = query.Encode()

	return c.sendRequest(deleteParametersRequest, deletionURL, "DELETE")
}

func (c APIClient) signCreateCredentialRequest(signingKey ed25519.PrivateKey, host string, req *creationRequest) {
	messageToSign := fmt.Sprintf("%s%s%s%s", host, req.Username, req.Password, req.Expiration)
	req.Signature = c.signMessage(signingKey, messageToSign)
}

func (c APIClient) signDeleteCredentialRequest(signingKey ed25519.PrivateKey, host string, req *deleteRequest) {
	messageToSign := fmt.Sprintf("%s%s", host, req.username)
	req.signature = c.signMessage(signingKey, messageToSign)
}

func (c APIClient) signMessage(signingKey ed25519.PrivateKey, message string) string {
	signature := ed25519.Sign(signingKey, []byte(message))

	return base64.StdEncoding.EncodeToString(signature)
}

func (c APIClient) sendRequest(requestParameters interface{}, url url.URL, method string) error {
	j, err := json.Marshal(requestParameters)
	if err != nil {
		return err
	}
	body := bytes.NewReader(j)

	req, err := http.NewRequest(method, url.String(), body)
	if err != nil {
		return err
	}
	req.Header.Set("Content-Type", "application/json")
	resp, err := c.HTTPClient.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode != 200 {
		return fmt.Errorf("%s %s failed: %s", method, url.String(), resp.Status)
	}

	return nil
}

const tuleapCreationEndpoint = "/api/dynamic_credentials"
const tuleapDeletionEndpoint = "/api/dynamic_credentials/%s"
