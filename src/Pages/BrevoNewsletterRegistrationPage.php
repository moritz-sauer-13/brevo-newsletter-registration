<?php

namespace Brevo\NewsletterRegistration\Pages;

use Brevo\NewsletterRegistration\DataObjects\BrevoList;
use Brevo\NewsletterRegistration\Traits\BrevoNewsletterConfigTrait;
use SilverStripe\CMS\Model\SiteTree;
use Page;

class BrevoNewsletterRegistrationPage extends Page
{
    use BrevoNewsletterConfigTrait;

    private static $table_name = 'BrevoNewsletterRegistrationPage';

    private static $singular_name = 'Newsletter Anmeldung';
    private static $plural_name = 'Newsletter Anmeldungen';

    private static $db = [
        'APIKey' => 'Text',
        'DOITemplateID' => 'Int',
    ];

    private static $many_many = [
        'BrevoLists' => BrevoList::class
    ];

    private static $has_one = [
        'SuccessLink' => SiteTree::class,
        'OptInHintLink' => SiteTree::class,
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        return $this->getBrevoConfigFields($fields);
    }
}
