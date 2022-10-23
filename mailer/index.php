<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

require 'config.php';



class Mailer {
	public $errors = [];
	
	public $name;
	public $email;
	public $subject;
	public $message;
	
	private $variables = [];
	
	public $subject_prefix = "website.de: new inquiry received: ";
	
	public $debug = false;
	
	public function __construct($post_vars) {
		$this->name = strlen($post_vars["name"]) > 0 ? $post_vars["name"] : "";
		$this->email = strlen($post_vars["email"]) > 0 ? $post_vars["email"] : "";
		$this->message = strlen($post_vars["field"]) > 0 ? nl2br($post_vars["field"]) : "";
		
		$this->variables = array (
			"name" => $this->name,
			"email" => $this->email ,
			"message" => $this->message
		);
		
		foreach($this->variables as $var_key => $var) {
			if(strlen($var) == 0) {
				$this->errors[] = $var_key;
			}
		}
		if($this->debug) {
			echo "<br><br>name: ".$this->name."<br>";
			echo "email: ".$this->email."<br>";
			echo "subject: ".$this->subject."<br>";
			echo "message: ".$this->message."<br>";
		}
		if(!$this->validate_email($this->email)) {
			$this->errors[] = "email not valid";
		}
		
		if(count($this->errors) == 0) {
			$subject = $this->subject_prefix.$this->name;
			$sent_mail = $this->send_email($this->email, $subject, $this->message, $name=$this->name, $sender_fullname=NULL, $cc=NULL, $reply=NULL, $bcc=NULL, $subject_prefix=$this->subject_prefix);
			
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array("sent_mail" => $sent_mail));
		} else {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array("sent_mail" => false, "errors" => $this->errors));
		}
		 
	}
	
	private function validate_email($email) {
		$emailErr = !filter_var($email, FILTER_VALIDATE_EMAIL) ? "Invalid email format" : "";
		return strlen($emailErr) == 0;
	}
	
	private function send_email($receipient, $subject=null, $body=null, $name=null, $sender_fullname=NULL, $cc=NULL, $reply=NULL, $bcc=NULL, $subject_prefix=NULL) {
		
		$errors = [];

				if($this->debug) {
					echo "<br>subject not empty<br>";
				}
				$mail = new PHPMailer(true);
				
				try {
					$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
					$mail->isSMTP();                                            //Send using SMTP
					$mail->Host       = EMAIL_HOST;                     		//Set the SMTP server to send through
					$mail->SMTPAuth   = true;                                   //Enable SMTP authentication
					$mail->Username   = EMAIL_USER;                   //SMTP username
					$mail->Password   = EMAIL_PASS;                         //SMTP password
					$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
					$mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
					$mail->CharSet   = 'UTF-8';
					$mail->Encoding  = 'base64';
					if(!$this->debug) {
						$mail->SMTPDebug = 0;
					}
					if(!is_null($sender_fullname)) {
						if(strlen($sender_fullname) > 0) {
							$mail->setFrom(EMAIL_USER, 'Kontaktformulat @ website.de');
						}
						
					} else {
						// $mail->setFrom(EMAIL_USER);
						$mail->setFrom($receipient);
						
					}
					
					if($this->validate_email($receipient)) {
						// Send Email to
						$mail->addAddress("kontakt@website.de");
						
						
					}
					
					if (!is_null($cc)) {
						if($this->validate_email($cc)) {
							$mail->addCC($cc);
						} else {
							$errors[] = "invalid cc";
						}
						
					}
					if (!is_null($reply)) {
						if($this->validate_email($reply)) {
							$mail->addReplyTo($reply);	
						} else {
							$errors[] = "invalid reply";
						}
						
					}
					if (!is_null($bcc)) {
						if($this->validate_email($bcc)) {
							$mail->addBCC($bcc);	
						} else {
							$errors[] = "invalid bcc";
						}
						
					}
					
					//Attachments
					// $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
					// $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

					$mail->isHTML(true);                                  //Set email format to HTML
					
					if(!is_null($subject) && !empty($subject)) {
						$mail->Subject = $subject;
						
					} else {
						$errors[] = "invalid subject";
						
					}

					if(!is_null($body) && !empty($body)) {
						$subject_short = substr($subject, strlen($subject_prefix)); // gibt "d" zurück
						$body_tmp = "<b>Subject:</b> ".$subject_short."<br>";
						$body_tmp .= "<b>E-Mail:</b> ".$receipient."<br><hr><br>";
						$body_tmp .= "<b>Message:</b><br><br>".$body;
						
						$mail->Body    = $body_tmp;
						$mail->AltBody = strip_tags(str_ireplace(array("<br />","<br>","<br/>"), "\r\n", $body_tmp));
					} else {
						$errors[] = "invalid body";	
						
					}
					
					
					if(count($errors) > 0) {
						echo "<ul>";
						foreach($errors as $error) {
							echo "<li>".$error."</li>";
						}
						echo "</ul>";
						
					} else {
						if($this->debug) {
							echo "<br>try sending...<br>";
						}
						
						$mail->send();
						
						if($this->debug) {
							echo 'Message has been sent';
						}
						return true;
						
					}
					
				} catch (Exception $e) {
					
					echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
					return false;
				}
				
				echo "<br>subject ,,,<br>";
				
			
		
		
		return "";
	}

}


if(isset($_POST['submit'])) {
	$mailer = new Mailer($_POST);
} else {
	?>
		<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		   <label for="name">name: </label><input id="name" type="text" name="name"><br>
		   <label for="email">email: </label><input id="email" type="email" name="email"><br>
		   <label for="subject">subject: </label><input id="subject" type="text" name="subject"><br>
		   <label for="message">nachricht: </label>
		   <textarea id="message" cols="100" rows="15" name="message">
		   </textarea>
		   <input type="submit" name="submit" value="Submit Form"><br>
		</form>
	<?
}
