<?php

namespace Craft;

use League\Csv\Writer;
use SebastianBergmann\Exporter\Exporter;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\NoStorageInterface;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Library\DataExport\ExportDataCSV;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Library\Helpers\PermissionsHelper;

class Freeform_ExportController extends Freeform_BaseController
{
    /**
     * @return string
     */
    public function actionExportDialogue()
    {
        $this->requireAjaxRequest();

        $formId = craft()->request->getParam('formId', null);

        $allowedFormIds = $this->getSubmissionService()->getAllowedSubmissionFormIds();

        /** @var Form[] $forms */
        $forms = [];

        $fields     = [];
        $formModels = $this->getFormService()->getAllForms();
        foreach ($formModels as $form) {
            if (null !== $allowedFormIds) {
                if (!in_array($form->id, $allowedFormIds, false)) {
                    continue;
                }
            }

            $forms[$form->id] = $form->getForm();
            foreach ($form->getForm()->getLayout()->getFields() as $field) {
                if ($field instanceof NoStorageInterface || !$field->getId()) {
                    continue;
                }

                $fields[$field->getId()] = $field;
            }
        }

        $firstForm = reset($forms);

        $userId = craft()->userSession->getUser()->id;

        /** @var Freeform_ExportSettingRecord $settingRecord */
        $settingRecord = Freeform_ExportSettingRecord::model()->findByAttributes(
            [
                'userId' => $userId,
            ]
        );

        craft()->templates->includeJsResource('freeform/js/cp/export.js');

        $setting = [];
        foreach ($forms as $form) {
            $storedFieldIds = $fieldSetting = [];

            if ($settingRecord && isset($settingRecord->setting[$form->getId()])) {
                foreach ($settingRecord->setting[$form->getId()] as $fieldId => $item) {
                    $label     = $item['label'];
                    $isChecked = (bool) $item['checked'];

                    if (is_numeric($fieldId)) {
                        try {
                            $field = $form->getLayout()->getFieldById($fieldId);
                            $label = $field->getLabel();

                            $storedFieldIds[] = $field->getId();
                        } catch (FreeformException $e) {
                            continue;
                        }
                    }

                    $fieldSetting[$fieldId] = [
                        'label'   => $label,
                        'checked' => $isChecked,
                    ];
                }
            }

            if (empty($fieldSetting)) {
                $fieldSetting['id']          = [
                    'label'   => 'ID',
                    'checked' => true,
                ];
                $fieldSetting['title']       = [
                    'label'   => 'Title',
                    'checked' => true,
                ];
                $fieldSetting['dateCreated'] = [
                    'label'   => 'Date Created',
                    'checked' => true,
                ];
            }

            foreach ($form->getLayout()->getFields() as $field) {
                if (
                    in_array($field->getId(), $storedFieldIds, true) ||
                    $field instanceof NoStorageInterface ||
                    !$field->getId()
                ) {
                    continue;
                }

                $fieldSetting[$field->getId()] = [
                    'label'   => $field->getLabel(),
                    'checked' => true,
                ];
            }

            $formSetting['form']   = $form;
            $formSetting['fields'] = $fieldSetting;

            $setting[] = $formSetting;
        }

        $selectedFormId = null;
        if ($formId && isset($forms[$formId])) {
            $selectedFormId = $formId;
        } else if ($firstForm) {
            $selectedFormId = $firstForm->getId();
        }

        $this->renderTemplate(
            'freeform/_components/modals/export_csv',
            [
                'setting'        => $setting,
                'forms'          => $forms,
                'fields'         => $fields,
                'selectedFormId' => $selectedFormId,
            ]
        );
    }

    /**
     *
     */
    public function actionIndex()
    {
        $this->requirePostRequest();
        PermissionsHelper::requirePermission(PermissionsHelper::PERMISSION_SUBMISSIONS_ACCESS);

        $settings = $this->getExportSettings();

        $formId       = craft()->request->getRequiredPost('form_id');
        $exportType   = craft()->request->getRequiredPost('export_type');
        $exportFields = craft()->request->getRequiredPost('export_fields');

        $formModel = $this->getFormService()->getFormById($formId);
        if (!$formModel) {
            return;
        }

        PermissionsHelper::requirePermission(
            PermissionsHelper::prepareNestedPermission(
                PermissionsHelper::PERMISSION_SUBMISSIONS_MANAGE,
                $formId
            )
        );

        $form      = $formModel->getForm();
        $fieldData = $exportFields[$form->getId()];

        $settings->setting = $exportFields;
        $settings->save();

        $searchableFields = $labels = [];
        foreach ($fieldData as $fieldId => $data) {
            $label     = $data['label'];
            $isChecked = $data['checked'];

            if (!(bool) $isChecked) {
                continue;
            }

            $labels[$fieldId] = $label;

            $fieldName = is_numeric($fieldId) ? Freeform_SubmissionRecord::getFieldColumnName($fieldId) : $fieldId;
            $fieldName = $fieldName === 'title' ? 'c.' . $fieldName : 's.' . $fieldName;

            $searchableFields[] = $fieldName;
        }

        $data = craft()
            ->db
            ->createCommand()
            ->select(implode(',', $searchableFields))
            ->join('content c', 'c.elementId = s.id')
            ->from(Freeform_SubmissionRecord::TABLE . ' s')
            ->where(
                [
                    'formId' => $form->getId(),
                ]
            )
            ->queryAll();

        switch ($exportType) {
            case 'json':
                return $this->getExportProfileService()->exportJson($form, $data);

            case 'xml':
                return $this->getExportProfileService()->exportXml($form, $data);

            case 'text':
                return $this->getExportProfileService()->exportText($form, $data);

            case 'csv':
            default:
                return $this->getExportProfileService()->exportCsv($form, $labels, $data);
        }
    }

    /**
     * @return Freeform_ExportSettingRecord
     */
    private function getExportSettings()
    {
        $userId   = craft()->userSession->getUser()->id;
        $settings = Freeform_ExportSettingRecord::model()->findByAttributes(
            [
                'userId' => $userId,
            ]
        );

        if (!$settings) {
            $settings = Freeform_ExportSettingRecord::create($userId);
        }

        return $settings;
    }
}
