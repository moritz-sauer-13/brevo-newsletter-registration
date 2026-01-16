<?php

namespace Brevo\NewsletterRegistration\Pages;

use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Configuration;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\TagField\TagField;
use Page;
use Exception;
use GuzzleHttp\Client;

class BrevoNewsletterRegistrationPage extends Page
{
    private static $singular_name = 'Newsletter Anmeldung';
    private static $plural_name = 'Newsletter Anmeldungen';

    private static $db = [
        'APIKey' => 'Text',
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

        $fields->removeByName([
            'BrevoLists'
        ]);

        $fields->addFieldsToTab('Root.Brevo', [
            TextField::create('APIKey', _t(__CLASS__ . '.API_KEY', 'API Key')),
        ]);

        if($this->APIKey){
            $availableLists = [];
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->APIKey);
            $apiInstance = new ContactsApi(
                new Client(),
                $config
            );
            try {
                $result = $apiInstance->getLists();
                if($result && $result->getLists()){
                    foreach($result->getLists() as $list){
                        if(!BrevoList::ListExists($list['id'])){
                            BrevoList::create([
                                'ListID' => $list['id'],
                                'Title' => $list['name']
                            ])->write();
                        }
                    }
                    $fields->addFieldsToTab('Root.Brevo', [
                        ListboxField::create('BrevoLists', _t(__CLASS__ . '.LISTS', 'Listen'), BrevoList::get()->map('ID', 'Title'))
                            ->setDescription(_t(__CLASS__ . '.LISTS_DESCRIPTION', 'Hier muss mindestens eine Liste ausgewählt werden, zu der die Newsletteranmeldung bei Brevo hinzugefügt werden soll.')),
                    ]);
                }
            } catch (Exception $e) {
                $fields->addFieldToTab('Root.Brevo', HTMLEditorField::create('APIError', 'API Error', 'Error calling ListsApi->getLists: ' . $e->getMessage())->setReadonly(true));
            }
        }

        $fields->addFieldsToTab('Root.Links', [
            TreeDropdownField::create('OptInHintLinkID', _t(__CLASS__ . '.OPT_IN_HINT_LINK', 'Weiterleitung nach erfolgreicher Anmeldung mit Hinweis auf Double Opt-In Mail'), SiteTree::class),
            TreeDropdownField::create('SuccessLinkID', _t(__CLASS__ . '.SUCCESS_LINK', 'Weiterleitung nach Double Opt-In bestätigung'), SiteTree::class),
        ]);

        return $fields;
    }
}