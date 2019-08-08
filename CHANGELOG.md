# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [unreleased]
### Added
- Groundwork to support multiple openpgp backends

### Improved
- PB-391: As an admin deleting a user I should see the name and email of the user i'm about the delete in the model dialog
- PB-396: As a user deleting a password I should see the name of the password i’m about to delete in the modal
- PB-397: As a user in the user workspace, I should see a relevant feedback if a user is not a member of any group
- PB-505: Upgrade CakePHP (3.8.0)
- PB-533: PB-533 Implement /auth/is-authenticated to check if the user authenticated status without extending the session
- PB-601: Added test to check which data is saved for "add favorite" endpoint and check if resource endpoint is CSRF protected.
- PB-242: Performance - Fetch the passwords in priority
- Extend resource checkbox clickable area
- Select automatically the resource which has been created
- Select created tag after the import of resources
- Unselect the select all checkbox if all passwords are not selected

### Fixed
- PB-330: Don't execute the migration script V210InstallAccountSettingsPlugin if the table organization_settings already exists
- PB-364: Fix Pressing enter on tag editor doesn't hide autocomplete
- PB-427: The email sender shell task shouldn't have to access the organization_settings table to send emails
- PB-572: Fix MFA verification should happen after every login or after 30 days if remember me selected
- Fix reload button javascript on setup start page
- Fix an admin can trigger a XSS during setup with malicious firstname
