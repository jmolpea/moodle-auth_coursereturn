# Changelog

## 1.0.0 (2026092700)

First stable release for Moodle 4.5 LTS.

- Fixed: automatic enrolment skipped self-enrolment methods created with default settings, which store the enrolment key as NULL instead of an empty string.
- Security: confirmation lookups now require the secret as well as the username, so `/login/confirm.php` and `/auth/coursereturn/confirm.php` can no longer be used to check whether a username exists.
- Security: `start.php` returns the same error for missing and hidden courses.
- Diagnostics labels are now translatable language strings.
- Code brought in line with the Moodle coding style (moodle-cs `moodle-extra`: 0 errors, 0 warnings).
- Added GitHub Actions CI with moodle-plugin-ci.

## 1.0.0-rc4 (2026091701)

- The confirmation email credits the MFA email factor for the confirming session only.

## 1.0.0-rc3

- Site policy acceptance preserved across browsers; diagnostics page.
