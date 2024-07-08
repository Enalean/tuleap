package main

import (
	"log"

	"github.com/sirupsen/logrus"
	"github.com/stripe/smokescreen/cmd"
	"github.com/stripe/smokescreen/pkg/smokescreen"
)


func main() {
	conf, err := cmd.NewConfiguration(nil, nil)
	if err != nil {
		logrus.Fatalf("Could not create configuration: %v", err)
	} else if conf != nil {
		conf.Log.Formatter = &logrus.JSONFormatter{}

		adapter := &smokescreen.Log2LogrusWriter{
			Entry: conf.Log.WithField("stdlog", "1"),
		}

		// Set the standard logger to use our logger's writer as output.
		log.SetOutput(adapter)
		log.SetFlags(0)
		smokescreen.StartWithConfig(conf, nil)
	}
}
