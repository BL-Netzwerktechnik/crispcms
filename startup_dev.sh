#!/bin/bash

export FRAMEWORK_VERSION=$(git describe --tags --dirty --always)
export HOST=$(gp url 80 | sed -E 's_^https?://__')

composer install

docker-compose -f docker-compose.dev.yml up --build