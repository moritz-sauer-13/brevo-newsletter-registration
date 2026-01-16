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
            TextField::create('APIKey', 'API Key'),
        ]);

        if($this->APIKey){
            $availableLists = [];
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->APIKey);
            $apiInstance = new ContactsApi(
                new GuzzleHttp\Client(),
                $config
            );
            try {
                $result = $apiInstance->getLists();
                if($result && $result->getLists()){
                    foreach($result->getLists() as $list){
                        if(!BrevoList::ListExists($list['id'])){
                            BrevoList::create([
                                'ID' => $list['id'],
                                'Title' => $list['name']
                            ])->write();
                        }
                    }
                    $fields->addFieldsToTab('Root.Brevo', [
                        ListboxField::create('BrevoLists', 'Listen', BrevoList::get()->map())
                            ->setDescription('Hier muss mindestens eine Liste ausgewählt werden, zu der die Newsletteranmeldung bei Brevo hinzugefügt werden soll.'),
                    ]);
                }
            } catch (Exception $e) {
                echo 'Exception when calling ListsApi->getLists: ', $e->getMessage(), PHP_EOL;
            }
        }

        $fields->addFieldsToTab('Root.Links', [
            TreeDropdownField::create('OptInHintLinkID', 'Weiterleitung nach erfolgreicher Anmeldung mit Hinweis auf Double Opt-In Mail', SiteTree::class),
            TreeDropdownField::create('SuccessLinkID', 'Weiterleitung nach Double Opt-In bestätigung', SiteTree::class),
        ]);

        return $fields;
    }
}