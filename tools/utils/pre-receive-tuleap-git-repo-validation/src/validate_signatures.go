package main

import (
	"io"
	"regexp"
	"strings"

	"github.com/go-git/go-git/v5"
	"github.com/go-git/go-git/v5/plumbing"
)

var sshCommitSignature = regexp.MustCompile(`(?Us)gpgsig (-----BEGIN SSH SIGNATURE-----.*-----END SSH SIGNATURE-----)`)
var sshTagSignature = regexp.MustCompile(`(?s)-----BEGIN SSH SIGNATURE-----.*-----END SSH SIGNATURE-----\n$`)

func ValidateSignatures(hookData HookData, repo *git.Repository) (*string, error) {
	var notSignedItems []string
	for referenceName := range hookData.UpdatedReferences {
		var objectType plumbing.ObjectType
		if strings.HasPrefix(referenceName, "refs/tags/") {
			objectType = plumbing.TagObject
		} else if strings.HasPrefix(referenceName, "refs/heads/") {
			objectType = plumbing.CommitObject
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

		if objectType == plumbing.CommitObject && !sshCommitSignature.Match(encodedObjectContent) {
			notSignedItems = append(notSignedItems, "head commit of "+referenceName)
			continue
		}

		if objectType == plumbing.TagObject && !sshTagSignature.Match(encodedObjectContent) {
			notSignedItems = append(notSignedItems, "annotated tag "+referenceName)
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
