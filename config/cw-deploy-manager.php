<?php

return [
    // Cloudways Deploy Manager: CW_DM_*

    // Set this to something like: my_name_cloud_deploy_manager
    'app_credentials_username' => env('CW_DM_APP_CREDENTIALS_USERNAME', false),

    'group_alias' => [
        // define any you want here
        // alias(strtolower) => full name

    ],
    'ssh' => [
        // Name of uploaded SSK key and will be visible in Cloudways
        // ex: MyName Dev Laptop
        'key_uploaded_name' => env('CW_DM_SSH_KEY_UPLOADED_NAME', false),

        // Set up a ssh key for Cloudways see: https://support.cloudways.com/en/articles/5120579-why-should-you-set-up-ssh-keys#h_4163feed5d
        // OR https://www.howtogeek.com/762863/how-to-generate-ssh-keys-in-windows-10-and-windows-11/
        'public_key' => env('CW_DM_SSH_PUBLIC_KEY', false),
        'public_key_path' => env('CW_DM_SSH_PUBLIC_KEY_PATH', false),
        'private_key_path' => env('CW_DM_SSH_PRIVATE_KEY_PATH', false),
        // Windows Plink key path as PPK file:
        'private_ppk_key_path' => env('CW_DM_SSH_PRIVATE_PPK_KEY_PATH', false),

        // Windows:
        'use_plink' => env('CW_DM_SSH_USE_PLINK', false),
        'use_temp' => env('CW_DM_SSH_USE_TEMP', false),
    ],
];
