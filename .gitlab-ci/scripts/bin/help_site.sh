#!/usr/bin/env bash
#

set -euo

CI_SCRIPTS_DIR=$(dirname "$0")/..

# shellcheck source=.gitlab-ci/scripts/lib/git-helpers.sh
source "$CI_SCRIPTS_DIR"/lib/git-helpers.sh

PASSBOLT_HELP_DIR="passbolt_help"
GITLAB_USER_EMAIL="contact@passbolt.com"
GIT_CI_TOKEN_NAME=${GIT_CI_TOKEN_NAME:-gitlab-ci-token}
ACCESS_TOKEN_NAME="help-site-bot"
CI_COMMIT_TAG_TEST="4.0.1-test-helpsite"
HELP_SITE_REPO="gitlab.com/passbolt/passbolt-help.git"


function create_release_notes() {
    title="$(grep name ../config/version.php | awk -F "'" '{print $4}')"
    slug="$(grep name ../config/version.php | awk -F "'" '{print $4}' | tr ' ' '_' | tr '[:upper:]' '[:lower:]')"
    categories="releases $PASSBOLT_FLAVOUR"
    song="$(grep 'Release song:' RELEASE_NOTES.md | awk '{print $2}')"
    quote="$(grep name ../config/version.php | awk -F "'" '{print $4}')"
    permalink="/releases/$PASSBOLT_FLAVOUR/$(grep name ../config/version.php | awk -F "'" '{print $4}' | tr ' ' '_' | tr '[:upper:]' '[:lower:]')"
    date="$(date +'%Y-%m-%d')"

    cat << EOF >> _releases/"$PASSBOLT_FLAVOUR"/"$CI_COMMIT_TAG_TEST".md
---
title: $title
slug: $slug
layout: release
categories: $categories
version: $CI_COMMIT_TAG_TEST
product: $PASSBOLT_FLAVOUR
song: $song
quote: $quote
permalink: $permalink
date: $date
---
EOF

    cat RELEASE_NOTES.md >> _releases/"$PASSBOLT_FLAVOUR"/"$CI_COMMIT_TAG_TEST".md
}

setup_gpg_key "$GPG_KEY_PATH" "$GPG_PASSPHRASE" "$GPG_KEY_GRIP"
setup_git_user "$GITLAB_USER_EMAIL" "$ACCESS_TOKEN_NAME"

git clone -b master https://"$HELPSITE_TOKEN_NAME":"$HELPSITE_TOKEN"@"$HELP_SITE_REPO" "$PASSBOLT_HELP_DIR"
cd "$PASSBOLT_HELP_DIR"
create_release_notes
git checkout -b release_notes_"$CI_COMMIT_TAG_TEST"
git add _releases/"$PASSBOLT_FLAVOUR"/"$CI_COMMIT_TAG_TEST".md
git commit -m ":robot: Automatically added release notes for version $CI_COMMIT_TAG_TEST $PASSBOLT_FLAVOUR"
glab mr create -s release_notes_"$CI_COMMIT_TAG_TEST" -b master -d ":robot: Release notes for $CI_COMMIT_TAG_TEST" -t "Release notes for $CI_COMMIT_TAG_TEST" --push
bash .gitlab-ci/scripts/bin/slack-status-messages.sh ":notebook: New helpsite release notes created for $CI_COMMIT_TAG_TEST" "https://gitlab.com/passbolt/passbolt-help/-/merge_requests"
