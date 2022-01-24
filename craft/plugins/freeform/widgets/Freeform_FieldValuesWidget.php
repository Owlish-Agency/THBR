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

use Solspace\Freeform\Library\Composer\Components\AbstractField;

class Freeform_FieldValuesWidget extends BaseWidget
{
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
     */
    public function getBodyHtml()
    {
        craft()->templates->includeJsFile('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.bundle.min.js');
        $data = $this->getChartData();

        return craft()->templates->render(
            'freeform/_widgets/radial-charts/body',
            [
                'chartData' => $data,
                'settings'  => $this->getSettings(),
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

        $fields    = $this->getFieldService()->getAllFields();
        $fieldList = [];
        foreach ($fields as $field) {
            switch ($field->type) {
                case AbstractField::TYPE_TEXTAREA:
                case AbstractField::TYPE_FILE:
                    continue 2;
                    break;
            }

            $fieldList[$field->id] = $field->label;
        }

        return craft()->templates->render(
            'freeform/_widgets/radial-charts/settings',
            [
                'settings'           => $this->getSettings(),
                'formOptions'        => $formsOptions,
                'fieldList'          => $fieldList,
                'dateRangeOptions'   => craft()->freeform_widgets->getDateRanges(),
                'fieldValueSettings' => true,
                'chartTypes'         => [
                    Freeform_WidgetsService::CHART_PIE        => 'Pie',
                    Freeform_WidgetsService::CHART_DONUT      => 'Donut',
                    Freeform_WidgetsService::CHART_POLAR_AREA => 'Polar Area',
                ],
            ]
        );
    }

    /**
     * @return array
     */
    protected function defineSettings()
    {
        return [
            'title'       => [AttributeType::String, 'default' => Craft::t('Freeform Pro Field Values')],
            'formId'      => [AttributeType::Number, 'default' => null, 'required' => true],
            'fieldId'     => [AttributeType::String, 'default' => null, 'required' => true],
            'dateRange'   => [AttributeType::String, 'default' => Freeform_WidgetsService::RANGE_LAST_30_DAYS],
            'chartHeight' => [AttributeType::Number, 'default' => 100],
            'chartType'   => [AttributeType::String, 'default' => Freeform_WidgetsService::CHART_POLAR_AREA],
            'showEmpty'   => [AttributeType::Bool, 'default' => true],
        ];
    }

    /**
     * @return array
     */
    private function getChartData()
    {
        list($rangeStart, $rangeEnd) = craft()->freeform_widgets->getRange($this->getSettings()->dateRange);

        $formId     = $this->getSettings()->formId;
        $fieldId    = $this->getSettings()->fieldId;
        $field      = $this->getFieldService()->getFieldById($fieldId);
        $columnName = Freeform_SubmissionRecord::getFieldColumnName($fieldId);

        $showEmpty = $this->getSettings()->showEmpty;

        $result = craft()
            ->db
            ->createCommand()
            ->select("$columnName as val, COUNT(id) as count")
            ->from(Freeform_SubmissionRecord::TABLE)
            ->where(
                'dateCreated >= :rangeStart 
                AND dateCreated <= :rangeEnd 
                AND formId = :formId',
                [
                    'rangeStart' => $rangeStart,
                    'rangeEnd'   => $rangeEnd,
                    'formId'     => $formId,
                ]
            )
            ->group($columnName)
            ->queryAll();

        $cleanResults = [0 => ['val' => null, 'count' => 0]];
        foreach ($result as $item) {
            $value = $item['val'];
            if (!$value || $value === '[]') {
                $cleanResults[0]['count'] += (int)$item['count'];
            } else {
                $cleanResults[] = $item;
            }
        }

        if (!$showEmpty) {
            unset($cleanResults[0]);
        }

        $labels = $data = $backgroundColors = $hoverBackgroundColors = [];
        foreach ($cleanResults as $item) {
            $columnValue = $item['val'];
            if ($columnValue && in_array(
                    $field->type,
                    [AbstractField::TYPE_CHECKBOX_GROUP, AbstractField::TYPE_EMAIL],
                    true
                )
            ) {
                $columnValue = implode(', ', json_decode($columnValue));
            }

            $count = (int)$item['count'];
            $color = $columnValue ? craft()->freeform_widgets->getColor($columnValue) : [5, 148, 209];

            $labels[]                = $columnValue ?: 'Empty';
            $data[]                  = $count;
            $backgroundColors[]      = sprintf('rgba(%s,0.8)', implode(',', $color));
            $hoverBackgroundColors[] = sprintf('rgba(%s,1)', implode(',', $color));
        }

        return [
            'type'    => $this->getSettings()->chartType,
            'data'    => [
                'labels'   => $labels,
                'datasets' => [
                    [
                        'data'                 => $data,
                        'backgroundColor'      => $backgroundColors,
                        'hoverBackgroundColor' => $hoverBackgroundColors,
                    ],
                ],
            ],
            'options' => [
                'tooltips'   => [
                    'backgroundColor' => 'rgba(250, 250, 250, 0.9)',
                    'titleFontColor'  => '#000',
                    'bodyFontColor'   => '#000',
                    'cornerRadius'    => 3,
                    'xPadding'        => 10,
                    'yPadding'        => 7,
                    'displayColors'   => false,
                ],
                'responsive' => true,
                'legend'     => [
                    'labels' => [
                        'padding'       => 15,
                        'usePointStyle' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return Freeform_FormsService
     */
    private function getFormService()
    {
        return craft()->freeform_forms;
    }

    /**
     * @return Freeform_FieldsService
     */
    private function getFieldService()
    {
        return craft()->freeform_fields;
    }
}
