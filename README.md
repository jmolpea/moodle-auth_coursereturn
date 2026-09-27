# Email registration with course return (auth_coursereturn)

Email self-registration for Moodle that sends new users back to the course they came from once they confirm their account. Optionally, it enrols them in that course, provided the course's own self-enrolment method allows it.

Without this plugin, a visitor who starts on a course page, creates an account and clicks the confirmation email usually ends up on the Dashboard and has to find the course again. With it, the confirmation link logs them in, runs Moodle's normal access checks (MFA, site policies, required profile fields) and then takes them to the course.

## Flow

Course page → standard Moodle sign-up form (with a notice naming the course) → confirmation email → confirm → Moodle login, MFA and policies → course (and, optionally, self-enrolment).

## Requirements

- Moodle 4.5 LTS (`$plugin->supported = [405, 405]`).
- Core plugins `auth_email` and `enrol_self` (both ship with Moodle).
- Outgoing email configured on the site (SMTP recommended) and cron running.

## Installation

1. **Site administration → Plugins → Install plugins**, upload the ZIP and complete the upgrade.
   Alternatively, copy this folder to `auth/coursereturn` and visit **Site administration → Notifications**.
2. **Site administration → Plugins → Authentication → Manage authentication**:
   - enable **Email registration with course return**;
   - select it as the **Self registration** method.

   These are two separate settings. Keep enabled any method used by existing accounts, including *Email-based self-registration*, so pending accounts can still confirm.
3. Open the plugin settings and decide whether to enable **Enrol automatically when permitted** (off by default).
4. In each course that should accept automatic enrolment, check **Participants → Enrolment methods → Self enrolment**: enabled, *Allow new enrolments* on, no enrolment key and no group key. Dates, capacity and cohort restrictions are respected.
5. Recommended: enable reCAPTCHA for self-registration (see *Security*).

## Linking to a course

Use either link on your website, catalogue or marketing emails:

```text
https://YOUR-SITE/auth/coursereturn/start.php?id=COURSEID
https://YOUR-SITE/course/view.php?id=COURSEID
```

`start.php` stores the course explicitly and opens the login page, where visitors choose **Create new account**. It is the most reliable option for external catalogues, custom themes and courses that allow guest access. Visitors who open `/login/signup.php` directly, without a course, are confirmed and logged in and land on the Dashboard: the plugin never guesses a course.

## Behaviour

| Case | Result |
|---|---|
| Sign-up started from a course | Confirms, logs in and returns to the course; enrols when enabled and permitted. |
| Sign-up without a course | Confirms, logs in, goes to the Dashboard. |
| MFA with the email factor | The confirmation email proves mailbox ownership for that session, so no second code is sent. Other factors and later logins follow the site's MFA configuration. |
| Site policies | Acceptances made on the sign-up form are kept; pending or new policies are requested by Moodle. |
| Link opened again after confirmation | Normal login is required. A confirmation email is never a reusable login link. |
| Another account already logged in | The session is not replaced; the user is asked to log out or use a private window. |
| JavaScript disabled | A button completes the confirmation. |
| Course with a key, closed, full or restricted | No enrolment; Moodle's normal course access page is shown. |
| Existing suspended or expired enrolment | Never reactivated. |

## Confirmation email

The confirmation email is Moodle's standard one, sent **synchronously while the account is created** (it does not wait for cron), exactly as core email self-registration does. The only difference is that the link points to `/auth/coursereturn/confirm.php`. "Resend confirmation email" from user administration also works: those links use `/login/confirm.php` and are routed to the plugin.

If users report that the email does not arrive but a manual resend does, the cause is on the delivery side, not in the plugin. Check:

- the plugin's diagnostics page (below): *Failed email events*, *All email disabled (noemailever)* and *Email diversion*;
- the mail server or provider logs for the recipient address at sign-up time;
- the spam/quarantine folder and greylisting (the first message to a new address is often delayed by several minutes; a resend made later is delivered immediately);
- SPF, DKIM and DMARC alignment of the *noreply* address used by Moodle (**Site administration → Server → Email → Outgoing mail configuration**).

## Diagnostics

Administrators (`moodle/site:config`) can open **Site administration → Plugins → Authentication → Email registration with course return → Registration and course return diagnostics**, or `/auth/coursereturn/diagnostics.php`. It shows the relevant site configuration and, for a given username, the account state, saved course, pending destination, failed email events and policy acceptance. It is read-only and never displays passwords, email bodies or confirmation secrets.

## Security

- **No new account-creation endpoint.** Accounts are created only by Moodle's `/login/signup.php`, with its validation, password policy, allowed/denied email domains, digital age of consent, site policies and CAPTCHA. The plugin keeps the reCAPTCHA setting of *Email-based self-registration* if it was already enabled.
- **Mass sign-ups.** Moodle has no built-in rate limit for self-registration. On public sites enable reCAPTCHA, consider *allowemailaddresses*/*denyemailaddresses*, keep *Delete not fully set up users after* (`deleteunconfirmed`) at its default, and rate-limit `/login/signup.php` at the web server or WAF. Automatic enrolment only happens after the mailbox is confirmed and only through an open self-enrolment method, so it grants nothing that any logged-in user could not already do by clicking *Enrol me*.
- **Confirmation.** Secrets are Moodle's own (15 random characters), compared in constant time and valid for a configurable period (7 days by default). Lookups require username *and* secret, so the confirmation pages cannot be used to discover usernames. GET and HEAD requests never confirm: the page submits a sesskey-protected POST, so simple link scanners cannot consume the link. The page sends `Content-Security-Policy`, `Cache-Control: no-store` and `Referrer-Policy: no-referrer`.
- **Login.** Uses `complete_user_login()` (session ID regeneration, events, concurrent-login limits). A session is never created from an id received in a URL, and an existing session of another account is never replaced.
- **Redirects.** The destination is limited to existing courses on the configured `wwwroot`; there is no redirect parameter. `start.php` returns the same error for missing and hidden courses.
- **Enrolment.** Runs after `require_login()` and MFA, through `enrol_self`'s own `can_self_enrol()` and `enrol_self()`, with a lock per instance.

## Privacy

The plugin stores one temporary user preference, `auth_coursereturn_pending` (course id and expiry time), from confirmation until the user reaches the course, for at most 24 hours; a scheduled task removes expired values. It is described and exported through the Privacy API and deleted by Moodle with the rest of the user's preferences. Core's `auth_email_wantsurl` preference is used as in core email self-registration. No data leaves the site.

## Uninstalling

Uninstall through **Site administration → Plugins → Plugins overview** before deleting the folder. Accounts using this method are switched to *Email-based self-registration*, which is enabled, and the temporary preference is removed. Passwords and enrolments are kept. Links in confirmation emails sent before uninstalling stop working; resend them from user administration.

## Development

```sh
php admin/tool/phpunit/cli/init.php --disable-composer
vendor/bin/phpunit --test-suffix _test.php auth/coursereturn/tests
```

Continuous integration runs [moodle-plugin-ci](https://github.com/moodlehq/moodle-plugin-ci) on every push.

## License

2026 RSMAX Consulting SL. GNU GPL v3 or later; see [LICENSE](LICENSE).
