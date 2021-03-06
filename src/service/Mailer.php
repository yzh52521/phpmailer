<?php

namespace yzh52521\PHPMailer\service;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    public $options = [];
    protected $addresses = [];
    protected $sender = [];
    protected $cc = [];
    protected $bcc = [];
    protected $attachment = [];
    protected $title = '';
    protected $body = '';
    protected $altBody = '';
    protected $mail = null;
    protected $app;

    public function __construct($app = null, $configs = [])
    {
        if (is_array($configs)) {
            $this->options = array_merge($this->options, $configs);
        }
        $this->app  = $app ?: app();
        $this->mail = new PHPMailer(true);
    }


    /**
     * 初始化参数
     */
    public function create(): Mailer
    {
        $this->mail->SMTPDebug  = $this->options['debug'];
        $this->mail->Host       = $this->options['host'];
        $this->mail->CharSet    = $this->options['charset'];
        $this->mail->SMTPAuth   = $this->options['auth'];
        $this->mail->Username   = $this->options['username'];
        $this->mail->Password   = $this->options['password'];
        $this->mail->SMTPSecure = $this->options['security'];
        $this->mail->Port       = $this->options['port'];
        $this->mail->isHTML($this->options['is_html']);
        return $this;
    }

    /**
     * 设置发送人
     * @param $address
     * @param string $senderName
     * @return $this
     */
    public function setSender($address, string $senderName = ''): Mailer
    {
        $this->sender['a'] = $address;
        $this->sender['n'] = $senderName;
        return $this;
    }

    /**
     * 设置收件人回复地址
     * @param string $address
     * @param string $replayName
     * @return $this
     */
    public function setReplyAddress(string $address = '', string $replayName = ''): Mailer
    {
        $this->sender['a'] = $address;
        $this->sender['n'] = $replayName;
        return $this;
    }

    /**
     * 设置单个接收人
     * @param $address
     * @param string $recName
     */
    public function setAddressee($address, string $recName = ''): Mailer
    {
        $this->setAddress('addressee', $address, $recName);
        return $this;
    }

    /**
     * 设置多个接收人
     * @param array $array
     * @return $this
     */
    public function setManyAddressee( array $array): Mailer
    {
        array_walk(
            $array,
            function ($v) {
                $this->setAddressee($v);
            }
        );
        return $this;
    }

    /**
     * 设置抄送单个接收人
     * @param string $address
     * @param string $name
     * @return $this
     */
    public function setCC(string $address = '', string $name = ''): Mailer
    {
        $this->setAddress('cc', $address, $name);
        return $this;
    }

    /**
     * 设置抄送多个接收人
     * @param array $array
     * @return $this
     */
    public function setManyCC( array $array): Mailer
    {
        array_walk(
            $array,
            function ($v) {
                $this->setCC($v);
            }
        );
        return $this;
    }

    /**
     * 设置暗抄送单个接收人
     * @param string $address
     * @param string $name
     * @return $this
     */
    public function setBCC(string $address = '', string $name = ''): Mailer
    {
        $this->setAddress('bcc', $address, $name);
        return $this;
    }

    /**
     * 设置暗抄送多个接收人
     * @param array $array
     */
    public function setManyBCC(array $array): Mailer
    {
        array_walk(
            $array,
            function ($v) {
                $this->setBCC($v);
            }
        );
        return $this;
    }

    /**
     * 设置单个附件
     * @param $filename
     * @param string $name
     * @return $this
     */
    public function setAttachment($filename, string $name = ''): Mailer
    {
        $this->setAddress('attachment', $filename, $name);
        return $this;
    }

    /**
     * 设置多个附件
     * @param array $array
     * @return $this
     */
    public function setManyAttachment(array $array): Mailer
    {
        array_walk(
            $array,
            function ($v) {
                $this->setAttachment($v);
            }
        );
        return $this;
    }

    protected function setAddress($param, $address, $name): void
    {
        $this->{$param}[] = [
            'a' => $address,
            'n' => $name
        ];
    }

    /**
     * 设置邮件内容
     * @param string $title
     * @param string $body
     * @param string $altBody
     * @return $this
     */
    public function setContent(string $title = '', string $body = '', string $altBody = ''): Mailer
    {
        $this->title   = $title;
        $this->body    = $body;
        $this->altBody = $altBody;
        return $this;
    }

    /**
     * 发送
     * @return array|bool
     * @throws Exception
     */
    public function send()
    {
        $this->mail->isSMTP();
        $this->mail->setFrom($this->sender['a'], $this->sender['n']);
        $this->mail->addReplyTo($this->sender['a'], $this->sender['n']);

        $this->addAddress($this->addressee, 'addAddress');
        $this->addAddress($this->bcc, 'addBCC');
        $this->addAddress($this->cc, 'addCC');
        $this->addAddress($this->attachment, 'addAttachment');

        $this->mail->Subject = $this->title;
        $this->mail->Body    = $this->body;
        $this->mail->AltBody = $this->altBody;
        try {
            if ($this->mail->send()) {
                if ($this->options['debug']) {
                    $this->app->log->debug(
                        'Succeed to send email addresses: ' . json_encode($this->addresses) . ' title:' . $this->title
                    );
                }
                return true;
            } else {
                if ($this->options['debug']) {
                    $this->app->log->debug('Fail to send email with error: ' . $this->mail->ErrorInfo);
                }
                return false;
            }
        } catch (Exception $e) {
            if ($this->options['debug']) {
                $this->app->log->debug('Fail to send email with error: ' . $this->mail->ErrorInfo);
            }
            return false;
        }
    }

    protected function addAddress($address, $func): Mailer
    {
        foreach ($address as $item) {
            $item['a'] && $this->mail->{$func}($item['a'], $item['n']);
        }
        return $this;
    }

}
