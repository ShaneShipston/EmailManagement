Email Management
================

Send emails easier

Requirements
------------

- PHP 5+

Examples
--------

Send an Email

	$email = new Email();
	$email->add_recipient('email@domain.com', 'Recipient');
	$email->set_subject('Email Demo');
	$email->set_body('Demo Email');
	$email->send();

With an attachment

	$email = new Email();
	$email->add_recipient('email@domain.com', 'Recipient');
	$email->set_subject('Email Demo');
	$email->set_body('Demo Email');
	$email->add_attachment('file.zip');
	$email->send();