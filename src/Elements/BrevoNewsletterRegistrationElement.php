<?php

namespace Brevo\NewsletterRegistration\Elements;

use Brevo\NewsletterRegistration\DataObjects\BrevoList;
use Brevo\NewsletterRegistration\Traits\BrevoNewsletterConfigTrait;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;

class BrevoNewsletterRegistrationElement extends BaseElement
{
    use BrevoNewsletterConfigTrait;

    private static $table_name = 'BrevoNewsletterRegistrationElement';

    private static $singular_name = 'Newsletter Anmeldung';
    private static $plural_name = 'Newsletter Anmeldungen';

    private static $icon = 'font-icon-p-mail';

    private static $db = [
        'APIKey' => 'Text',
        'DOITemplateID' => 'Int',
        'Content' => 'HTMLText',
    ];

    private static $many_many = [
        'BrevoLists' => BrevoList::class
    ];

    private static $has_one = [
        'SuccessLink' => SiteTree::class,
        'OptInHintLink' => SiteTree::class,
    ];

    private static $defaults = [
        'DOITemplateID' => 2,
    ];

    private static $controller_class = BrevoNewsletterRegistrationElementController::class;

    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Newsletter Anmeldung');
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab('Root.Main', [
            HTMLEditorField::create('Content', _t(__CLASS__ . '.CONTENT', 'Inhalt')),
        ]);

        return $this->getBrevoConfigFields($fields);
    }
}
