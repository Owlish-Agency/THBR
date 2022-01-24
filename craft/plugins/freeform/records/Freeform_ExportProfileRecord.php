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

/**
 * Class Freeform_ExportProfileRecord
 *
 * @property int    $id
 * @property int    $formId
 * @property string $name
 * @property int    $limit
 * @property string $dateRange
 * @property array  $fields
 * @property array  $filters
 * @property array  $statuses
 */
class Freeform_ExportProfileRecord extends BaseRecord
{
    const TABLE = 'freeform_export_profiles';

    /**
     * @return string
     */
    public function getTableName()
    {
        return self::TABLE;
    }

    /**
     * @return array
     */
    public function defineRelations()
    {
        return [
            'form' => [
                static::BELONGS_TO,
                'Freeform_FormRecord',
                'formId',
                'required' => true,
                'onDelete' => static::CASCADE,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function defineAttributes()
    {
        return [
            'name'      => [AttributeType::String, 'required' => true],
            'limit'     => AttributeType::Number,
            'dateRange' => AttributeType::String,
            'fields'    => [AttributeType::Mixed, 'required' => true],
            'filters'   => AttributeType::Mixed,
            'statuses'  => [AttributeType::Mixed, 'required' => true],
        ];
    }
}
