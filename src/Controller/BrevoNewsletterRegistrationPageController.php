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

class NewsletterRegistrationPageController extends PageController
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
            TextField::create('LastName', _t('Newsletter.LASTNAME', 'Nachname')),
            DateField::create('Birthday', _t('Newsletter.BIRTHDAY', 'Geburtstag'))
        );

        $fields->push($listsField = CheckboxSetField::create('Lists', _t('Newsletter.LISTS', 'Listen'), $this->BrevoLists()));
        if($this->BrevoLists()->count() == 1){
            $listsField->setValue($this->BrevoLists()->first()->ID);
            $listsField->setDisabled(true);
            $listsField->setTitle(_t('Newsletter.LIST', 'Liste'));
        }

        $actions = FieldList::create(
            FormAction::create('handleNewsletter', _t('Newsletter.SUBSCRIBE', 'Anmelden'))
                ->addExtraClass('btn-primary-red-focus')
        );

        $requiredFields = RequiredFields::create('Email', 'Salutation', 'FirstName', 'LastName', 'Lists');

        $form = Form::create($this, __FUNCTION__, $fields, $actions, $requiredFields);
        $form->setTemplate('NewsletterRegistrationForm');
        $form->enableSpamProtection();

        return $form;
    }

    public function handleNewsletter($data, $form)
    {
        $data = Convert::raw2sql($data);
        if (!$this->APIKey) {
            $form->sessionMessage('API Key failed', 'bad');
            return $this->redirectBack();
        }

        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->APIKey);
        $apiInstance = new ContactsApi(
            new GuzzleHttp\Client(),
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

        if($data['Birthday']){
            $contactAttributes['GEBURTSDATUM'] = $data['Birthday'];
        }

        if($data['Lists']){
            $tmpArray = [];
            foreach ($data['Lists'] as $listID){
                $tmpArray[] = intval($listID);
            }
            $data['Lists'] = $tmpArray;
        }

        $createContact = new CreateDoiContact();
        $createContact->setEmail($data['Email']);
        $createContact->setTemplateId(3);
        $createContact->setIncludeListIds($data['Lists']);
        $createContact->setAttributes($contactAttributes);
        if($this->SuccessLinkID > 0){
            $createContact->setRedirectionUrl($this->SuccessLink()->AbsoluteLink());
        }

        try {
            // Versuche, den Kontakt zu erstellen
            $apiInstance->createDoiContact($createContact);
            $successMessage = 'Erfolgreich angemeldet. Bitte prüfen Sie Ihr Postfach um die Anmeldung zu bestätigen.';
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

                    $successMessage = 'Kontakt erfolgreich aktualisiert';
                    $form->sessionMessage(strip_tags($successMessage), 'good');
                } catch (Exception $updateException) {
                    $failureMessage = 'Fehler beim Aktualisieren des Kontakts';
                    $form->sessionMessage(strip_tags($failureMessage) . ': ' . $updateException->getMessage(), 'bad');
                }
            } else {
                $failureMessage = 'Fehler bei der Anmeldung';
                $form->sessionMessage(strip_tags($failureMessage) . ': ' . $e->getMessage(), 'bad');
            }
        }

        return $this->redirectBack();
    }
}