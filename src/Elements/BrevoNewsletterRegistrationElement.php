<?php

namespace Brevo\NewsletterRegistration\Elements;

use Brevo\NewsletterRegistration\Controller\BrevoNewsletterRegistrationPageController;
use Brevo\NewsletterRegistration\DataObjects\BrevoList;
use Brevo\NewsletterRegistration\Traits\BrevoNewsletterConfigTrait;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\UserForms\Control\UserDefinedFormController;

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

    public function NewsletterRegistrationForm()
    {
        $controller = BrevoNewsletterRegistrationPageController::create($this);
        $current = Controller::curr();

        if ($current) {
            $request = $current->getRequest();
            $controller->setRequest($request);
        }

        if ($current && $current->getAction() == 'handleNewsletter' && $request->param('ID') == $this->ID) {
            die('XYZ');
            return $controller->renderWith(BrevoNewsletterRegistrationPageController::class .'_ReceivedFormSubmission');
        }
        // Get the page link from the element's parent page for correct form action URL
        $page = $this->getPage();
        if ($page) {
            $link = $page->Link();
        } else {
            // Fallback: suppress E_USER_WARNING if url_segment config missing
            set_error_handler(fn(int $errno, string $errstr) => true, E_USER_WARNING);
            $link = $current?->Link() ?? '';
            restore_error_handler();
        }

        if($link == '/'){
            $link = '/home';
        }

        $form = $controller->NewsletterRegistrationForm();
        $form->setFormAction(
            Controller::join_links(
                $link,
                'element',
                $this->ID,
                'handleNewsletter'
            )
        );

        return $form;
    }


}
