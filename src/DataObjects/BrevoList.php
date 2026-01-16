<?php

namespace Brevo\NewsletterRegistration\DataObjects;

use SilverStripe\ORM\DataObject;
use Brevo\NewsletterRegistration\Pages\BrevoNewsletterRegistrationPage;

class BrevoList extends DataObject
{
    private static $table_name = 'BrevoNewsletterList';

    private static $db = [
        'ListID' => 'Text',
        'Title' => 'Text',
    ];

    private static $belongs_many_many = [
        'NewsletterRegistrationPages' => BrevoNewsletterRegistrationPage::class
    ];

    public static function ListExists($listID)
    {
        return (bool)BrevoList::get()->filter('ListID', $listID)->first();
    }
}