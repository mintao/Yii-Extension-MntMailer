<?php
/**
 * Mintao Yii Extensions
 *
 * PHP Version 5.3
 *
 * @category  Components
 * @package   Mintao_Yii_Extensions
 * @author    Florian Fackler <florian.fackler@mintao.com>
 * @copyright 2011 mintao GmbH & Co. KG
 * @license   BSD. http://www.yiiframework.com/license/
 * @link      http://mintao.com
 */

/**
 * Mintao mailer combines swift mailer and yii using yii's template engine
 *
 * As for webpages, you can define a layout file, which is the frame
 * around your mail template.
 * Html and plain text layouts/templates are in separate folders.
 *
 * @category Mail
 * @package  Mintao_Yii_Extensions
 * @author   Florian Fackler <florian.fackler@mintao.com>
 * @license  BSD. http://www.yiiframework.com/license/
 * @license  Proprietary. All rights reserved
 * @link     http://mintao.com
 */
class MntMailer extends CComponent
{
    /**
     * Path to swiftmailer
     *
     * @var mixed
     * @access public
     */
    public $swiftmailerPath;

    /**
     * Layout for mail. There must be a name and two files with this
     * name <LAYOUT>-html.php and <LAYOUT>-txt.php
     *
     * @var string
     * @access public
     */
    public $layout = 'layout';

    /**
     * @var string Template file to use for mail
     */
    public $template;

    /**
     * Delivery mode. Possible: [plain, mixed]
     * plain: Plain text email,
     * mixed: Html and plain version
     *
     * @var string
     * @access public
     */
    public $mode = 'plain';

    /**
     * Set to true to save a file instead of sending a mail
     * This helps developing without internet connection, or
     * if you don't want to receive thousands of emails during
     * development.
     *
     * @var bool Set to true to save a file
     * @access public
     */
    public $sandboxMode = false;

    /**
     * Use this path to save the local file. Only necessary in
     * sandbox mode
     *
     * @var string
     * @access public
     */
    public $sandboxFilePath = '/tmp';

    /**
     * @var array Assoc array containing placeholders for view
     *
     * e.g. array(
     *     'firstName' => 'Peter',
     *     'lastName' => 'Miller',
     *     ...
     * )
     */
    public $placeholder;

    /**
     * @var array|string Blind carbon copy email address
     */
    public $bcc;

    /**
     * @var string The return path in mail header (email address)
     */
    public $returnPath;

    /**
     * @var string From email part in mail header
     */
    public $fromEmail;

    /**
     * @var string From name part in mail header
     */
    public $fromName;

    /**
     * @var string To part in mail header / Recipient's email address
     */
    public $to;

    /**
     * @var array Array of attachments
     *
     * List of local files to attach to the email
     */
    public $attachments = array();

    /**
     * @var string The mail subject
     */
    public $subject;

    /**
     * @var string ReplyTo email address in mail header
     */
    public $replyTo;

    /**
     * @var string The mail host address
     */
    public $host;

    /**
     * @var integer Which port to use
     */
    public $port;

    /**
     * @var string Username for mail server
     */
    public $username;

    /**
     * @var string Password for mail server
     */
    public $password;

    /**
     * @var string Which encryption method to use for mail server connection
     */
    public $encryption;

    /**
     * If $this->hasErrors() returns true, check errors with
     * $this->getErrors()
     *
     * @var mixed
     * @access private
     */
    private $_errors;

    // Mail content
    private $_plain_body;
    private $_html_body;

    /**
     * Initially called method
     *
     * @access public
     * @return void
     */
    public function init()
    {
        if (is_null($this->swiftmailerPath)) {
            throw new CException('Path to swiftmailer is not set');
        }
        // Initiate swiftmailer autoloader. If this does not work, it'll
        // throw an exception.
        $this->_loadSwiftmailer();

        // Set the path to the mail templates
        Yii::setPathOfAlias(
            'mailview',
            __DIR__ . DIRECTORY_SEPARATOR
            . 'views' . DIRECTORY_SEPARATOR
        );

    }

    /**
     * Configures the module with the specified configuration.
     *
     * @param array $config the configuration array
     */
    public function configure(array $config)
    {
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Reset all errors
     *
     * @access protected
     * @return void
     */
    protected function resetErrors()
    {
        $this->_errors = array();
    }

    /**
     * Check for all mandatory fields
     *
     * @access private
     * @return void
     */
    private function _isReadyToSend()
    {
        if ('' === trim($this->subject)) {
            $this->_errors[] = 'No subject set';
        }
        if ('' === trim($this->fromEmail)) {
            $this->_errors[] = 'No recipient email address given';
        }
        if ('' === trim($this->fromEmail)) {
            $this->_errors[] = 'No recipient email address given';
        }
        if ('' === trim($this->template)) {
            $this->_errors[] = 'No template given';
        }
        // ...
        return $this->hasErrors();
    }

    /**
     * Return all errors
     *
     * @access public
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Returns true if an error occured. Use $this->getErrors()
     * to find out which error
     *
     * @access public
     * @return bool
     */
    public function hasErrors()
    {
        return empty($this->_errors);
    }

    /**
     * Send out the mail
     *
     * @access public
     * @return void
     */
    public function send(array $config = array())
    {
        if (! empty($config)) {
            $this->configure($config);
        }

        if (false === $this->_isReadyToSend()) {
            return $this;
        }

        $this->_plain_body = $this->parseTemplate(
            $this->template,
            'plain',
            $this->placeholder
        );

        $message = Swift_Message::newInstance();
        $message
            ->setSubject($this->subject)
            ->setBody($this->_plain_body, 'text/plain')
            ->setReturnPath($this->returnPath)
            ->setSender(array($this->fromEmail => $this->fromName))
            ->setFrom(array($this->fromEmail => $this->fromName))
            ->setReplyTo($this->replyTo)
            ->setTo($this->to)
            ->setBcc($this->bcc);

        // In sandbox mode just save the plain text version as file
        if (true === $this->sandboxMode) {
            return $this->_saveAsFile();
        }

        if ('mixed' === $this->mode) {
            $this->_html_body = $this->parseTemplate(
                $this->template,
                'html',
                $this->placeholder
            );
            // Any references to local assets?
            $this->_inlinifiyAssets($message);
            $message->addPart($this->_plain_body, 'text/plain')
                ->setBody($this->_html_body, 'text/html');
        }

        if (! empty($this->attachments)) {
            foreach ((array)$this->attachments as $attachmentPath) {
                $mime = CFileHelper::getMimeTypeByExtension($attachmentPath);
                $attachment = Swift_Attachment::fromPath($attachmentPath, $mime);
                $message->attach($attachment);
            }
        }

        $transport = Swift_SmtpTransport::newInstance($this->host, $this->port)
            ->setUsername($this->username)
            ->setPassword($this->password);

        if (! empty($this->encryption)) {
            $transport->setEncryption($this->encryption);
        }

        $mailer = Swift_Mailer::newInstance($transport);
        if (! $mailer->send($message, $fail)) {
            $this->_errors[] = $fail;
            Yii::log(
                CVarDumper::dumpAsString($fail),
                CLogger::LEVEL_ERROR,
                __CLASS__ . '::' . __FUNCTION__
            );
        };
        return $this;
    }

    /**
     * Parse a mail template
     *
     * @param string $sourceFile File name without extension
     * @param string $mode       plain or html
     * @param array $placeholer  Assoc array with template placeholder values
     * @access public
     * @return void
     */
    public function parseTemplate(
        $sourceFile,
        $mode = 'plain',
        array $data = array()
    )
    {
        if (! in_array($mode, array('html', 'plain'))) {
            throw new CException(
                'Only html or plain is allowed for mode'
            );
        }
        $controller = new CExtController(__CLASS__);
        $controller->layout = "mailview.$mode.{$this->layout}";
        $content = $controller->render("mailview.$mode.$sourceFile", $data, true);
        if ('html' === $mode) {
            $content = preg_replace('@<!--.*?-->@s', '', $content);
            $content = preg_replace('@/\*.*?\*/@s', '', $content);
            $content = preg_replace('@\s{2,}@s', ' ', $content);
        }
        return $content;
    }

    /**
     * Load swiftmailer lib
     *
     * @access public
     * @return void
     */
    private function _loadSwiftmailer()
    {
        Yii::import($this->swiftmailerPath . '.lib.classes.Swift', true);
        Yii::registerAutoloader(array('Swift', 'autoload'));
        Yii::import($this->swiftmailerPath . '.lib.swift_init', true);
        if (! class_exists('Swift_Message')) {
            throw new CException('Swiftmailer not installed');
        }
    }

    /**
     * Find images to replace them with inline version
     *
     * @access private
     * @return void
     */
    private function _inlinifiyAssets(Swift_Message $message)
    {
        preg_match_all('@(?:src|background)="([^"]+)"@', $this->_html_body, $match);

        if (! isset($match[1])) {
            return;
        }

        $matches = array_unique($match[1]);
        unset($match);
        $localFilePath = Yii::app()->getBasePath() . '/../public/';
        $localFilePath = Yii::getPathOfAlias('webroot');

        foreach($matches as $file) {
            if (0 !== mb_strpos($file, '/', 0, Yii::app()->charset)) {
                echo "Not a local file";
                continue;
            }
            $localFile = $localFilePath . $file;
            echo "Local file: $localFile .... ";
            if (file_exists($localFile)) {
                $cid = $message->embed(Swift_Image::fromPath($localFile));
                $this->_html_body = str_replace($file, $cid, $this->_html_body);
            }
        }
    }

    /**
     * Save the plain text body as file
     *
     * @access private
     * @return MntMailer
     */
    private function _saveAsFile()
    {
        // Check if temp folder exists and is writable
        if (! is_dir($this->sandboxFilePath)) {
            throw new CException(
                'Sandbox path is not a directory'
            );
        }
        if (! is_writable($this->sandboxFilePath)) {
            throw new CException(
                'Sandbox path is not writable'
            );
        }
        $filePrefix = str_replace(
            ' ',
            '-',
            mb_strtolower($this->subject, Yii::app()->charset)
        );
        $file = $this->sandboxFilePath . DIRECTORY_SEPARATOR . $filePrefix;
        $fh = fopen($file, 'w');
        fwrite($fh, $this->_plain_body);
        fclose($fh);
        return $this;
    }
}


