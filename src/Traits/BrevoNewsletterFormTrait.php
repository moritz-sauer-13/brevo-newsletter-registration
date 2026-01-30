<?php

namespace Brevo\NewsletterRegistration\Traits;

use Brevo\Client\Api\ContactsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\CreateDoiContact;
use Brevo\Client\Model\UpdateContact;
use Brevo\NewsletterRegistration\DataObjects\BrevoList;
use GuzzleHttp\Client;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use Exception;

/**
 * Trait for shared Newsletter form logic (PageController & ElementController)
 * Can be extended via hooks: updateNewsletterRegistrationFields, updateNewsletterRegistrationRequiredFields,
 * updateNewsletterRegistrationData, updateNewsletterRegistrationContactAttributes, updateCreateDoiContact,
 * onAfterNewsletterRegistration
 */
trait BrevoNewsletterFormTrait
{
    private static $doi_template_id = 2;

    private static $include_default_styles = true;

    protected function loadNewsletterStyles(): void
    {
        if ($this->config()->get('include_default_styles')) {
            Requirements::css('moritz-sauer-13/brevo-newsletter-registration:client/dist/styles.css');
        }
    }

    /**
     * Returns the data provider (Page or Element)
     */
    abstract protected function getNewsletterDataProvider();

    public function NewsletterRegistrationForm()
    {
        $this->loadNewsletterStyles();

        $provider = $this->getNewsletterDataProvider();

        if (!$provider->APIKey) {
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

        $brevoLists = $provider->BrevoLists();
        if ($brevoLists->count() > 1) {
            $fields->push(CheckboxSetField::create('Lists', _t('Newsletter.LISTS', 'Listen'), $brevoLists->map('ID', 'Title')));
        } elseif ($brevoLists->count() == 1) {
            $fields->push(HiddenField::create('Lists', 'Lists', $brevoLists->first()->ID));
        }

        $actions = FieldList::create(
            FormAction::create('handleNewsletter', _t('Newsletter.SUBSCRIBE', 'Anmelden'))
                ->addExtraClass('btn-primary-red-focus')
        );

        $requiredFields = RequiredFieldsValidator::create('Email', 'Salutation', 'FirstName', 'LastName');
        if ($provider->BrevoLists()->count() > 1) {
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
        $provider = $this->getNewsletterDataProvider();

        $data = Convert::raw2sql($data);
        $this->extend('updateNewsletterRegistrationData', $data);

        if (!$provider->APIKey) {
            $form->sessionMessage('API Key failed', 'bad');
            return $this->redirectBack();
        }

        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $provider->APIKey);
        $apiInstance = new ContactsApi(new Client(), $config);

        $newSalutation = match ($data['Salutation']) {
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

        if ($data['Lists']) {
            if (!is_array($data['Lists'])) {
                $data['Lists'] = [$data['Lists']];
            }
            $tmpArray = [];
            foreach ($data['Lists'] as $listID) {
                $brevoList = BrevoList::get()->byID($listID);
                if ($brevoList) {
                    $tmpArray[] = intval($brevoList->ListID);
                }
            }
            $data['Lists'] = $tmpArray;
        }

        $createContact = new CreateDoiContact();
        $createContact->setEmail($data['Email']);

        $templateId = $provider->DOITemplateID ?: $this->config()->get('doi_template_id');
        $createContact->setTemplateId($templateId);

        $createContact->setIncludeListIds($data['Lists']);
        $createContact->setAttributes($contactAttributes);
        if ($provider->SuccessLinkID > 0) {
            $createContact->setRedirectionUrl($provider->SuccessLink()->AbsoluteLink());
        }

        $this->extend('updateCreateDoiContact', $createContact, $data);

        try {
            $apiInstance->createDoiContact($createContact);
            $successMessage = _t('Newsletter.SUCCESS_MESSAGE', 'Erfolgreich angemeldet. Bitte prüfen Sie Ihr Postfach um die Anmeldung zu bestätigen.');
            $form->sessionMessage(strip_tags($successMessage), 'good');
            if ($provider->OptInHintLinkID > 0) {
                return $this->redirect($provider->OptInHintLink()->Link());
            }
        } catch (Exception $e) {
            if ($e->getCode() == 400 && strpos($e->getMessage(), 'Contact already exist') !== false) {
                try {
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
