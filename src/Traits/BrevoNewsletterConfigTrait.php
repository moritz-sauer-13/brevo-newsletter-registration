<?php

namespace Brevo\NewsletterRegistration\Traits;

use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Configuration;
use Brevo\NewsletterRegistration\DataObjects\BrevoList;
use GuzzleHttp\Client;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use Exception;

/**
 * Trait for shared Brevo configuration fields (Page & Element)
 * Can be extended via updateBrevoConfigFields hook
 */
trait BrevoNewsletterConfigTrait
{
    public function getBrevoConfigFields($fields)
    {
        $fields->removeByName(['BrevoLists']);

        $fields->addFieldsToTab('Root.Brevo', [
            TextField::create('APIKey', _t(__CLASS__ . '.API_KEY', 'API Key')),
            TextField::create('DOITemplateID', _t(__CLASS__ . '.DOI_TEMPLATE_ID', 'DOI Template ID')),
        ]);

        if ($this->APIKey) {
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->APIKey);
            $apiInstance = new ContactsApi(new Client(), $config);
            try {
                $result = $apiInstance->getLists();
                if ($result && $result->getLists()) {
                    foreach ($result->getLists() as $list) {
                        if (!BrevoList::ListExists($list['id'])) {
                            $newList = new BrevoList([
                                'ListID' => (string) $list['id'],
                                'Title' => $list['name']
                            ]);
                            $newList->write();
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

        $this->extend('updateBrevoConfigFields', $fields);

        return $fields;
    }
}
