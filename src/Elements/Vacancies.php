<?php

namespace LumturoNet\ContaoPersonioBundle\Elements;

use Codefog\HasteBundle\Form\Form;
use Contao\BackendTemplate;
use Contao\Config;
use Contao\ContentElement;
use Contao\Controller;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\PageModel;
use Contao\System;
use InvalidArgumentException;
use LumturoNet\ContaoPersonioBundle\Helpers;
use LumturoNet\ContaoPersonioBundle\Traits\Reader;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class Vacancies extends ContentElement
{
    use Reader;

    private $intVacancyId  = null;
    protected $strTemplate = 'lt_personio_vacancies';

    public function __construct($objContainer, $strColumn)
    {
        parent::__construct($objContainer, $strColumn);
    }

    public function generate()
    {
        $this->strWsUrl = \Config::get('personio_webservice_url');

        if (TL_MODE === 'BE') {
            $this->Template           = new BackendTemplate('be_wildcard');
            $this->Template->wildcard = '### Übersichtsseite + Detail zu Stellenangebot ###';

            return $this->Template->parse();
        }

        // Set the item from the auto_item parameter
        if (!isset($_GET['item']) && Config::get('useAutoItem') && isset($_GET['auto_item'])) {
            preg_match('/(\d{6,})/', Input::get('auto_item'), $matches);
            $this->intVacancyId = $matches[0] ?? null;

            Input::setGet('item', Input::get('auto_item'));
        }

        return parent::generate();
    }

    public function compile()
    {
        if (filter_var($this->strWsUrl, FILTER_VALIDATE_URL) === false) {
            $this->Template = new FrontendTemplate('lt_personio_error');
            return;
        }

        if (!is_null($this->intVacancyId) && is_numeric($this->intVacancyId)) {

            $arrVacancy = $this->getVacancyById(intval($this->intVacancyId));

            $GLOBALS['personio']['ogContent'] = $arrVacancy;

            $templateData = $this->Template->getData();

            $this->Template          = new FrontendTemplate('lt_personio_vacancy');
            $this->Template->setData($templateData);
            $this->Template->vacancy = $arrVacancy;

            if ($arrVacancy === false) {
                $this->Template->connectionError = true;

                return;
            }

            $form = new Form('personio-recruiting-' . $this->intVacancyId, 'POST');

            $form->addFormField('error', [
                'inputType' => 'explanation',
                'eval' => ['text' => '']
            ]);

            $defaultFields = ['first_name', 'last_name', 'email', 'message'];
            foreach ($defaultFields as $defaultField) {
                $form->addFormField($defaultField, [
                    'label' => $GLOBALS['TL_LANG']['MSC']['personio_form_'.$defaultField],
                    // TODO: Should be config
                    'inputType' => ($defaultField === 'message' ? 'textarea' : 'text'),
                    'eval' => [ 'mandatory' => (in_array($defaultField, ['first_name', 'last_name', 'email']) ? true : false) ]
                ]);
            }

            try {
                $formConfig = System::getContainer()->getParameter('contao_personio.recruiting_form');
            } catch (InvalidArgumentException $objException) {
                $formConfig = null;
            }

            if ($formConfig) {
                foreach ($formConfig['system_fields'] as $systemField) {
                    $inputType = 'text';
                    $options = null;
                    // TODO: Should be config
                    switch ($systemField) {
                        case 'birthday':
                            $inputType = 'date';
                            break;
                        case 'gender':
                            $inputType = 'select';
                            $options = ['male' => 'Männlich', 'female' => 'Weiblich', 'diverse' => 'Divers', 'undefined' => 'Unbestimmt'];
                            break;
                    }
                    $form->addFormField($systemField, [
                        'label' => $GLOBALS['TL_LANG']['MSC']['personio_form_'.$systemField],
                        'inputType' => $inputType,
                        'options' => $options
                    ]);
                }
                if (isset($formConfig['custom_fields']) && is_array($formConfig['custom_fields']) && count($formConfig['custom_fields']) > 0) {
                    foreach ($formConfig['custom_fields'] as $customField) {
                        $form->addFormField($customField['attribute_id'], $customField['field_config']);
                    }
                }
                if (isset($formConfig['file_fields']) && is_array($formConfig['file_fields']) && count($formConfig['file_fields']) > 0) {
                    foreach ($formConfig['file_fields'] as $fileField) {
                        $form->addFormField($fileField, [
                            'label' => $GLOBALS['TL_LANG']['MSC']['personio_form_'.$fileField],
                            'inputType' => 'upload',
                            'eval' => [ 'mandatory' => ($fileField === 'cv'), 'extensions' => 'jpg,jpeg,png,tif,tiff,pdf,doc,docx' ]
                        ]);
                    }
                }
            }

            $form->addSubmitFormField('Absenden');

            if ($form->validate()) {
                try {
                    $token = System::getContainer()->getParameter('contao_personio.recruiting_api_token');
                } catch (InvalidArgumentException $objException) {
                    $token = null;
                }

                try {
                    $companyId = System::getContainer()->getParameter('contao_personio.recruiting_company_id');
                } catch (InvalidArgumentException $objException) {
                    $companyId = null;
                }

                $client = HttpClient::create();

                $formData = $form->fetchAll();
                $requestData = [
                    'phase' => [
                        'type' => 'system',
                        'id' => 'unassigned'
                    ],
                    'job_position_id' => $this->intVacancyId,
                    'attributes' => [],
                    'files' => []
                ];
                foreach ($defaultFields as $defaultField) {
                    if (isset($formData[$defaultField]) && !empty($formData[$defaultField])) {
                        $requestData[$defaultField] = $formData[$defaultField];
                    }
                }
                if ($formConfig) {
                    foreach ($formConfig['system_fields'] as $systemField) {
                        if (isset($formData[$systemField]) && !empty($formData[$systemField])) {
                            $requestData['attributes'][] = ['id' => $systemField, 'value' => $formData[$systemField]];
                        }
                    }
                    // TODO: Merge with system_fields if there are custom_fields
                    if (isset($formConfig['custom_fields']) && is_array($formConfig['custom_fields']) && count($formConfig['custom_fields']) > 0) {
                        foreach ($formConfig['custom_fields'] as $customField) {
                            if (isset($formData[$customField['attribute_id']]) && !empty($formData[$customField['attribute_id']])) {
                                $requestData['attributes'][] = [
                                    'id' => $customField['attribute_id'],
                                    'value' => $formData[$customField['attribute_id']]
                                ];
                            }
                        }
                    }
                    if (isset($formConfig['file_fields']) && is_array($formConfig['file_fields']) && count($formConfig['file_fields']) > 0) {
                        foreach ($formConfig['file_fields'] as $fileField) {
                            if (isset($_SESSION['FILES'][$fileField]) && !empty($_SESSION['FILES'][$fileField])) {
                                $fileFieldWrapper = [
                                    'file' => DataPart::fromPath(
                                        $_SESSION['FILES'][$fileField]['tmp_name'],
                                        $_SESSION['FILES'][$fileField]['name'],
                                        $_SESSION['FILES'][$fileField]['type']
                                    )
                                ];
                                $fileFormData = new FormDataPart($fileFieldWrapper);
                                $fileRequestConfig = [
                                    'headers' => $fileFormData->getPreparedHeaders()->toArray(),
                                    'body' => $fileFormData->bodyToIterable()
                                ];
                                $fileRequestConfig['headers']['X-Company-ID'] = $companyId;
                                $fileRequestConfig['headers']['accept'] = 'application/json';
                                $fileRequestConfig['headers']['authorization'] = 'Bearer ' . $token;
                                $fileResponse = $client->request('POST', 'https://api.personio.de/v1/recruiting/applications/documents', $fileRequestConfig);
                                $fileStatusCode = $fileResponse->getStatusCode();
                                if ($fileStatusCode < 300) {
                                    $fileResponseContent = $fileResponse->toArray(false);
                                    $requestData['files'][] = [
                                        'category' => $fileField,
                                        'uuid' => $fileResponseContent['uuid'],
                                        'original_filename' => $fileResponseContent['original_filename']
                                    ];
                                }
                            }
                        }
                    }
                }

                $response = $client->request('POST', 'https://api.personio.de/v1/recruiting/applications', [
                    'headers' => [
                        'X-Company-ID' => $companyId,
                        'accept' => 'application/json',
                        'authorization' => 'Bearer ' . $token,
                        'content-type' => 'application/json'
                    ],
                    'body' => json_encode($requestData)
                ]);
                $status = $response->getStatusCode();
                if ($status >= 300) {
                    $form->getWidget('error')->text = '<p class="error">Bei der Übertragung ist ein Fehler aufgetreten.</p>';
                    // TODO: Show different error messages depending on error type
                    // TODO: Log errors
                    //$responseContent = $response->toArray(false);
                }
                else {
                    if ($this->personio_jumpTo) {
                        $jumpTo = PageModel::findByPk($this->personio_jumpTo);
                        throw new RedirectResponseException('/'.$jumpTo->getFrontendUrl());
                    }
                }
            }

            $this->Template->form = $form->generate();
        } else {
            $strDetailpage = PageModel::findById($this->personio_vacancy_detailpage)->alias;
            $strDetailpage = $strDetailpage ?? Controller::replaceInsertTags('{{page::alias}}');

            $arrVacancies = [];

            if ($this->personio_company) {
                $arrVacancies = $this->getVacanciesByCompany($this->personio_company);
            }
            elseif ($this->personio_recruiting_category) {
                $arrVacancies = $this->getVacanciesByRecruitingCategory($this->personio_recruiting_category);
            }
            else {
                $arrVacancies = $this->getVacancies();
            }

            if ($arrVacancies === false) {
                $this->Template->connectionError = true;

                return;
            }

            try {
                $strSuffix = System::getContainer()->getParameter('contao.url_suffix');
            } catch (InvalidArgumentException $objException) {
                $strSuffix = '';
            }
            foreach ($arrVacancies as $index => $arrVacancy) {
                $arrVacancies[$index]['detailpage'] = $strDetailpage . '/' . Helpers::slug($arrVacancy['name']) . '-' . $arrVacancy['id'] . $strSuffix;//'.html';
            }

            $this->Template->vacancies = $arrVacancies;
        }
    }
}
