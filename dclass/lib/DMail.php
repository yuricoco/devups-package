<?php
// user \dclass\devups\model\Model;
use PHPMailer\PHPMailer\PHPMailer;

class DMail
{

    public $title = 'Reader3ag : ';
    public $view;
    public $object;
    public $content;
    public $lang;
    public $receivers = [];
    public $receiver_cc = [];
    public $attachments = [];
    /**
     * @param $model
     * @return DMail
     */
    public static function init($view, $lang = 'fr')
    {

        $dm = new DMail();
        $dm->view = $view;
        $dm->lang = $lang;
        return $dm;

    }

    public function preview($data, $show = true){
        $data['mail_title'] = $this->title;
        $data['lang'] = $this->lang;
        if ($show) {
            echo Genesis::getView($this->view, $data);
            die;
        }
        return Genesis::getView($this->view, $data);
    }

    public function setTitle($object)
    {
        $this->title .= $object;
        return $this;
    }

    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @param $email
     * @param $name
     * @return $this
     */
    public function addCC($email, $name = null)
    {
        if (is_array($email)){
            $this->receiver_cc = $email;
        }else {
            $this->receiver_cc[$email] = $name;
        }
        return $this;
    }

    /**
     * @param $email
     * @param $name
     * @return $this
     */
    public function addReceiver($email, $name = null)
    {
        if (is_array($email)){
            $this->receivers = $email;
        }else {
            $this->receivers[$email] = $name;
        }
        return $this;
    }

    /**
     * @param $email
     * @param $name
     * @return $this
     */
    public function addAttachment($attachment, $name)
    {
        $this->attachments[$attachment] = $name;
        return $this;
    }

    public function sendMail($data)
    {

        $this->content = $this->preview($data, false);
        if (!__prod ) {
            \DClass\lib\Util::log($this->content, date("Y_m_d-H_i_s")."_".$this->object.".html", ROOT."cache/", "w");
            return 0;
        }

// Instantiation and passing `true` enables exceptions
        $mail = new PHPMailer(true);

        try {
            //Server settings
            //$mail->SMTPDebug = false;                                       // Enable verbose debug output ->SMTPDebug = false;
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';                                         // Set mailer to use SMTP
            $mail->Host = sm_smtp;  // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                                   // Enable SMTP authentication
            $mail->Username = sm_username;                     // SMTP username
            $mail->Password = sm_password;                               // SMTP password
            $mail->SMTPSecure = sm_smtpsecurity;                                  // Enable TLS encryption, `ssl` also accepted
            $mail->Port = sm_port;                                    // TCP port to connect to

            //Recipients
            $mail->setFrom(sm_from, $this->title);
            foreach ($this->receivers as $email => $name)
                $mail->addAddress($email, $name);     // Add a recipient
            //$mail->addAddress('ellen@example.com');               // Name is optional
            $mail->addReplyTo(sm_from, $this->title);

            // copy
            foreach ($this->receiver_cc as $email => $name)
                $mail->addCC($email, $name);

            // Attachments
            foreach ($this->attachments as $attachment => $name) {
                $mail->addAttachment($attachment, $name);         // Add attachments
            }

            // Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = $this->object;
            $mail->Body = $this->content;
            $mail->AltBody = $this->content;

            //echo 'Message has been sent';
            $result = $mail->send();
            Emaillog::create([
                "object" => $this->object . " - object : " . $this->object . ' to ' . json_encode($this->receivers),
                "log" => json_encode($result),
            ]);

            return [
                "success" => true,
                "result" => $result,
                "detail" => 'Message has been sent'
            ];
        } catch (Exception $e) {
            //echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            Emaillog::create([
                "object" => $this->object  . " - object : " . $this->object . ' to ' . json_encode($this->receivers),
                "log" => "Message could not be sent. Error detail: {$mail->ErrorInfo}",
            ]);

            return [
                "success" => false,
                "detail" => "Message could not be sent. Error detail: {$mail->ErrorInfo}"
            ];
        }
    }

}
