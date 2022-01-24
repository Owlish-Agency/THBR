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

class Freeform_LinearChartsWidget extends BaseWidget
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

        switch ($this->getSettings()->dateRange) {
            case Freeform_WidgetsService::RANGE_LAST_7_DAYS:
                $incrementSkip = 1;
                break;

            case Freeform_WidgetsService::RANGE_LAST_30_DAYS:
                $incrementSkip = 3;
                break;

            case Freeform_WidgetsService::RANGE_LAST_60_DAYS:
                $incrementSkip = 6;
                break;

            case Freeform_WidgetsService::RANGE_LAST_90_DAYS:
                $incrementSkip = 10;
                break;

            case Freeform_WidgetsService::RANGE_LAST_24_HOURS:
            default:
                $incrementSkip = 1;
                break;
        }

        return craft()->templates->render(
            'freeform/_widgets/linear-charts/body',
            [
                'chartData'     => $data,
                'settings'      => $this->getSettings(),
                'incrementSkip' => $incrementSkip,
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
            'freeform/_widgets/linear-charts/settings',
            [
                'settings'         => $this->getSettings(),
                'formOptions'      => $formsOptions,
                'dateRangeOptions' => craft()->freeform_widgets->getDateRanges(),
                'chartTypes'    => [
                    Freeform_WidgetsService::CHART_LINE => 'Line',
                    Freeform_WidgetsService::CHART_BAR  => 'Bar',
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
            'title'       => [AttributeType::String, 'default' => Craft::t('Freeform Pro Linear Chart')],
            'formIds'     => [AttributeType::Mixed, 'default' => [], 'required' => true],
            'aggregate'   => [AttributeType::Bool, 'default' => false],
            'dateRange'   => [AttributeType::String, 'default' => Freeform_WidgetsService::RANGE_LAST_30_DAYS],
            'chartHeight' => [AttributeType::Number, 'default' => 50],
            'chartType'   => [AttributeType::String, 'default' => Freeform_WidgetsService::CHART_LINE],
        ];
    }

    /**
     * @return array
     * @throws \Craft\Exception
     */
    private function getChartData()
    {
        list($rangeStart, $rangeEnd) = craft()->freeform_widgets->getRange($this->getSettings()->dateRange);
        $diff = date_diff(new \DateTime($rangeStart), new \DateTime($rangeEnd));

        $labels      = $dates = [];
        $dateContext = new \DateTime($rangeStart);
        for ($i = 0; $i <= $diff->days; $i++) {
            $labels[] = $dateContext->format('M j');
            $dates[]  = $dateContext->format('Y-m-d');
            $dateContext->add(new \DateInterval('P1D'));
        }

        $forms = $this->getFormService()->getAllForms();

        $aggregateData = $this->getSettings()->aggregate;
        $formIdList    = $this->getSettings()->formIds;
        if ($formIdList === '*') {
            $formIdList = array_keys($forms);
        }


        $formData = [];
        foreach ($formIdList as $formId) {
            if (null !== $formId && !isset($forms[$formId])) {
                continue;
            }

            $where       = 'dateCreated >= :rangeStart AND dateCreated <= :rangeEnd';
            $whereParams = [
                'rangeStart' => $rangeStart,
                'rangeEnd'   => $rangeEnd,
            ];

            $form = null;
            if ($aggregateData) {
                $where .= ' AND formId IN (' . implode(',', $formIdList) . ')';
            } else {
                $form = $forms[$formId];
                $where .= ' AND formId = :formId';
                $whereParams['formId'] = $form->id;
            }


            $result = craft()
                ->db
                ->createCommand()
                ->select('DATE(dateCreated) as dt, COUNT(id) as count')
                ->from(Freeform_SubmissionRecord::TABLE)
                ->where($where, $whereParams)
                ->group('dt')
                ->queryAll();

            $data = [];
            foreach ($dates as $date) {
                $data[$date] = 0;
            }

            foreach ($result as $item) {
                $data[$item['dt']] = (int)$item['count'];
            }

            if ($form) {
                $color = craft()->freeform_widgets->getColor($form->color);
            } else {
                $color = [5, 148, 209];
            }

            $formData[] = [
                'label'                => $form ? $form->name : 'Submissions',
                'borderColor'          => sprintf('rgba(%s,1)', implode(',', $color)),
                'backgroundColor'      => sprintf('rgba(%s,1)', implode(',', $color)),
                'pointRadius'          => 3,
                'pointBackgroundColor' => sprintf('rgba(%s,1)', implode(',', $color)),
                'lineTension'          => 0.2,
                'fill'                 => false,
                'data'                 => array_values($data),
            ];

            if ($aggregateData) {
                break;
            }
        }

        $chartType = $this->getSettings()->chartType;

        return [
            'type'    => $chartType,
            'data'    => [
                'labels'   => $labels,
                'datasets' => $formData,
            ],
            'options' => [
                'tooltips'   => [
                    'backgroundColor' => 'rgba(250, 250, 250, 0.9)',
                    'titleFontColor'  => '#000',
                    'bodyFontColor'   => '#000',
                    'cornerRadius'    => 4,
                    'xPadding'        => 10,
                    'yPadding'        => 7,
                    'displayColors'   => false,
                ],
                'responsive' => true,
                'legend'     => [
                    'display' => !$this->getSettings()->aggregate,
                    'labels'  => [
                        'padding' => 20,
                        'usePointStyle' => true,
                    ],
                ],
                'scales'     => [
                    'yAxes' => [
                        [
                            'stacked' => $chartType === 'bar' ? true : null,
                            'beginAtZero' => true,
                        ],
                    ],
                    'xAxes' => [
                        [
                            'stacked' => $chartType === 'bar' ? true : null,
                            'gridLines' => [
                                'display' => false,
                            ],
                        ],
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
