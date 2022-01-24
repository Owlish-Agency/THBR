<?php
/**
 * Freeform for Craft
 *
 * @package       Solspace:Freeform
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2017, Solspace, Inc.
 * @link          https://solspace.com/craft/freeform
 * @license       https://solspace.com/software/license-agreement
 */

namespace Craft;

class Freeform_RecentWidget extends BaseWidget
{
    const DEFAULT_LIMIT = 5;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getSettings()->title;
    }

    /**
     * @return string
     */
    public function getIconPath()
    {
        return CRAFT_PLUGINS_PATH . 'freeform/resources/icon-mask.svg';
    }

    /**
     * @return string
     * @throws \Craft\Exception
     */
    public function getBodyHtml()
    {
        $forms      = $this->getFormService()->getAllForms();
        $formIdList = $this->getSettings()->formIds;
        if ($formIdList === '*') {
            $formIdList = array_keys($forms);
        }

        $criteria         = craft()->elements->getCriteria(Freeform_SubmissionModel::ELEMENT_TYPE);
        $criteria->formId = $formIdList;
        $criteria->order  = 'id DESC';
        $criteria->limit  = (int) $this->getSettings()->limit;

        return craft()->templates->render(
            'freeform/_widgets/recent/body',
            [
                'submissions' => $criteria,
                'settings'    => $this->getSettings(),
            ]
        );
    }

    /**
     * @return string
     */
    public function getSettingsHtml()
    {
        $forms        = $this->getFormService()->getAllForms();
        $formsOptions = [];
        foreach ($forms as $form) {
            $formsOptions[$form->id] = $form->name;
        }

        return craft()->templates->render(
            'freeform/_widgets/recent/settings',
            [
                'settings'         => $this->getSettings(),
                'formOptions'      => $formsOptions,
                'dateRangeOptions' => craft()->freeform_widgets->getDateRanges(),
            ]
        );
    }

    /**
     * @return array
     */
    protected function defineSettings()
    {
        return [
            'title'   => [AttributeType::String, 'default' => Craft::t('Freeform Pro Recent')],
            'formIds' => [AttributeType::Mixed, 'default' => []],
            'limit'   => [AttributeType::Number, 'default' => self::DEFAULT_LIMIT],
        ];
    }

    /**
     * @return Freeform_FormsService
     */
    private function getFormService()
    {
        return craft()->freeform_forms;
    }
}
