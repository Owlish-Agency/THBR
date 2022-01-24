<?php

namespace Craft;

use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Library\Helpers\PermissionsHelper;

class Freeform_ExportProfilesController extends Freeform_BaseController
{
    public function actionIndex()
    {
        PermissionsHelper::requirePermission(PermissionsHelper::PERMISSION_EXPORT_PROFILES_ACCESS);

        $exportProfileService = $this->getExportProfileService();
        $exportProfiles       = $exportProfileService->getAllProfiles();

        $this->renderTemplate(
            'freeform/export_profiles',
            [
                'exportProfiles' => $exportProfiles,
            ]
        );
    }

    /**
     * @param array $variables
     *
     * @throws HttpException
     */
    public function actionEdit(array $variables = [])
    {
        PermissionsHelper::requirePermission(PermissionsHelper::PERMISSION_EXPORT_PROFILES_MANAGE);

        if (empty($variables['profile'])) {
            $profileId = isset($variables['profileId']) ? $variables['profileId'] : null;
            if ($profileId) {
                $profile = $this->getExportProfileService()->getProfileById($profileId);

                if (!$profile) {
                    throw new HttpException(404, Craft::t('Profile with ID {id} not found'), ['id' => $profileId]);
                }

                $title = $profile->name;
            } else {
                $formHandle = isset($variables['formHandle']) ? $variables['formHandle'] : null;

                $form = $this->getFormService()->getFormByHandle($formHandle);

                if (!$form) {
                    throw new HttpException(
                        404,
                        Craft::t('Form with handle {handle} not found'),
                        ['handle' => $formHandle]
                    );
                }

                $profile = Freeform_ExportProfileModel::create($form->getForm());
                $title   = Craft::t('Create a new Export Profile');
            }
        } else {
            $profile   = $variables['profile'];
            $profileId = $profile->id;
            $title     = $profile->name;
        }

        $title .= " ({$profile->getFormModel()->name})";

        $variables = array_merge(
            $variables,
            [
                'profile'            => $profile,
                'title'              => $title,
                'formOptionList'     => $this->getFormService()->getAllFormNames(),
                'statusOptionList'   => $this->getStatusesService()->getAllStatusNames(),
                'continueEditingUrl' => 'freeform/export-profiles/{id}',
                'crumbs'             => [
                    ['label' => 'Freeform', 'url' => UrlHelper::getUrl('freeform')],
                    ['label' => Craft::t('Export Profiles'), 'url' => UrlHelper::getUrl('freeform/export-profiles')],
                    [
                        'label' => $title,
                        'url'   => UrlHelper::getUrl(
                            'freeform/export-profiles/' . ($profile ? $profileId : 'new')
                        ),
                    ],
                ],
            ]
        );

        craft()->templates->includeJsResource('freeform/js/cp/export-profile.js');

        $this->renderTemplate('freeform/export_profiles/edit', $variables);
    }

    /**
     * @throws Exception
     */
    public function actionSave()
    {
        PermissionsHelper::requirePermission(PermissionsHelper::PERMISSION_EXPORT_PROFILES_MANAGE);

        $post = craft()->request->getPost();

        $formId    = craft()->request->getPost('formId');
        $formModel = $this->getFormService()->getFormById($formId);

        if (!$formModel) {
            throw new Exception(Craft::t('Form with ID {id} not found', ['id' => $formId]));
        }

        $profileId = craft()->request->getPost('profileId');
        $profile   = $this->getNewOrExistingProfile($profileId, $formModel->getForm());

        $profile->setAttributes($post);

        $profile->fields  = $post['fieldSettings'];
        $profile->filters = isset($post['filters']) ? $post['filters'] : [];

        if ($this->getExportProfileService()->save($profile)) {
            // Return JSON response if the request is an AJAX request
            if (craft()->request->isAjaxRequest()) {
                $this->returnJson(['success' => true]);
            } else {
                craft()->userSession->setNotice(Craft::t('Profile saved'));
                craft()->userSession->setFlash(Craft::t('Profile saved'), true);
                $this->redirectToPostedUrl($profile);
            }
        } else {
            // Return JSON response if the request is an AJAX request
            if (craft()->request->isAjaxRequest()) {
                $this->returnJson(['success' => false]);
            } else {
                craft()->userSession->setError(Craft::t('Profile not saved'));

                // Send the event back to the template
                craft()->urlManager->setRouteVariables(
                    [
                        'profile' => $profile,
                        'errors'  => $profile->getErrors(),
                    ]
                );
            }
        }
    }

    /**
     * Deletes a notification
     */
    public function actionDelete()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();
        PermissionsHelper::requirePermission(PermissionsHelper::PERMISSION_EXPORT_PROFILES_MANAGE);

        $profileId = craft()->request->getRequiredPost('id');

        $this->getExportProfileService()->deleteById($profileId);
        $this->returnJson(['success' => true]);
    }

    /**
     * @throws HttpException
     */
    public function actionExport()
    {
        PermissionsHelper::requirePermission(PermissionsHelper::PERMISSION_EXPORT_PROFILES_ACCESS);

        $this->requirePostRequest();

        $profileId = craft()->request->getRequiredPost('profileId');
        $type      = craft()->request->getRequiredPost('type');

        $profile = $this->getExportProfileService()->getProfileById($profileId);

        if (!$profile) {
            throw new HttpException(404, Craft::t('Profile with ID {id} not found'), ['id' => $profileId]);
        }

        $form = $profile->getFormModel()->getForm();
        $data = $profile->getSubmissionData();

        switch ($type) {
            case 'json':
                return $this->getExportProfileService()->exportJson($form, $data);

            case 'xml':
                return $this->getExportProfileService()->exportXml($form, $data);

            case 'text':
                return $this->getExportProfileService()->exportText($form, $data);

            case 'csv':
            default:
                $labels = [];
                foreach ($profile->getFieldSettings() as $id => $item) {
                    if (!$item['checked']) {
                        continue;
                    }
                    $labels[$id] = $item['label'];
                }

                return $this->getExportProfileService()->exportCsv($form, $labels, $data);
        }
    }

    /**
     * @param int  $id
     * @param Form $form
     *
     * @return Freeform_ExportProfileModel
     */
    private function getNewOrExistingProfile($id, Form $form)
    {
        $profile = $this->getExportProfileService()->getProfileById($id);

        if (!$profile) {
            $profile = Freeform_ExportProfileModel::create($form);
        }

        return $profile;
    }
}
