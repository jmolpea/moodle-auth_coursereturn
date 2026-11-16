# Changelog

## 1.1.0 (2026111600)

- New: explicit confirmation. The link in the email opens a page that names the course, says what confirming does and shows a partly hidden email address; only its **Confirm and go to the course** button confirms the account. It no longer submits itself with JavaScript, so neither mail scanners nor link previews can confirm the account, and the user explicitly accepts the course and, if automatic enrolment is on, the enrolment request.
- New: the confirmation email names the course, explains the button and says until when the link is valid. With automatic enrolment on, it says that confirming means asking to be enrolled. Sign-ups without a visible course keep Moodle's standard email.
- New: the page shown after signing up names the course.
- New: an expired link shows its own message instead of "invalid confirmation data", and a link opened again after confirmation asks the user to log in to continue to the course.
- New: Brazilian Portuguese language pack.
- New: a "Configuration manual" link at the top of the plugin settings.
- Fix: whether the course may be named is now checked for the new account, not for the anonymous visitor, so the email and the confirmation page name the course on sites that force users to log in. Hidden courses are still never named.
- Supported Moodle versions: 4.5 LTS to 5.3, tested in CI.

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
