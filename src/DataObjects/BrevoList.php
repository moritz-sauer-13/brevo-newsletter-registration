<?php

namespace Brevo\NewsletterRegistration\DataObjects;

use SilverStripe\ORM\DataObject;

class BrevoList extends DataObject
{
    private static $db = [
        'ListID' => 'Text',
        'Title' => 'Text',
    ];

    private static $belongs_many_many = [
        'NewsletterRegistrationPages' => NewsletterRegistrationPage::class
    ];

    public static function ListExists($listID)
    {
        return (bool)BrevoList::get()->filter('ListID', $listID)->first();
    }
}