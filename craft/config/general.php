<?php

/**
 * General Configuration
 *
 * All of your system's general configuration settings go in here.
 * You can see a list of the default settings in craft/app/etc/config/defaults/general.php
 */

return array(
    // All environments
    '*' => array(
        'omitScriptNameInUrls' => true,
        'usePathInfo' => true,
        'cacheDuration' => false,
        'useEmailAsUsername' => true,
        'generateTransformsBeforePageLoad' => true,
        'loginPath' => 'admin/dashboard',
        'redirectAfterCpLogout'   => '/admin/login', // To redirect CP users after logout
        'redirectAfterSiteLogout' => '/volunteer/volunteer-timesheet', // To redirect site users after logout
        'setPasswordPath' => '/volunteer/resetpassword',
        'setPasswordSuccessPath' => '/volunteer/resetpasswordsuccess',
        'cache' => false,
        'maxSlugIncrement' => 200000,
        'siteUrl' => '//' . $_SERVER['SERVER_NAME'] . '/',
        'environmentVariables' => array(
            'siteName' => 'The Hospice of Baton Rouge'
        ),
        'defaultSearchTermOptions' => array(
            'subLeft' => true,
            'subRight' => true,
        ),
    ),
    '.test' => array(
        'devMode' => true,
        'cache' => false,
        'enableTemplateCaching' => false,
        'siteUrl' => 'http://' . $_SERVER['SERVER_NAME'] . '/',
        'environmentVariables' => array(
            'staticAssetsVersion' => time(),
        ),
    ),
    'hospicebr.org' => array(
        'cache' => false,
        'devMode' => false,
        'enableTemplateCaching' => true,
        'allowAutoUpdates' => false,
        'defaultFolderPermissions' => 0775,
        'defaultFilePermissions' => 0664,
        'siteUrl' => 'https://hospicebr.org/',
        'environmentVariables' => array(
            'staticAssetsVersion' => time(),
        ),
    ),
    'dev.hospicebr.org' => array(
        'devMode' => false,
        'cache' => false,
        'defaultFolderPermissions' => 0775,
        'defaultFilePermissions' => 0664,
        'siteUrl' => 'https://dev.hospicebr.org/',
        'environmentVariables' => array(
            'staticAssetsVersion' => time(),
        ),
    )
);