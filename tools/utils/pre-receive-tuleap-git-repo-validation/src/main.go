package main

import (
	"bufio"
	"encoding/json"
	"fmt"
	"os"
)

type HookData struct {
	UpdatedReferences map[string]UpdatedReference `json:"updated_references"`
}

type UpdatedReference struct {
	OldValue string `json:"old_value"`
	NewValue string `json:"new_value"`
}

type HookResult struct {
	RejectionMessage *string `json:"rejection_message"`
}

func checkIfError(err error) {
	if err == nil {
		return
	}

	fmt.Fprintln(os.Stderr, err)
	os.Exit(0)
}

func main() {
	var input []byte
	for in := bufio.NewScanner(os.Stdin); in.Scan(); {
		input = append(input, in.Bytes()...)
	}

	var hookData HookData
	err := json.Unmarshal(input, &hookData)
	checkIfError(err)

	rejectionMessage := ValidateTagsFormat(hookData)

	output, err := json.Marshal(HookResult{RejectionMessage: rejectionMessage})
	checkIfError(err)

	fmt.Println(string(output))
	os.Exit(0)
}
