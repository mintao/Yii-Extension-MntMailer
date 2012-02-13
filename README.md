MntMailer is a swiftmailer wrapper for Yii Framework
====================================================

Configuration
-------------

<pre>
&lt;?php
    // application components
    'components' => array(
        // ...

        // Swift mailer extended template mailer
        'mailer' => array(
            // The path where this extension can be found
            'class'           => 'ext.MntMailer.MntMailer',
            // The path where the swiftmailer installation can be found
            'swiftmailerPath' => 'application.library.swiftmailer',
            // The email address for automatic replies
            'returnPath'      => 'bounce@myapp.com',
            // Your email address
            'fromEmail'       => 'noreply@myapp.com',
            // Your name
            'fromName'        => 'My App.com',
            // SMTP host
            'host'            => 'smtp.googlemail.com',
            // SMTP port
            'port'            => 587,
            // SMTP username
            'username'        => 'username@domain.com',
            // SMTP password
            'password'        => 'youWontGuessIt',
            // SMTP encyption method
            'encryption'      => 'tls',

            // You may use this in development configuration to save a file
            // instead of sending an email
            'sandboxMode'     => true,
            // Path where plain text email will be saved if in sandbox mode
            'sandboxFilePath' => '/tmp',
        ),
    ),
//..
</pre>

Usage in code
-------------

<pre>
&lt;?php
$mailSent = Yii::app()->mailer->send(
    array(
        // Use plain text AND html as email format
        // Possible: 'plain' or 'mixed'
        'mode' => 'mixed',

        // Default layout is layout.php
        //'layout' => 'layout',

        // Email's subject
        'subject' => 'Welcome to ' . Yii::app()->getName(),

        // Template of email
        'template' => 'welcome',

        // The recipient address of your email
        'to' => 'user@example.com',

        // Placeholders in your email template
        'placeholder' => array(
            'title'     => 'Mr',
            'firstName' => 'Florian',
            'lastName'  => 'Fackler',
            'url'       => 'http://mintao.com',
            'color'     => 'blue',
            // ...
        ),

        // You can attach files. For this, simply add the file path to
        // the array "attachments"
        'attachments' => array(
            Yii::getPathOfAlias('images') . DIRECTORY_SEPARATOR . 'icon.png',
        )
    )
//..
</pre>

