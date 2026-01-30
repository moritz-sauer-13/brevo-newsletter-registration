<?php

namespace Brevo\NewsletterRegistration\Elements;

use Brevo\NewsletterRegistration\Traits\BrevoNewsletterFormTrait;
use DNADesign\Elemental\Controllers\ElementController;

class BrevoNewsletterRegistrationElementController extends ElementController
{
    use BrevoNewsletterFormTrait;

    private static $allowed_actions = [
        'NewsletterRegistrationForm',
        'handleNewsletter',
    ];

    protected function getNewsletterDataProvider()
    {
        return $this->getElement();
    }
}
