#!/bin/bash
#

set -eo

case "$1" in
    gitlab)
      glab auth login -t "$GLAB_CLI_TOKEN"
      if [ -f RELEASE_NOTES.md ]; then
          glab release create "$CI_COMMIT_TAG" -F RELEASE_NOTES.md
      else
          glab release create "$CI_COMMIT_TAG" --notes "$CI_COMMIT_TAG"
      fi
    ;;
    github)
      if [ -f RELEASE_NOTES.md ]; then
          gh release create "${GITHUB_REF#refs/*/}" --notes-file RELEASE_NOTES.md
      else
          gh release create "${GITHUB_REF#refs/*/}" --notes "${GITHUB_REF#refs/*/}"
      fi
    ;;
    *) echo "Unrecognized option"
    ;;
esac

