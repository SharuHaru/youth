<?php
/*
|-----------------------------------------------------------------------
| SMTP Configuration
|-----------------------------------------------------------------------
*/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$smtp_config = [
    'host'     => 'smtp.gmail.com',
    'auth'     => true,
    'username' => 'chatgpt4youth@gmail.com',
    'password' => 'zzaq xzfz clrs cuav', // Hide this in Deployment
    'secure'   => PHPMailer::ENCRYPTION_STARTTLS,
    'port'     => 587,
    'debug'    => 0,
    'from'     => [
        'email' => 'chatgpt4youth@gmail.com',
        'name'  => 'Youth'
    ],
    'options'  => [
        'ssl'  => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        ]
    ]
];