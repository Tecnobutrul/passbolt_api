# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [unreleased]
### Added
- PB-244: As LU I should be able to rename/delete tags from the tags filter section
- Multiple openpgp backend support

### Improved
- PB-391: As an admin deleting a user I should see the name and email of the user i'm about the delete in the model dialog
- PB-396: As a user deleting a password I should see the name of the password i’m about to delete in the modal
- PB-397: As a user in the user workspace, I should see a relevant feedback if a user is not a member of any group

### Fixed
- PB-330: Don't execute the migration script V210InstallAccountSettingsPlugin if the table organization_settings already exists
- PB-364: Fix Pressing enter on tag editor doesn't hide autocomplete
- PB-339: Non admin users should not be able to create shared tags
- PB-427: The email sender shell task shouldn't have to access the organization_settings table to send emails
