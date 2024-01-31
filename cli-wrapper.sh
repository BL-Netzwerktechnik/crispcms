#!/bin/bash

# If debug parameter is passed, run in debug mode and remove the debug parameter

if [ "$1" == "debug" ]; then

    # Remove debug parameter
    shift

    docker exec -u 33 -it crispcms crisp-cli --loglevel=debug $@
    exit 0
fi

docker exec -u 33 -it crispcms crisp-cli --loglevel=info $@