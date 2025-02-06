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
	"github.com/go-git/go-git/v5/plumbing/object"
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
	for referenceName, referenceValues := range hookData.UpdatedReferences {
		if strings.HasPrefix(referenceName, "refs/tags/") {
			reference, err := repo.Reference(plumbing.ReferenceName(referenceName), false)
			if err != nil {
				return nil, err
			}

			notSignedMessage, err := checkGitObjectSignature(
				repo,
				"annotated tag "+referenceName,
				plumbing.TagObject,
				reference.Hash(),
				allowedSigningKeys,
			)

			if err != nil {
				return nil, err
			}

			if notSignedMessage != "" {
				notSignedItems = append(notSignedItems, notSignedMessage)
			}
		} else if strings.HasPrefix(referenceName, "refs/heads/") {
			if referenceValues.NewValue == "0000000000000000000000000000000000000000" {
				continue
			}

			currentCommit, err := repo.CommitObject(plumbing.NewHash(referenceValues.NewValue))
			if err != nil {
				return nil, fmt.Errorf("could not get new rev: %w", err)
			}
			oldCommit, err := repo.CommitObject(plumbing.NewHash(referenceValues.OldValue))
			if err != nil {
				return nil, fmt.Errorf("could not get old rev: %w", err)
			}

			commitsToVerify, err := findNewCommits(currentCommit, oldCommit)
			if err != nil {
				return nil, fmt.Errorf("could not identify commits to verify: %w", err)
			}

			commitsHashSignatureVerified := make(map[plumbing.Hash]bool)
			for _, commitToVerify := range commitsToVerify {
				notSignedMessage, err := checkGitObjectSignature(
					repo,
					"commit "+commitToVerify.Hash.String(),
					plumbing.CommitObject,
					commitToVerify.Hash,
					allowedSigningKeys,
				)
				if err != nil {
					return nil, err
				}

				if notSignedMessage == "" {
					commitsHashSignatureVerified[commitToVerify.Hash] = true
					commitToVerify.Parents().ForEach(func(commit *object.Commit) error {
						commitsHashSignatureVerified[commit.Hash] = true
						return nil
					})
				}
			}

			for _, commitToVerify := range commitsToVerify {
				if !commitsHashSignatureVerified[commitToVerify.Hash] {
					notSignedItems = append(notSignedItems, "commit "+commitToVerify.Hash.String())
				}
			}
		}
	}

	if len(notSignedItems) > 0 {
		rejectionMessage := "The following items '" + strings.Join(notSignedItems, ", ") + "' have not been signed, please see docs/release.md"
		return &rejectionMessage, nil
	}

	return nil, nil
}

func findNewCommits(fromCommit *object.Commit, tailCommit *object.Commit) ([]*object.Commit, error) {
	newCommits := []*object.Commit{}
	if fromCommit.Hash == tailCommit.Hash {
		return newCommits, nil
	}

	isAncestor, err := fromCommit.IsAncestor(tailCommit)
	if err != nil || isAncestor {
		return newCommits, err
	}

	newCommits = append(newCommits, fromCommit)
	err = fromCommit.Parents().ForEach(func(commit *object.Commit) error {
		additionalCommits, err := findNewCommits(commit, tailCommit)
		if err != nil {
			return err
		}

		newCommits = append(
			newCommits,
			additionalCommits...,
		)
		return nil
	})

	return newCommits, err

}

func checkGitObjectSignature(repo *git.Repository, itemName string, objectType plumbing.ObjectType, hash plumbing.Hash, allowedSigningKeys []IntegratorSigningKey) (string, error) {
	encodedObjectContent, err := getEncodedObjectContent(repo, objectType, hash)
	if err == plumbing.ErrObjectNotFound && objectType == plumbing.TagObject {
		return fmt.Sprintf("%s (not annotated tag?)", itemName), nil
	} else if err != nil {
		return "", err
	}

	var signatureExtractRegexp *regexp.Regexp
	if objectType == plumbing.TagObject {
		signatureExtractRegexp = sshTagSignature
	} else if objectType == plumbing.CommitObject {
		signatureExtractRegexp = sshCommitSignature
	}

	matches := signatureExtractRegexp.FindSubmatch(encodedObjectContent)
	if len(matches) != 2 {
		return itemName, nil
	}

	err = verifySignature(
		allowedSigningKeys,
		matches[1],
		bytes.ReplaceAll(encodedObjectContent, matches[0], []byte("")),
	)
	if err != nil {
		return fmt.Sprintf("%s (%s)", itemName, err), nil
	}
	return "", nil
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
