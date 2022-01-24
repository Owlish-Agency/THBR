<?php
    namespace Craft;

    $message = craft()->request->getPost('message');

    if ($message)
    {
        /** The name of the `Mail Route` post param is passed through a
         *  hidden field so that it can be named anything in the CMS
         *  (i.e. Subject)
         */
        $mailRouteKey = craft()->request->getPost('mailRouteKey');
        $mailRoute = $message[$mailRouteKey];
        if ($mailRouteKey && isset($mailRoute))
        {
            $mailRoute = craft()->security->validateData($mailRoute);
            /** Update the $_POST variables with the cleaned up value of the
             *  Mail Route param so our emails are prettier
             */
            $_POST['message'][$mailRouteKey] = $mailRoute;
            /** Create an array from value of `mailRoute` so we can get the
             *  separate the `toEmail` from the subject. The `toEmail` accepts
             *  multiple emails separated by commas.
             *
             *  Should be written in the format:
             *  `email@example.com|Subject`
             */
            $explodedMailRoute = explode('|', $mailRoute);
            if (is_array($explodedMailRoute))
            {
                $toEmail = $explodedMailRoute[0];
            }
            else
            {
                $toEmail = $mailRoute;
            }
        }
        else
        {
            $toEmail = craft()->request->getPost('toEmail');
            $toEmail = craft()->security->validateData($toEmail);
        }

    }

    $successFlashMessage = craft()->request->getPost('notice');
    $successFlashMessage = craft()->security->validateData($successFlashMessage);

    return array(
        'toEmail' => ($toEmail ?: null),
        'prependSubject'      => '',
        'prependSender'       => '',
        'allowAttachments'    => true,
        'honeypotField'       => 'difficulty',
        'successFlashMessage' => ($successFlashMessage ?: null),
    );
