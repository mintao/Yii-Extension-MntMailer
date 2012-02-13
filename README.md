MntMailer is a swiftmailer wrapper for Yii Framework
====================================================

Configuration
-------------

<pre>
&gt;?php
    // application components
    'components' => array(
        // ...

        // Swift mailer extended template mailer
        'mailer' => array(
            'class'           => 'ext.MntMailer.MntMailer',
            'swiftmailerPath' => 'application.library.swiftmailer',
            'returnPath'      => 'bounce@myapp.com',
            'fromEmail'       => 'noreply@myapp.com',
            'fromName'        => 'My App.com',
            'host'            => 'smtp.googlemail.com',
            'port'            => 587,
            'username'        => 'username@domain.com',
            'password'        => 'youWontGuessIt',
            'encryption'      => 'tls',
        ),
    ),
//..
</pre>
