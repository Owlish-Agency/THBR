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

class Freeform_RadialChartsWidget extends BaseWidget
{
    protected $colspan = 2;

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

        return craft()->templates->render(
            'freeform/_widgets/radial-charts/settings',
            [
                'settings'         => $this->getSettings(),
                'formOptions'      => $formsOptions,
                'chartTypes'       => [
                    Freeform_WidgetsService::CHART_PIE        => 'Pie',
                    Freeform_WidgetsService::CHART_DONUT      => 'Donut',
                    Freeform_WidgetsService::CHART_POLAR_AREA => 'Polar Area',
                ],
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
            'title'       => [AttributeType::String, 'default' => Craft::t('Freeform Pro Radial Chart')],
            'formIds'     => [AttributeType::Mixed, 'default' => [], 'required' => true],
            'dateRange'   => [AttributeType::String, 'default' => Freeform_WidgetsService::RANGE_LAST_30_DAYS],
            'chartHeight' => [AttributeType::Number, 'default' => 100],
            'chartType'   => [AttributeType::String, 'default' => Freeform_WidgetsService::CHART_DONUT],
        ];
    }

    /**
     * @return array
     */
    private function getChartData()
    {
        list($rangeStart, $rangeEnd) = craft()->freeform_widgets->getRange($this->getSettings()->dateRange);

        $forms = $this->getFormService()->getAllForms();

        $formIdList = $this->getSettings()->formIds;
        if ($formIdList === '*') {
            $formIdList = array_keys($forms);
        }


        $result = craft()
            ->db
            ->createCommand()
            ->select('formId, COUNT(id) as count')
            ->from(Freeform_SubmissionRecord::TABLE)
            ->where(
                'dateCreated >= :rangeStart 
                AND dateCreated <= :rangeEnd 
                AND formId IN (' . implode(',', $formIdList) . ')',
                [
                    'rangeStart' => $rangeStart,
                    'rangeEnd'   => $rangeEnd,
                ]
            )
            ->group('formId')
            ->queryAll();

        $labels = $data = $backgroundColors = $hoverBackgroundColors = $formsWithResults = [];
        foreach ($result as $item) {
            $formId             = $item['formId'];
            $formsWithResults[] = $formId;

            $count = (int) $item['count'];
            $color = craft()->freeform_widgets->getColor($forms[$formId]->color);

            $labels[]                = $forms[$formId]->name;
            $data[]                  = $count;
            $backgroundColors[]      = sprintf('rgba(%s,0.8)', implode(',', $color));
            $hoverBackgroundColors[] = sprintf('rgba(%s,1)', implode(',', $color));
        }

        foreach ($formIdList as $formId) {
            if (in_array($formId, $formsWithResults, false)) {
                continue;
            }

            $color = craft()->freeform_widgets->getColor($forms[$formId]->color);

            $labels[]                = $forms[$formId]->name;
            $data[]                  = 0;
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
}
