<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    /** static data */
    private static ?array $config = null;

    /** properties */
    private array  $recipients = [];
    private string $subject    = '';
    private string $body       = '';


    /**
     * Initializes Mailer.
     * @param mixed $recipient
     * @param string $subject
     * @param string $body
     * @throws Exception
     */
    public function __construct(mixed $recipient = '', string $subject = '', string $body = '')
    {
        self::setConfig();

        // initialize recipients
        if (!empty($recipient)) {
            if (is_array($recipient)) {
                $this->setRecipients($recipient);
            }
            else {
                $this->addRecipient($recipient);
            }
        }

        // initialize subject
        if (!empty($subject)) {
            $this->setSubject($subject);
        }

        // initialize body
        if (!empty($body)) {
            $this->setBody($body);
        }
    }


    /**
     * Set email config.
     * @return void
     */
    private static function setConfig(): void
    {
        if (self::$config === null) {
            require __DIR__ . '/../config/app.php';
            require __DIR__ . '/../config/smtp.php';

            if (isset($app_config) && isset($smtp_config)) {
                self::$config = [
                    'app'  => $app_config,
                    'smtp' => $smtp_config
                ];
            }
        }
    }


    /**
     * Returns the email config.
     * @return array|null
     */
    protected function getConfig(): ?array
    {
        return self::$config;
    }


    /**
     * Returns the email config in a static context.
     * @return array|null
     */
    protected static function getConfigStatic(): ?array
    {
        self::setConfig();

        return self::$config;
    }


    /**
     * Gets Mailer recipients.
     * @return array
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }


    /**
     * Gets Mailer subject.
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }


    /**
     * Gets Mailer body.
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }


    /**
     * Gets body using the default template.
     * @return string
     */
    private function getDefaultHTMLBody(): string
    {
        $body     = $this->body;
        $app_name = self::$config['app']['name']     ?? '';
        $app_url  = self::$config['app']['base_url'] ?? '';

        return <<<HTML
        <html lang="en">
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #ffffff; color: #333;">
            <header style="background: #F2F2F2; padding: 2rem 1rem; text-align: center;">
                <div style="display: inline-flex; align-items: center; justify-content: center; gap: 1rem;">                  
                    <svg width="300" height="170" viewBox="0 0 300 170" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="300" height="170" fill="url(#paint0_radial_1755_1355)"/>
                        <path d="M1168.87 298.667L1171.78 309.558C1181.19 347.307 1184.88 376.614 1182.83 397.479C1182.12 407.912 1179.82 419.464 1175.95 432.137C1167.45 456.131 1160.98 471.793 1156.53 479.123C1143.95 500.112 1134.62 513.741 1128.54 520.009C1120.68 529.322 1112.5 537.362 1104.01 544.129C1095.41 553.043 1069.16 567.307 1025.27 586.922L1025.24 403.15L1028.86 394.794C1035.85 381.225 1046.54 369.29 1060.93 358.99C1076.58 347.673 1112.56 327.565 1168.87 298.667ZM963.966 130L963.932 133.887L963.891 157.388C983.845 157.446 1010.47 162.57 1043.77 172.76C1065.18 182.565 1085.44 194.949 1104.55 209.909L963.606 291.707L963.523 331.425L976.02 346.058L963.454 363.35L962.907 627.641L960.227 627.771L971.344 628.308C970.758 514.556 971.388 445.126 973.236 420.016C974.998 394.421 979.591 378.481 987.015 372.198C989.531 368.293 992.071 385.676 994.637 424.346C996.089 494.796 996.799 561.513 996.766 624.496C1036.5 618.544 1065.73 610.281 1084.47 599.708C1101.59 590.394 1119.24 576.412 1137.4 557.761C1161.23 532.164 1178.39 507.976 1188.88 485.197C1201.34 460.234 1208.37 428.822 1209.99 390.962C1210.17 371.767 1208.38 351.666 1204.62 330.659C1201.34 316.365 1192.93 293.074 1179.41 260.783C1111.65 297.73 1071.06 320.458 1057.63 328.97C1037.14 342.153 1022.93 354.688 1015 366.575L1007.26 344.807L1169.1 246.277L1154.04 225.893L991.339 326.095L981.719 310.799L1146.46 212.741C1118.07 186.649 1087.31 165.323 1054.19 148.764C1029.51 140.064 999.435 133.81 963.966 130Z" fill="url(#paint1_linear_1755_1355)"/>
                        <path d="M754.21 296.631L770.369 305.751C807.186 326.107 840.571 346.944 870.525 368.264C886.799 383.282 895.891 395.427 897.799 404.7L898.957 409.26L899.829 588.825C869.402 575.499 851.243 567.145 845.35 563.763C830.571 557.023 811.549 539.867 788.286 512.296C777.511 499.727 765.103 475.015 751.062 438.158C742.839 412.829 738.987 390.707 739.507 371.792C740.355 349.664 745.256 324.61 754.21 296.631ZM956.505 130.207L943.982 131.122C909.06 134.907 877.321 143.588 848.763 157.165C833.188 165.2 808.265 183.725 773.996 212.741L938.734 310.799L929.114 326.095L766.409 225.893L751.356 246.277L913.194 344.807L905.455 366.575C899.931 357.364 888.208 346.632 870.287 334.38C833.188 311.263 790.108 286.731 741.046 260.783C718.953 308.678 708.651 351.259 710.141 388.527C712.608 454.808 737.779 512.649 785.654 562.051C798.134 575.033 818.5 589.423 846.754 605.22C858.549 611.66 884.194 618.086 923.687 624.496C923.525 561.503 924.31 494.162 926.041 422.47C928.474 385.119 930.959 368.334 933.496 372.115C940.964 379.453 945.481 395.011 947.047 418.787C949.005 442.596 949.692 512.437 949.109 628.308L960.226 627.771L957.546 627.641L956.996 362.853L943.817 344.393L956.929 332.137L956.846 292.416L818.65 211.196C863.071 174.523 909.043 156.537 956.564 157.238L956.505 130.207Z" fill="url(#paint2_linear_1755_1355)"/>
                        <path d="M949.373 130.817C952.726 130.423 956.079 130.028 960.009 130.014C960.082 130.014 960.155 130.014 960.228 130.014C964.106 130.014 968.53 130.373 972.954 130.733L968.937 157.628L952.178 156.969L949.373 130.817Z" fill="url(#paint3_linear_1755_1355)"/>
                        <path d="M967.136 289.671L959.934 293.868L953.86 290.777L953.226 335.6L960.936 328.392L967.717 336.335L967.136 289.671Z" fill="url(#paint4_linear_1755_1355)"/>
                        <path d="M967.974 357.131L960.377 367.585L952.922 357.149L949.081 628.327L971.35 628.152L967.974 357.131Z" fill="url(#paint5_linear_1755_1355)"/>
                        <path d="M633.02 693.445L610.739 692.96C608.709 696.058 606.679 699.155 610.636 706.391C614.592 713.627 624.535 725.001 634.478 736.375L656.227 737.142C657.653 734.721 659.079 732.3 655.212 725.018C651.344 717.735 642.182 705.59 633.02 693.445Z" fill="url(#paint6_linear_1755_1355)"/>
                        <path d="M729.606 694.086L707.844 693.387L658.116 764.713L657.513 819.624C660.93 820.707 664.348 821.789 668.337 821.91C668.597 821.918 668.858 821.922 669.122 821.922C672.92 821.922 677.186 821.136 681.452 820.349L681.84 770.936C699.471 747.243 717.102 723.55 725.063 710.742C733.024 697.933 731.315 696.009 729.606 694.086Z" fill="url(#paint7_linear_1755_1355)"/>
                        <path d="M812.82 695.805L813.575 714.001C802.551 717.802 791.527 721.602 785.511 733.32C779.494 745.038 778.487 764.674 783.841 776.972C789.196 789.27 800.913 794.23 812.63 799.19L812.877 821.787C807.348 821.35 801.819 820.914 794.167 817.714C786.515 814.515 776.741 808.553 768.855 797.736C760.969 786.919 754.973 771.248 755.673 755.118C756.373 738.989 763.771 722.402 773.373 711.677C782.975 700.951 794.782 696.086 801.724 694.418C803.866 693.903 805.545 693.693 806.904 693.693C809.948 693.693 811.384 694.749 812.82 695.805Z" fill="url(#paint8_linear_1755_1355)"/>
                        <path d="M827.438 695.724L826.743 713.922C837.78 717.686 848.817 721.449 854.873 733.147C860.928 744.845 862.002 764.477 856.689 776.793C851.375 789.108 839.675 794.108 827.975 799.107L827.803 821.706C833.331 821.25 838.858 820.795 846.499 817.57C854.141 814.345 863.895 808.349 871.745 797.506C879.594 786.663 885.538 770.971 884.783 754.845C884.029 738.718 876.576 722.156 866.938 711.463C857.3 700.77 845.476 695.945 838.529 694.3C836.408 693.798 834.741 693.592 833.39 693.592C830.316 693.592 828.877 694.658 827.438 695.724Z" fill="url(#paint9_linear_1755_1355)"/>
                        <path d="M946.467 777.344L946.199 695.55C942.216 694.083 938.232 692.617 934.289 692.617C934.083 692.617 933.876 692.621 933.67 692.629C929.525 692.795 925.427 694.67 921.33 696.546L921.362 779.283C923.659 785.826 925.956 792.37 929.378 797.938C932.8 803.505 937.347 808.097 942.226 812.121C947.105 816.145 952.316 819.601 956.898 821.015C958.941 821.646 960.859 821.871 962.707 821.871C965.004 821.871 967.194 821.524 969.384 821.176L970.564 800.947C964.844 798.631 959.124 796.316 955.108 792.382C951.092 788.448 948.78 782.896 946.467 777.344Z" fill="url(#paint10_linear_1755_1355)"/>
                        <path d="M1008.43 777.244L1007.92 695.451C1011.94 693.928 1015.96 692.404 1019.96 692.404C1020.11 692.404 1020.27 692.407 1020.43 692.411C1024.57 692.538 1028.69 694.375 1032.8 696.211L1033.56 778.944C1031.32 785.509 1029.09 792.074 1025.72 797.674C1022.35 803.275 1017.85 807.909 1013 811.98C1008.16 816.05 1002.99 819.555 998.418 821.013C996.3 821.688 994.314 821.924 992.398 821.924C990.181 821.924 988.057 821.608 985.934 821.292L984.562 801.075C990.259 798.705 995.957 796.335 999.936 792.364C1003.91 788.392 1006.17 782.818 1008.43 777.244Z" fill="url(#paint11_linear_1755_1355)"/>
                        <path d="M1065.78 714.579L1066.22 696.272C1068.61 694.749 1071 693.225 1074.74 692.631C1076.07 692.421 1077.56 692.327 1079.09 692.327C1081.88 692.327 1084.78 692.642 1086.94 693.139C1090.27 693.908 1091.83 695.11 1093.39 696.313L1093.93 715.342C1088.65 716.601 1083.37 717.859 1078.82 717.859C1078.6 717.859 1078.39 717.856 1078.18 717.85C1073.49 717.723 1069.64 716.151 1065.78 714.579Z" fill="url(#paint12_linear_1755_1355)"/>
                        <path d="M1109.21 821.709L1127.19 822.404L1130.72 818.975L1130.11 716.948L1168.46 717.229L1171.71 713.66L1172.89 696.982L1169.22 693.089L1108.2 693.04L1105.32 695.957L1106.5 817.761L1109.21 821.709Z" fill="url(#paint13_linear_1755_1355)"/>
                        <path d="M1205.38 698.296L1208.79 693.777L1226.52 693.956L1229.68 696.973L1229.79 732.048L1226.13 735.77L1209.8 736.049L1205.02 730.556L1205.38 698.296Z" fill="url(#paint14_linear_1755_1355)"/>
                        <path d="M1287.34 698.27L1292.22 693.662L1307.57 693.867L1311.39 697.23L1311.62 816.374L1306.88 820.715L1291.31 820.579L1287.17 817.389L1287.35 768.012L1229.77 767.936L1229.72 817.385L1225.74 820.597L1208.85 820.729L1204.87 817.117L1205.87 749.076L1210.21 745.151L1286.8 745.026L1287.34 698.27Z" fill="url(#paint15_linear_1755_1355)"/>
                        <defs>
                        <radialGradient id="paint0_radial_1755_1355" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(960 540) rotate(90) scale(540 1706.67)">
                        <stop offset="0.00480769" stop-color="#3A3A3A"/>
                        <stop offset="0.451923" stop-color="#212121"/>
                        <stop offset="0.966346" stop-color="#060606"/>
                        </radialGradient>
                        <linearGradient id="paint1_linear_1755_1355" x1="969.027" y1="159.775" x2="969.027" y2="630.864" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#3F51B5"/>
                        <stop offset="0.509009" stop-color="#F44336"/>
                        <stop offset="1" stop-color="#FFEB3B"/>
                        </linearGradient>
                        <linearGradient id="paint2_linear_1755_1355" x1="969.027" y1="159.775" x2="969.027" y2="630.864" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#3F51B5"/>
                        <stop offset="0.509009" stop-color="#F44336"/>
                        <stop offset="1" stop-color="#FFEB3B"/>
                        </linearGradient>
                        <linearGradient id="paint3_linear_1755_1355" x1="969.027" y1="159.775" x2="969.027" y2="630.865" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#3F51B5"/>
                        <stop offset="0.509009" stop-color="#F44336"/>
                        <stop offset="1" stop-color="#FFEB3B"/>
                        </linearGradient>
                        <linearGradient id="paint4_linear_1755_1355" x1="969.026" y1="159.775" x2="969.026" y2="630.865" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#3F51B5"/>
                        <stop offset="0.509009" stop-color="#F44336"/>
                        <stop offset="1" stop-color="#FFEB3B"/>
                        </linearGradient>
                        <linearGradient id="paint5_linear_1755_1355" x1="969.028" y1="159.775" x2="969.028" y2="630.864" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#3F51B5"/>
                        <stop offset="0.509009" stop-color="#F44336"/>
                        <stop offset="1" stop-color="#FFEB3B"/>
                        </linearGradient>
                        <linearGradient id="paint6_linear_1755_1355" x1="978.679" y1="697.814" x2="978.679" y2="825.943" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#3F51B5"/>
                        <stop offset="0.509009" stop-color="#F44336"/>
                        <stop offset="1" stop-color="#FFEB3B"/>
                        </linearGradient>
                        <linearGradient id="paint7_linear_1755_1355" x1="978.679" y1="697.814" x2="978.679" y2="825.943" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#3F51B5"/>
                        <stop offset="0.509009" stop-color="#F44336"/>
                        <stop offset="1" stop-color="#FFEB3B"/>
                        </linearGradient>
                        <linearGradient id="paint8_linear_1755_1355" x1="978.679" y1="697.814" x2="978.679" y2="825.943" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#3F51B5"/>
                        <stop offset="0.509009" stop-color="#F44336"/>
                        <stop offset="1" stop-color="#FFEB3B"/>
                        </linearGradient>
                        <linearGradient id="paint9_linear_1755_1355" x1="978.679" y1="697.814" x2="978.679" y2="825.943" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#3F51B5"/>
                        <stop offset="0.509009" stop-color="#F44336"/>
                        <stop offset="1" stop-color="#FFEB3B"/>
                        </linearGradient>
                        <linearGradient id="paint10_linear_1755_1355" x1="978.679" y1="697.814" x2="978.679" y2="825.943" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#3F51B5"/>
                        <stop offset="0.509009" stop-color="#F44336"/>
                        <stop offset="1" stop-color="#FFEB3B"/>
                        </linearGradient>
                        <linearGradient id="paint11_linear_1755_1355" x1="978.679" y1="697.814" x2="978.679" y2="825.943" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#3F51B5"/>
                        <stop offset="0.509009" stop-color="#F44336"/>
                        <stop offset="1" stop-color="#FFEB3B"/>
                        </linearGradient>
                        <linearGradient id="paint12_linear_1755_1355" x1="978.679" y1="697.814" x2="978.679" y2="825.943" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#3F51B5"/>
                        <stop offset="0.509009" stop-color="#F44336"/>
                        <stop offset="1" stop-color="#FFEB3B"/>
                        </linearGradient>
                        <linearGradient id="paint13_linear_1755_1355" x1="978.679" y1="697.814" x2="978.679" y2="825.943" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#3F51B5"/>
                        <stop offset="0.509009" stop-color="#F44336"/>
                        <stop offset="1" stop-color="#FFEB3B"/>
                        </linearGradient>
                        <linearGradient id="paint14_linear_1755_1355" x1="978.679" y1="697.814" x2="978.679" y2="825.943" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#3F51B5"/>
                        <stop offset="0.509009" stop-color="#F44336"/>
                        <stop offset="1" stop-color="#FFEB3B"/>
                        </linearGradient>
                        <linearGradient id="paint15_linear_1755_1355" x1="978.679" y1="697.814" x2="978.679" y2="825.943" gradientUnits="userSpaceOnUse">
                        <stop stop-color="#3F51B5"/>
                        <stop offset="0.509009" stop-color="#F44336"/>
                        <stop offset="1" stop-color="#FFEB3B"/>
                        </linearGradient>
                        </defs>
                    </svg>

                    <h1 style="font-size: 1.8rem; margin: 0; color: #333;">{$app_name}</h1>
                </div>
            </header>

            <main style="margin: 0 auto; max-width: 576px; padding: 2rem 1rem 3rem 1rem; line-height: 1.5; background-color: #ffffff;">
                <div style="margin-top: 1rem; font-size: 2rem; color: #333;">{$body}</div>
                <div style="color: gray; margin-top: 1.5rem; border-top: 1px solid #eee; padding-top: 1rem;">—</div>
                <div style="font-weight: bold; margin-top: 0.5rem;">{$app_name} Team</div>
            </main>

            <footer style="color: gray; border-top: 1px solid #eee; margin-top: 1rem; padding: 1rem; text-align: center; font-size: 0.9rem;">
                <p>This is an automated email from <strong>{$app_name}</strong> |
                <a href="{$app_url}" style="color: gray; text-decoration: none;" target="_blank">{$app_url}</a></p>
            </footer>
        </body>
        </html>
        HTML;
    }



    /**
     * Set Mailer recipients.
     * @param array $emails
     * @return void
     * @throws Exception
     */
    public function setRecipients(array $emails): void
    {
        $this->recipients = [];
        foreach ($emails as $email) {
            if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid recipient email address [' . $email . '].');
            }

            $this->recipients[] = $email;
        }
    }


    /**
     * Add Mailer recipient.
     * @param string $email
     * @return void
     * @throws Exception
     */
    public function addRecipient(string $email): void
    {
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid recipient email address [' . $email . '].');
        }

        $this->recipients[] = $email;
    }


    /**
     * Set Mailer subject.
     * @param string $subject
     * @return void
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }


    /**
     * Set Mailer body.
     * @param string $body
     * @return void
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }


    /**
     * Sends the Email.
     * @return bool
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws Exception
     */
    public function send(): bool
    {
        // load smtp config
        $smtp_config = self::$config['smtp'] ?? null;
        if (!$smtp_config) {
            throw new Exception('Missing SMTP Config!');
        }

        // Server Settings
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host        = $smtp_config['host'];
        $mail->SMTPAuth    = $smtp_config['auth'];


        // Use your Gmail Account
        $mail->Username    = $smtp_config['username'];
        $mail->Password    = $smtp_config['password'];
        $mail->SMTPSecure  = $smtp_config['secure'];
        $mail->Port        = $smtp_config['port'];
        
        
        $mail->SMTPDebug   = $smtp_config['debug'];
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => $smtp_config['options']['ssl']['verify_peer'],
                'verify_peer_name'  => $smtp_config['options']['ssl']['verify_peer_name'],
                'allow_self_signed' => $smtp_config['options']['ssl']['allow_self_signed']
            ]
        ];
        
        //Recipients
        $mail->From        = $smtp_config['from']['email'];
        $mail->FromName    = $smtp_config['from']['name'];
        foreach ($this->recipients as $recipient) {
            $mail->addAddress($recipient);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $this->subject;
        $mail->Body    = $this->getDefaultHTMLBody();

        if (!$mail->send()) {
            throw new Exception('Error in sending email: ' . $mail->ErrorInfo);
        }

        return true;
    }
}