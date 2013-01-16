<?php
/**
 * Email
 * 
 * @version 1.0.0
 */
class Email 
{
	private $recipients = array();
	private $cc = array();
	private $bcc = array();
	private $subject;
	private $message;
	private $from;
	private $libr = "\r\n";
	private $priority = 3;
	private $type = 'text/html';
	private $charset = 'iso-8859-1';
	private $uid;
	private $headers = array();
	private $attachments = array();
	
	/**
	 * New Email
	 *
	 * @since 1.0.0
	 */
	public function __construct() 
	{
		$this->create_mime_boundry();
	}
	
	/**
	 * Add Recipients
	 *
	 * @since 1.0.0
	 * 
	 * @param string $email Email Address
	 * @param string $name Recipient Name
	 */
	public function add_recipient($email, $name = '') 
	{
		$this->new_email($email, $name);
		
		return $this;
	}
	
	/**
	 * Add Carbon Copies
	 *
	 * @since 1.0.0
	 * 
	 * @param string $email Email Address
	 * @param string $name Recipient Name
	 */
	public function add_cc($email, $name = '') 
	{
		$this->new_email($email, $name, 'cc');
		
		return $this;
	}
	
	/**
	 * Add Blind Carbon Copies
	 *
	 * @since 1.0.0
	 * 
	 * @param string $email Email Address
	 * @param string $name Recipient Name
	 */
	public function add_bcc($email, $name = '') 
	{
		$this->new_email($email, $name, 'bcc');
		
		return $this;
	}
	
	/**
	 * Modify Sender
	 *
	 * @since 1.0.0
	 * 
	 * @param string $email From Email
	 * @param string $name Sender Name
	 */
	public function set_from($email, $name = '') 
	{
		if($this->validate_email($email) && $this->dns_check($email)) 
		{
			if(!empty($name)) 
				$this->from = ucwords($name).' <'.$email.'>';
			else 
				$this->from = $email;
		}
		
		return $this;
	}
	
	/**
	 * Set Subject
	 *
	 * @since 1.0.0
	 * 
	 * @param string $subject Subject
	 */
	public function set_subject($subject) 
	{
		$this->subject = trim($subject);
		
		return $this;
	}
	
	/**
	 * Set Email Body
	 *
	 * @since 1.0.0
	 * 
	 * @param string $message Body
	 * @param string $type Content Type
	 * @param string $charset Character Set
	 */
	public function set_body($message, $type = '', $charset = '') 
	{
		if(!empty($type)) $this->type = $type;
		if(!empty($charset)) $this->charset = $charset;
		
		$message = $this->strip_line_breaks($message);
		
		$this->message = $this->mail_body($message);
		
		return $this;
	}
	
	/**
	 * Set Email Priority
	 *
	 * @since 1.0.0
	 * 
	 * @param string|integer $priority New Priority
	 */
	public function set_priority($priority) 
	{
		if(is_string($priority)) 
		{
			$priorities = array('low' => 4, 'normal' => 3, 'high' => 2);
			if(array_key_exists($priority, $priorities)) 
			{
				$this->priority = $priorities[$priority];
			}
		} 
		else
		{
			$this->priority = $priority;
		}
		
		return $this;
	}
	
	/**
	 * Add an Attachment
	 * 	- Disposition can be either attachment or inline (used for images inside HTML)
	 *
	 * @since 1.0.0
	 * 
	 * @param string $filepath File Location
	 * @param string $disposition Content Disposition
	 */
	public function add_attachment($filepath, $disposition = 'attachment') 
	{
		$file_info = $this->file_info($filepath);
		if(!$file_info) return;
		
		$filename = basename($filepath);
		$file_type = $this->mime_type($filepath);
		$chunks = chunk_split(base64_encode($file_info));
		
		$mail_part = '--'.$this->uid.$this->libr;
		$mail_part .= 'Content-type:'.$file_type.'; name="'.$filename.'"'.$this->libr;
		$mail_part .= 'Content-Transfer-Encoding: base64'.$this->libr;
		$mail_part .= 'Content-Disposition: '.$disposition.';'.$this->libr.$this->libr;
		$mail_part .= $chunks;
		$mail_part .= $this->libr.$this->libr;
		
		$this->attachments[] = $mail_part;
		
		return $this;
	}
	
	/**
	 * Process and Send the email
	 *
	 * @since 1.0.0
	 * 
	 * @return boolean Success/Failure
	 */
	public function send() 
	{
		if(empty($this->recipients)) throw new Exception('No Recipients.');
		
		$headers = '';
		
		if(!empty($this->from)) 
		{
			$headers .= "From: ".$this->from.$this->libr;
			$headers .= "Reply-To: ".$this->from.$this->libr;
		}

		if(!empty($this->cc)) $headers .= "Cc: ".implode(", ", $this->cc) . $this->libr;
		if(!empty($this->bcc)) $headers .= "Bcc: ".implode(", ", $this->bcc) . $this->libr;
		
		$headers .= "MIME-Version: 1.0" . $this->libr;
		$headers .= "X-Priority: ".$this->priority . $this->libr;
		
		if(!empty($this->attachments)) // With Attachment
		{ 
			$headers .= 'Content-Type: multipart/mixed; boundary="' . $this->uid . '"' . $this->libr . $this->libr;
			
			$message = '--' . $this->uid . '' . $this->libr;
			$message .= 'Content-Type: multipart/alternative; boundary="mail_' . $this->uid . '"' . $this->libr . $this->libr;
			$message .= $this->message;
			
			foreach($this->attachments as $attach) 
			{
				$message .= $attach;
			}
			
			$message .= '--' . $this->uid . '--';
		} 
		elseif($this->type == 'text/html') // Without Attachment
		{ 
			$headers .= 'Content-Type: multipart/alternative; boundary="mail_' . $this->uid . '"' . $this->libr . $this->libr;
			$message = $this->message . $this->libr . $this->libr;
		} 
		else 
		{
			$message = $this->message . $this->libr . $this->libr;
		}
		
		if(empty($this->subject)) $this->subject = '(No Subject)';
		
		return mail(implode(", ", $this->recipients), $this->subject, $message, $headers); 
	}
	
	/**
	 * Ensure Proper Formatting on Emails
	 *
	 * @since 1.0.0
	 * @access private
	 * 
	 * @param string $email Email Address
	 * @return boolean Pass/Fail
	 */
	private function validate_email($email) 
	{
		$pattern = '/^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/';
		if(preg_match($pattern, $email)) return true;
		
		return false;
	}
	
	/**
	 * Validate Email MX Records (Where possible)
	 *
	 * @since 1.0.0
	 * @access private
	 * 
	 * @param string $email Email Address
	 * @return boolean Pass/Fail
	 */
	private function dns_check($email) 
	{
		if(function_exists("checkdnsrr")) 
		{
			$parts = explode("@", $email);
			if(checkdnsrr($parts[1], "MX"))
			{
				return true;
			} 
			else 
			{
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Create a Unique Boundary
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function create_mime_boundry() 
	{
		$this->uid = md5(uniqid(time()));
	}
	
	/**
	 * Generate the email body
	 *
	 * @since 1.0.0
	 * @access private
	 * 
	 * @param string $message Unformatted Message
	 * @return string Formatted Email
	 */
	private function mail_body($message) 
	{
		$mail_body = '--mail_'.$this->uid.''.$this->libr;
		
		// Ensure plain text email clients can still read the email
		$mail_body .= 'Content-Type: text/plain; charset="'.$this->charset.'" '.$this->libr;
		$mail_body .= 'Content-Transfer-Encoding: 7bit'.$this->libr.$this->libr;
		$mail_body .= strip_tags(trim($message)).$this->libr.$this->libr;
		
		$mail_body .= '--mail_'.$this->uid.''.$this->libr;
		
		if($this->type == 'text/plain') 
		{
			$mail_body .= $this->libr;
			return $mail_body;
		}
		
		$mail_body .= 'Content-Type: text/html; charset="'.$this->charset.'"'.$this->libr;
		$mail_body .= 'Content-Transfer-Encoding: 7bit'.$this->libr.$this->libr;
		$mail_body .= trim($message).$this->libr.$this->libr;
		
		$mail_body .= '--mail_'.$this->uid.'--'.$this->libr.$this->libr;
		
		return $mail_body;
	}
	
	/**
	 * Get File Information
	 *
	 * @since 1.0.0
	 * @access private
	 * 
	 * @param string $file File Location
	 * @return string|boolean File Contents/Fail
	 */
	private function file_info($file) 
	{
		if(file_exists($file) && $info = file_get_contents($file))
			return $info;
		
		return false;
	}
	
	/**
	 * Add Email to respective pool
	 *
	 * @since 1.0.0
	 * @access private
	 * 
	 * @param string $email Email Address
	 * @param string $name Recipient Name
	 * @param string $type Pool Name
	 */
	private function new_email($email, $name, $type = 'recipients') 
	{
		if($this->validate_email($email) && $this->dns_check($email)) 
		{
			// Format Email
			if(!empty($name)) $recipient = ucwords($name).' <'.$email.'>';
			else $recipient = $email;
			
			// Add New Email to Pool
			switch($type) 
			{
				case 'cc';
					if(!in_array($recipient, $this->cc))
						$this->cc[] = $recipient;
					break;
				case 'bcc';
					if(!in_array($recipient, $this->bcc))
						$this->bcc[] = $recipient;
					break;
				default:
					if(!in_array($recipient, $this->recipients))
						$this->recipients[] = $recipient;
					break;
			}
		}
	}
	
	/**
	 * Get MIME Type
	 *
	 * @since 1.0.0
	 * @access private
	 * 
	 * @param string $file File Location
	 * @return string MIME Type
	 */
	private function mime_type($file) 
	{
		$path_parts = pathinfo($file);
		
		$mime_types = array(
			"pdf"=>"application/pdf"
			,"exe"=>"application/octet-stream"
			,"zip"=>"application/zip"
			,"docx"=>"application/msword"
			,"doc"=>"application/msword"
			,"xls"=>"application/vnd.ms-excel"
			,"ppt"=>"application/vnd.ms-powerpoint"
			,"gif"=>"image/gif"
			,"png"=>"image/png"
			,"jpeg"=>"image/jpg"
			,"jpg"=>"image/jpg"
			,"mp3"=>"audio/mpeg"
			,"wav"=>"audio/x-wav"
			,"mpeg"=>"video/mpeg"
			,"mpg"=>"video/mpeg"
			,"mpe"=>"video/mpeg"
			,"mov"=>"video/quicktime"
			,"avi"=>"video/x-msvideo"
			,"3gp"=>"video/3gpp"
			,"css"=>"text/css"
			,"js"=>"application/javascript"
			,"php"=>"text/html"
			,"htm"=>"text/html"
			,"html"=>"text/html"
			,"xml"=>"application/xml"
		);
		
		$extension = strtolower($path_parts['extension']);
		
		return $mime_types[$extension];
	}
	
	/**
	 * Remove Line Breaks
	 *
	 * @since 1.0.0
	 * @access private
	 * 
	 * @param string $message Content
	 * @return string Formatted Content
	 */
	private function strip_line_breaks($message) 
	{
		return preg_replace("/([\r\n])/", "", $message);
	}
}