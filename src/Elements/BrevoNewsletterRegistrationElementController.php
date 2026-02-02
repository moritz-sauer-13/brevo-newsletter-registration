<?php

namespace Brevo\NewsletterRegistration\Elements;

use Brevo\NewsletterRegistration\Traits\BrevoNewsletterFormTrait;
use DNADesign\Elemental\Controllers\ElementController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\UserForms\Control\UserDefinedFormController;

class BrevoNewsletterRegistrationElementController extends ElementController
{
    use BrevoNewsletterFormTrait {
        handleNewsletter as traitHandleNewsletter;
    }

    private static $allowed_actions = [
        'NewsletterRegistrationForm',
        'handleNewsletter',
    ];

    protected function getNewsletterDataProvider()
    {
        return $this->getElement();
    }

    /**
     * Wrapper method to handle the newsletter form submission
     * Adapts the RequestHandler signature to the Form action handler signature
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function handleNewsletter(HTTPRequest $request)
    {
        $form = $this->NewsletterRegistrationForm();
        $data = $request->postVars();

        return $this->traitHandleNewsletter($data, $form);
    }
}
