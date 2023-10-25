#!/bin/bash

CI_COMMIT_TAG=$(git describe --tags --always)

echo $CI_COMMIT_TAG | cut -d. -f1
echo $CI_COMMIT_TAG | cut -d. -f2
echo $CI_COMMIT_TAG | cut -d. -f3

