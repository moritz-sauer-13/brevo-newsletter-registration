<?php

namespace Brevo\NewsletterRegistration\Pages;

use Brevo\NewsletterRegistration\Traits\BrevoNewsletterFormTrait;
use PageController;

class BrevoNewsletterRegistrationPageController extends PageController
{
    use BrevoNewsletterFormTrait;

    private static $allowed_actions = [
        'NewsletterRegistrationForm',
        'handleNewsletter',
    ];

    protected function getNewsletterDataProvider()
    {
        return $this->data();
    }
}
