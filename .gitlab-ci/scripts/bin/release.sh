#!/bin/bash
#

set -eo

if [ "$1" == "github" ]; then
  if [ -f RELEASE_NOTES.md ]; then
      gh release create "${GITHUB_REF#refs/*/}" --notes-file RELEASE_NOTES.md
  else
      gh release create "${GITHUB_REF#refs/*/}" --notes "${GITHUB_REF#refs/*/}"
  fi
fi
