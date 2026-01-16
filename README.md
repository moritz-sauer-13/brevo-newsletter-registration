# Brevo Newsletter Registration for SilverStripe

Adds a page type with a Newsletter Registration Form that integrates with Brevo (formerly Sendinblue).

## Features

- Custom Newsletter Registration Page.
- Selectable Brevo lists for subscription.
- Support for Double Opt-In (DOI) via Brevo.
- Configurable redirect pages for success and DOI hints.
- Fully localized (English and German).

## Requirements

- SilverStripe CMS ^5
- `getbrevo/brevo-php` ^2.0

## Installation

```bash
composer require moritz-sauer-13/brevo-newsletter-registration
```

After installation, run a `dev/build?flush=all`.

## Configuration

You can configure the default DOI template ID via SilverStripe's configuration system:

```yaml
Brevo\NewsletterRegistration\Pages\BrevoNewsletterRegistrationPageController:
  doi_template_id: 3
```

## Usage

1. Create a new page of type "Newsletter Anmeldung" in the CMS.
2. Enter your Brevo API Key in the "Brevo" tab.
3. Save the page. The available lists from your Brevo account will be fetched and displayed.
4. Select one or more lists.
5. In the "Links" tab, select pages for redirection:
   - **Opt-In Hint Link**: Where to go after the form is submitted (e.g., "Please check your emails").
   - **Success Link**: The URL Brevo will redirect the user to after they clicked the DOI link (must match your Brevo template configuration).

## License
MIT