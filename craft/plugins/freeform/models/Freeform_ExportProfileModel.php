<?php

namespace Craft;

use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\NoStorageInterface;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Library\Exceptions\FreeformException;

/**
 * Class Freeform_ExportProfileModel
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
class Freeform_ExportProfileModel extends BaseModel
{
    /**
     * @param Form $form
     *
     * @return Freeform_ExportProfileModel
     */
    public static function create(Form $form)
    {
        $model = new Freeform_ExportProfileModel();

        $model->formId = $form->getId();

        return $model;
    }

    /**
     * @return Freeform_FormModel
     */
    public function getFormModel()
    {
        return freeform()->forms->getFormById($this->formId);
    }

    /**
     * @return int
     */
    public function getSubmissionCount()
    {
        $command = $this->buildCommand();

        $command->select('COUNT(s.id)');

        try {
            return $command->queryScalar();
        } catch (\CDbException $e) {
            craft()->userSession->setError($e->getMessage());

            return 'Invalid Query';
        }
    }

    /**
     * @return array
     */
    public function getSubmissionData()
    {
        $command = $this->buildCommand();

        try {
            return $command->queryAll();
        } catch (\CDbException $e) {
            craft()->userSession->setError($e->getMessage());

            return [];
        }
    }

    /**
     * @return \DateTime|null
     */
    public function getDateRangeEnd()
    {
        if (empty($this->dateRange)) {
            return null;
        }

        if (is_numeric($this->dateRange)) {
            $time = new \DateTime("-{$this->dateRange} days");
            $time->setTime(0, 0, 0);

            return $time;
        }

        switch ($this->dateRange) {
            case 'today':
                return (new \DateTime('now'))->setTime(0, 0, 0);

            case 'yesterday':
                return (new \DateTime('-1 day'))->setTime(0, 0, 0);

            default:
                return new DateTime('now');
        }
    }

    /**
     * @return array
     */
    public function getFieldSettings()
    {
        $form = $this->getFormModel()->getForm();

        $storedFieldIds = $fieldSettings = [];
        if (!empty($this->fields)) {
            foreach ($this->fields as $fieldId => $item) {
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

                $fieldSettings[$fieldId] = [
                    'label'   => $label,
                    'checked' => $isChecked,
                ];
            }
        }

        if (empty($fieldSettings)) {
            $fieldSettings['id']          = [
                'label'   => 'ID',
                'checked' => true,
            ];
            $fieldSettings['title']       = [
                'label'   => 'Title',
                'checked' => true,
            ];
            $fieldSettings['dateCreated'] = [
                'label'   => 'Date Created',
                'checked' => true,
            ];
            $fieldSettings['status'] = [
                'label'   => 'Status',
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

            $fieldSettings[$field->getId()] = [
                'label'   => $field->getLabel(),
                'checked' => true,
            ];
        }

        return $fieldSettings;
    }

    /**
     * @return array
     */
    protected function defineAttributes()
    {
        return [
            'id'        => AttributeType::Number,
            'name'      => AttributeType::String,
            'formId'    => AttributeType::Number,
            'limit'     => AttributeType::Number,
            'dateRange' => AttributeType::String,
            'fields'    => AttributeType::Mixed,
            'filters'   => AttributeType::Mixed,
            'statuses'  => [AttributeType::Mixed, 'required' => true, 'default' => '*'],
        ];
    }

    /**
     * @return DbCommand|\CDbCommand
     */
    private function buildCommand()
    {
        $fieldData = $this->getFieldSettings();

        $searchableFields = $labels = [];
        foreach ($fieldData as $fieldId => $data) {
            $isChecked = $data['checked'];

            if (!(bool) $isChecked) {
                continue;
            }

            $fieldName = is_numeric($fieldId) ? Freeform_SubmissionRecord::getFieldColumnName($fieldId) : $fieldId;
            switch ($fieldName) {
                case 'title':
                    $fieldName = 'c.' . $fieldName;
                    break;
                case 'status':
                    $fieldName = 'stat.name AS status';
                    break;
                default:
                    $fieldName = 's.' . $fieldName;
                    break;
            }

            $searchableFields[] = $fieldName;
        }

        $conditions = ['s.formId = :formId'];
        $parameters = ['formId' => $this->formId];

        $dateRangeEnd = $this->getDateRangeEnd();
        if ($dateRangeEnd) {
            $conditions[] = 's.dateCreated >= :dateRangeEnd';
            $parameters['dateRangeEnd'] = $dateRangeEnd->format('Y-m-d H:i:s');
        }

        if ($this->filters) {
            foreach ($this->filters as $filter) {
                $id    = $filter['field'];
                $type  = $filter['type'];
                $value = $filter['value'];

                $fieldId = $id;
                if (is_numeric($id)) {
                    $fieldId = Freeform_SubmissionRecord::getFieldColumnName($id);
                }

                if ($fieldId === 'id') {
                    $fieldId = 's.id';
                }

                if ($fieldId === 'dateCreated') {
                    $fieldId = 's.dateCreated';
                }

                if ($fieldId === 'status') {
                    $fieldId = 'stat.name AS status';
                }

                switch ($type) {
                    case '=':
                        $conditions[] = "$fieldId = :field_$id";
                        break;

                    case '!=':
                        $conditions[] = "$fieldId != :field_$id";
                        break;

                    case 'like':
                        $conditions[] = "$fieldId LIKE :field_$id";
                        break;

                    default:
                        continue 2;
                }

                $parameters["field_$id"] = $value;
            }
        }

        $command = craft()
            ->db
            ->createCommand()
            ->select(implode(',', $searchableFields))
            ->from(Freeform_SubmissionRecord::TABLE . ' s')
            ->join(Freeform_StatusRecord::TABLE . ' stat', 'stat.id = s.statusId')
            ->join('content c', 'c.elementId = s.id')
            ->where(implode(' AND ', $conditions), $parameters);

        if ($this->limit) {
            $command->limit((int) $this->limit);
        }

        if (is_array($this->statuses)) {
            $command->andWhere(['IN', 'statusId', $this->statuses]);
        }

        return $command;
    }
}
