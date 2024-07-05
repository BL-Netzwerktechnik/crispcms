#!/bin/bash

# If debug parameter is passed, run in debug mode and remove the debug parameter

docker exec -u 33 -e LOG_LEVEL=info -it core-license-1 crisp-cli $@