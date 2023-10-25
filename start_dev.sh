#!/bin/bash

export GIT_TAG=$(git describe --tags --always)
export HOST=$(gp url 80 | sed -E 's_^https?://__')

docker-compose -f docker-compose.dev.yml up