<?php
/**
 * Autoloader minimal pour PHPMailer (sans Composer)
 */
spl_autoload_register(function (string $class): void {
    $map = [
        'PHPMailer\\PHPMailer\\PHPMailer'   => __DIR__ . '/phpmailer/phpmailer/src/PHPMailer.php',
        'PHPMailer\\PHPMailer\\SMTP'        => __DIR__ . '/phpmailer/phpmailer/src/SMTP.php',
        'PHPMailer\\PHPMailer\\Exception'   => __DIR__ . '/phpmailer/phpmailer/src/Exception.php',
        'PHPMailer\\PHPMailer\\POP3'        => __DIR__ . '/phpmailer/phpmailer/src/POP3.php',
        'PHPMailer\\PHPMailer\\OAuth'       => __DIR__ . '/phpmailer/phpmailer/src/OAuth.php',
        'PHPMailer\\PHPMailer\\DSNConfigurator' => __DIR__ . '/phpmailer/phpmailer/src/DSNConfigurator.php',
    ];
    if (isset($map[$class])) {
        require $map[$class];
    }
});
