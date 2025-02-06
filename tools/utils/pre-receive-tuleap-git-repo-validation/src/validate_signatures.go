package main

import (
	"bytes"
	"crypto/sha256"
	"crypto/sha512"
	"encoding/pem"
	"errors"
	"fmt"
	"golang.org/x/crypto/ssh"
	"hash"
	"io"
	"regexp"
	"strings"
	"time"

	"github.com/go-git/go-git/v5"
	"github.com/go-git/go-git/v5/plumbing"
)

var sshCommitSignature = regexp.MustCompile(`(?Us)gpgsig (-----BEGIN SSH SIGNATURE-----.*-----END SSH SIGNATURE-----)\n`)
var sshTagSignature = regexp.MustCompile(`(?s)(-----BEGIN SSH SIGNATURE-----.*-----END SSH SIGNATURE-----)\n$`)

var supportedHashAlgorithms = map[string]func() hash.Hash{
	"sha256": sha256.New,
	"sha512": sha512.New,
}

func ValidateSignatures(hookData HookData, repo *git.Repository) (*string, error) {
	allowedSigningKeys := GetIntegratorsSigningKeys()

	var notSignedItems []string
	for referenceName := range hookData.UpdatedReferences {
		var objectType plumbing.ObjectType
		itemName := ""
		var signatureExtractRegexp *regexp.Regexp

		if strings.HasPrefix(referenceName, "refs/tags/") {
			objectType = plumbing.TagObject
			itemName = "annotated tag " + referenceName
			signatureExtractRegexp = sshTagSignature
		} else if strings.HasPrefix(referenceName, "refs/heads/") {
			objectType = plumbing.CommitObject
			itemName = "head commit of " + referenceName
			signatureExtractRegexp = sshCommitSignature
		} else {
			continue
		}

		reference, err := repo.Reference(plumbing.ReferenceName(referenceName), false)
		if err != nil {
			return nil, err
		}

		encodedObjectContent, err := getEncodedObjectContent(repo, objectType, reference.Hash())
		if err == plumbing.ErrObjectNotFound && objectType == plumbing.TagObject {
			notSignedItems = append(notSignedItems, referenceName+" (not annotated tag?)")
			continue
		} else if err != nil {
			return nil, err
		}

		matches := signatureExtractRegexp.FindSubmatch(encodedObjectContent)
		if len(matches) != 2 {
			notSignedItems = append(notSignedItems, itemName)
			continue
		}

		err = verifySignature(
			allowedSigningKeys,
			matches[1],
			bytes.ReplaceAll(encodedObjectContent, matches[0], []byte("")),
		)
		if err != nil {
			notSignedItems = append(notSignedItems, fmt.Sprintf("%s (%s)", itemName, err))
			continue
		}
	}

	if len(notSignedItems) > 0 {
		rejectionMessage := "The following items '" + strings.Join(notSignedItems, ", ") + "' have not been signed, please see docs/release.md"
		return &rejectionMessage, nil
	}

	return nil, nil
}

func getEncodedObjectContent(repo *git.Repository, objectType plumbing.ObjectType, hash plumbing.Hash) ([]byte, error) {
	object, err := repo.Storer.EncodedObject(objectType, hash)
	if err != nil {
		return nil, err
	}

	reader, err := object.Reader()
	if err != nil {
		return nil, err
	}
	defer reader.Close()

	data, err := io.ReadAll(reader)
	if err != nil {
		return nil, err
	}

	return data, nil
}

// https://github.com/openssh/openssh-portable/blob/826483d51a9fee60703298bbf839d9ce37943474/PROTOCOL.sshsig#L79
type sshSignatureMessageWrapper struct {
	MagicHeader   [6]byte
	Namespace     string
	Reserved      string
	HashAlgorithm string
	Hash          string
}

func verifySignature(allowedSigningKeys []IntegratorSigningKey, rawSignature []byte, content []byte) error {
	decodedSignature, err := decodeSignature(rawSignature)
	if err != nil {
		return err
	}

	hashAlgo := supportedHashAlgorithms[decodedSignature.HashAlgorithm]()
	if _, err := io.Copy(hashAlgo, bytes.NewBuffer(content)); err != nil {
		return fmt.Errorf("could not compute %s hash of the message: %w", decodedSignature.HashAlgorithm, err)
	}
	hashMessage := hashAlgo.Sum(nil)

	signedMessageToVerify := ssh.Marshal(sshSignatureMessageWrapper{
		MagicHeader:   decodedSignature.MagicHeader,
		Namespace:     "git",
		HashAlgorithm: decodedSignature.HashAlgorithm,
		Hash:          string(hashMessage),
	})

	sshSig := ssh.Signature{}
	if err := ssh.Unmarshal([]byte(decodedSignature.Signature), &sshSig); err != nil {
		return err
	}

	currentDate := time.Now()
	for _, possibleSigningKey := range allowedSigningKeys {
		if currentDate.Before(possibleSigningKey.ValidAfter) {
			continue
		}
		if !possibleSigningKey.ValidBefore.IsZero() && currentDate.After(possibleSigningKey.ValidBefore) {
			continue
		}
		if possibleSigningKey.PublicKey.Verify(signedMessageToVerify, &sshSig) == nil {
			return nil
		}
	}

	return errors.New("no valid signing key found for the signature")
}

// https://github.com/openssh/openssh-portable/blob/826483d51a9fee60703298bbf839d9ce37943474/PROTOCOL.sshsig#L32
type wrappedSSHSignature struct {
	MagicHeader   [6]byte
	Version       uint32
	PublicKey     string
	Namespace     string
	Reserved      string
	HashAlgorithm string
	Signature     string
}

func decodeSignature(rawSignature []byte) (*wrappedSSHSignature, error) {
	rawSignatureLines := bytes.Split(rawSignature, []byte("\n"))
	for i := range rawSignatureLines {
		rawSignatureLines[i] = bytes.Trim(rawSignatureLines[i], " ")
	}

	pemBlock, _ := pem.Decode(bytes.Join(rawSignatureLines, []byte("\n")))
	if pemBlock == nil {
		return nil, errors.New("unable to decode the PEM block of the signature")
	}

	if pemBlock.Type != "SSH SIGNATURE" {
		return nil, fmt.Errorf("wrong pem block type: %s. Expected SSH-SIGNATURE", pemBlock.Type)
	}

	sig := wrappedSSHSignature{}
	if err := ssh.Unmarshal(pemBlock.Bytes, &sig); err != nil {
		return nil, err
	}

	if sig.Version != 1 {
		return nil, fmt.Errorf("unsupported signature version: %d", sig.Version)
	}
	if string(sig.MagicHeader[:]) != "SSHSIG" {
		return nil, fmt.Errorf("invalid magic header: %s", sig.MagicHeader[:])
	}
	if sig.Namespace != "git" {
		return nil, fmt.Errorf("invalid signature namespace: %s", sig.Namespace)
	}
	if _, ok := supportedHashAlgorithms[sig.HashAlgorithm]; !ok {
		return nil, fmt.Errorf("unsupported hash algorithm: %s", sig.HashAlgorithm)
	}

	return &sig, nil
}
