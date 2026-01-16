<?php

namespace Brevo\NewsletterRegistration\Pages;

use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\CreateAttribute;
use Brevo\Client\Model\CreateContact;
use Brevo\Client\Model\CreateDoiContact;
use Brevo\Client\Model\UpdateContact;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;
use PageController;
use Exception;
use GuzzleHttp\Client;

class BrevoNewsletterRegistrationPageController extends PageController
{
    private static $allowed_actions = [
        'NewsletterRegistrationForm',
        'handleNewsletter',
    ];

    private static $api_url = 'https://api.brevo.com/v3';

    public function NewsletterRegistrationForm()
    {
        if(!$this->APIKey){
            return null;
        }
        $fields = FieldList::create(
            EmailField::create('Email', _t('Newsletter.EMAILADDRESS', 'E-Mail Adresse')),
            DropdownField::create('Salutation', _t('Newsletter.SALUTATION', 'Anrede'), [
                'Herr' => _t('Newsletter.MR', 'Herr'),
                'Frau' => _t('Newsletter.MRS', 'Frau'),
                'Divers' => _t('Newsletter.DIVERS', 'Divers'),
            ])->setEmptyString(_t('Newsletter.SELECT', '- - bitte wählen - -')),
            TextField::create('FirstName', _t('Newsletter.FIRSTNAME', 'Vorname')),
            TextField::create('LastName', _t('Newsletter.LASTNAME', 'Nachname'))
        );
        $this->extend('updateNewsletterRegistrationFields', $fields);

        $brevoLists = $this->BrevoLists();
        if ($brevoLists->count() > 1) {
            $fields->push(CheckboxSetField::create('Lists', _t('Newsletter.LISTS', 'Listen'), $brevoLists));
        } elseif ($brevoLists->count() == 1) {
            $fields->push(HiddenField::create('Lists[0]', 'Lists', $brevoLists->first()->ID));
        }

        $actions = FieldList::create(
            FormAction::create('handleNewsletter', _t('Newsletter.SUBSCRIBE', 'Anmelden'))
                ->addExtraClass('btn-primary-red-focus')
        );

        $requiredFields = RequiredFields::create('Email', 'Salutation', 'FirstName', 'LastName');
        if ($this->BrevoLists()->count() > 1) {
            $requiredFields->addRequiredField('Lists');
        }
        $this->extend('updateNewsletterRegistrationRequiredFields', $requiredFields);

        $form = Form::create($this, __FUNCTION__, $fields, $actions, $requiredFields);
        $form->setTemplate('NewsletterRegistrationForm');
        $form->enableSpamProtection();

        return $form;
    }

    public function handleNewsletter($data, $form)
    {
        $data = Convert::raw2sql($data);
        $this->extend('updateNewsletterRegistrationData', $data);
        if (!$this->APIKey) {
            $form->sessionMessage('API Key failed', 'bad');
            return $this->redirectBack();
        }

        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->APIKey);
        $apiInstance = new ContactsApi(
            new Client(),
            $config
        );

        $newSalutation =  match ($data['Salutation']) {
            'Herr' => 2,
            'Frau' => 1,
            'Divers' => 3,
            default => 0,
        };

        $contactAttributes = [
            'VORNAME' => $data['FirstName'],
            'NACHNAME' => $data['LastName'],
            'ANREDE' => $data['Salutation'],
            'ANREDE_NEU' => $newSalutation,
        ];

        $this->extend('updateNewsletterRegistrationContactAttributes', $contactAttributes, $data);

        if($data['Lists']){
            $tmpArray = [];
            foreach ($data['Lists'] as $listID){
                $brevoList = \Brevo\NewsletterRegistration\DataObjects\BrevoList::get()->byID($listID);
                if ($brevoList) {
                    $tmpArray[] = intval($brevoList->ListID);
                }
            }
            $data['Lists'] = $tmpArray;
        }

        $createContact = new CreateDoiContact();
        $createContact->setEmail($data['Email']);
        $createContact->setTemplateId($this->DOITemplateID ?: 2);
        $createContact->setIncludeListIds($data['Lists']);
        $createContact->setAttributes($contactAttributes);
        if($this->SuccessLinkID > 0){
            $createContact->setRedirectionUrl($this->SuccessLink()->AbsoluteLink());
        }

        $this->extend('updateCreateDoiContact', $createContact, $data);

        try {
            // Versuche, den Kontakt zu erstellen
            $apiInstance->createDoiContact($createContact);
            $successMessage = _t('Newsletter.SUCCESS_MESSAGE', 'Erfolgreich angemeldet. Bitte prüfen Sie Ihr Postfach um die Anmeldung zu bestätigen.');
            $form->sessionMessage(strip_tags($successMessage), 'good');
            if($this->OptInHintLinkID > 0){
                return $this->redirect($this->OptInHintLink()->Link());
            }
        } catch (Exception $e) {
            // Prüfe, ob der Fehler darauf hinweist, dass der Kontakt bereits existiert
            if ($e->getCode() == 400 && strpos($e->getMessage(), 'Contact already exist') !== false) {
                try {
                    // Wenn der Kontakt bereits existiert, füge ihn zur Liste hinzu
                    $updateContact = new UpdateContact();
                    $updateContact->setListIds($data['Lists']);
                    $apiInstance->updateContact($data['Email'], $updateContact);

                    $successMessage = _t('Newsletter.UPDATE_MESSAGE', 'Kontakt erfolgreich aktualisiert');
                    $form->sessionMessage(strip_tags($successMessage), 'good');
                } catch (Exception $updateException) {
                    $failureMessage = _t('Newsletter.UPDATE_ERROR', 'Fehler beim Aktualisieren des Kontakts');
                    $form->sessionMessage(strip_tags($failureMessage) . ': ' . $updateException->getMessage(), 'bad');
                }
            } else {
                $failureMessage = _t('Newsletter.ERROR_MESSAGE', 'Fehler bei der Anmeldung');
                $form->sessionMessage(strip_tags($failureMessage) . ': ' . $e->getMessage(), 'bad');
            }
        }

        $this->extend('onAfterNewsletterRegistration', $data, $form);

        return $this->redirectBack();
    }
}