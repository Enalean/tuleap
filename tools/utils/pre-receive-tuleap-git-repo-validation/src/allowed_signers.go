package main

import _ "embed"
import (
	"fmt"
	"golang.org/x/crypto/ssh"
	"regexp"
	"time"
)

//go:embed allowed-integrators
var integrators string

var allowedSignersFormat = regexp.MustCompile(`(?m)^[a-zA-Z0-9\-_\.]+@.+\s+valid-after="(?P<after>\d{8})"(?:,valid-before="(?P<before>\d{8})")?\s+(?P<public_key>.+)$`)

type IntegratorSigningKey struct {
	PublicKey   ssh.PublicKey
	ValidAfter  time.Time
	ValidBefore time.Time
}

func GetIntegratorsSigningKeys() []IntegratorSigningKey {
	signingKeys, err := getIntegratorsSigningKeysWithError()
	if err != nil {
		panic(fmt.Errorf("allowed-integrators parsing: %w", err))
	}
	return signingKeys
}

func getIntegratorsSigningKeysWithError() ([]IntegratorSigningKey, error) {
	var signingKeys []IntegratorSigningKey

	matches := allowedSignersFormat.FindAllStringSubmatch(integrators, -1)
	for _, match := range matches {
		integratorSigningKey := IntegratorSigningKey{}
		for i, name := range allowedSignersFormat.SubexpNames() {
			switch name {
			case "after":
				time, err := time.Parse("20060102", match[i])
				if err != nil {
					return nil, fmt.Errorf("could not parse after date (%w) of %s", err, match[0])
				}
				integratorSigningKey.ValidAfter = time
				break
			case "before":
				if match[i] == "" {
					continue
				}
				time, err := time.Parse("20060102", match[i])
				if err != nil {
					return nil, fmt.Errorf("could not parse before date (%w) of %s", err, match[0])
				}
				integratorSigningKey.ValidBefore = time
				break
			case "public_key":
				publicKey, _, _, _, err := ssh.ParseAuthorizedKey([]byte(match[i]))
				if err != nil {
					return nil, fmt.Errorf("could not parse public key (%w) of %s", err, match[0])
				}
				integratorSigningKey.PublicKey = publicKey

				break
			}
		}
		signingKeys = append(signingKeys, integratorSigningKey)
	}
	return signingKeys, nil
}
