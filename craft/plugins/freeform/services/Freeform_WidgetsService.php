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

class Freeform_WidgetsService extends BaseApplicationComponent
{
    const CHART_LINE       = 'line';
    const CHART_BAR        = 'bar';
    const CHART_DONUT      = 'doughnut';
    const CHART_PIE        = 'pie';
    const CHART_POLAR_AREA = 'polarArea';

    const RANGE_LAST_24_HOURS = 'last_24_hours';
    const RANGE_LAST_7_DAYS   = 'last_7_days';
    const RANGE_LAST_30_DAYS  = 'last_30_days';
    const RANGE_LAST_60_DAYS  = 'last_60_days';
    const RANGE_LAST_90_DAYS  = 'last_90_days';

    /** @var array */
    private static $dateRanges = [
        self::RANGE_LAST_24_HOURS => 'Last 24 hours',
        self::RANGE_LAST_7_DAYS   => 'Last 7 days',
        self::RANGE_LAST_30_DAYS  => 'Last 30 days',
        self::RANGE_LAST_60_DAYS  => 'Last 60 days',
        self::RANGE_LAST_90_DAYS  => 'Last 90 days',
    ];

    /**
     * @return array
     */
    public function getDateRanges()
    {
        $dateRanges = self::$dateRanges;

        array_walk(
            $dateRanges,
            function (&$value) {
                $value = Craft::t($value);
            }
        );

        return self::$dateRanges;
    }

    /**
     * @param string $rangeType
     *
     * @return array - [$dateRangeStart, $dateRangeEnd]
     * @throws Exception
     */
    public function getRange($rangeType)
    {
        if (!array_key_exists($rangeType, self::$dateRanges)) {
            throw new Exception(sprintf('Range type \'%s\' not supported', $rangeType));
        }

        switch ($rangeType) {
            case self::RANGE_LAST_24_HOURS:
                $rangeStart = date('Y-m-d H:i:s', strtotime('24 hours ago'));
                $rangeEnd   = date('Y-m-d H:i:s', time());
                break;

            case self::RANGE_LAST_7_DAYS:
                $rangeStart = date('Y-m-d H:i:s', strtotime('7 days ago'));
                $rangeEnd   = date('Y-m-d H:i:s', time());
                break;

            case self::RANGE_LAST_30_DAYS:
                $rangeStart = date('Y-m-d H:i:s', strtotime('30 days ago'));
                $rangeEnd   = date('Y-m-d H:i:s', time());
                break;

            case self::RANGE_LAST_60_DAYS:
                $rangeStart = date('Y-m-d H:i:s', strtotime('60 days ago'));
                $rangeEnd   = date('Y-m-d H:i:s', time());
                break;

            case self::RANGE_LAST_90_DAYS:
                $rangeStart = date('Y-m-d H:i:s', strtotime('90 days ago'));
                $rangeEnd   = date('Y-m-d H:i:s', time());
                break;

            default:
                $rangeStart = date('Y-m-d', strtotime('-1 month')) . ' 00:00:00';
                $rangeEnd   = date('Y-m-d H:i:s', time());
                break;
        }

        return [$rangeStart, $rangeEnd];
    }

    /**
     * Generates an RGB color based on $id int
     *
     * @param int|string $id
     *
     * @return array
     */
    public function getColor($id)
    {
        if (strpos($id, '#') === 0) {
            $hash = substr($id, 1, 6);
        } else {
            $hash = md5($id); // modify 'color' to get a different palette
        }

        return [
            hexdec(substr($hash, 0, 2)), // r
            hexdec(substr($hash, 2, 2)), // g
            hexdec(substr($hash, 4, 2)), // b
        ];
    }
}
