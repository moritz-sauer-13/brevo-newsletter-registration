# Brevo Newsletter Registration for SilverStripe

Adds a page type with a Newsletter Registration Form that integrates with Brevo (formerly Sendinblue).

## Features

- Custom Newsletter Registration Page.
- Selectable Brevo lists for subscription.
- Support for Double Opt-In (DOI) via Brevo.
- Configurable redirect pages for success and DOI hints.
- Fully localized (English and German).
- Extensible via SilverStripe Extension system.

## Requirements

- SilverStripe CMS ^5
- `getbrevo/brevo-php` ^2.0

## Installation

```bash
composer require moritz-sauer-13/brevo-newsletter-registration
```

After installation, run a `dev/build?flush=all`.

## Configuration

The registration page can be configured via the CMS (Brevo and Links tabs). Here you can set the API Key, select Brevo lists, and define the DOI Template ID.

## Usage

1. Create a new page of type "Newsletter Anmeldung" in the CMS.
2. Enter your Brevo API Key in the "Brevo" tab.
3. Select one or more lists after the API key is validated.
4. Configure redirect pages in the "Links" tab.

## Extensibility

Available hooks for `BrevoNewsletterRegistrationPageController`:

- `updateNewsletterRegistrationFields($fields)`
- `updateNewsletterRegistrationRequiredFields($requiredFields)`
- `updateNewsletterRegistrationData($data)`
- `updateNewsletterRegistrationContactAttributes($contactAttributes, $data)`
- `updateCreateDoiContact($createContact, $data)`
- `onAfterNewsletterRegistration($data, $form)`

## License
MIT