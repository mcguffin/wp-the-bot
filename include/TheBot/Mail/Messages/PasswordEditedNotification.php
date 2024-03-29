<?php
/**
 *	@package TheBot\Core
 *	@version 1.0.1
 *	2018-09-22
 */

namespace TheBot\Mail\Messages;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use TheBot\Core;
use TheBot\Mail;
use TheBot\Settings;

class PasswordEditedNotification extends Mail\Message {

	/**
	 *	@inheritdoc
	 */
	protected $id = 'user/password-edited-notification';

	/**
	 *	@inheritdoc
	 */
	protected $html_support = true;

	/**
	 *	@inheritdoc
	 */
	protected $context = 'network';

	/**
	 *	@inheritdoc
	 */
	protected $allow_disable = true;

	/**
	 *	@inheritdoc
	 */
	protected $capabilities = ['read'];


	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		$args = func_get_args();
		parent::__construct( ...$args );


		$this->add_support('html')
			->add_support('custom_subject')
			->add_support('custom_template')
			->add_support('custom_recipient');


		$this
			->add_option( new Core\Option\Boolean( 'custom_template', false, __('Custom Template','wp-the-bot'), $this->id ) )
			->add_option( new Core\Option\Boolean( 'custom_subject', false, __('Custom Subject','wp-the-bot'), $this->id ) )
			->add_option( new Core\Option\Text( 'subject', __( '[%s] Password Changed' ), __('Subject','wp-the-bot'), $this->id ) );

		add_filter('password_change_email', [ $this, 'mail_hook' ], 10, 3 );

		$this->title = __('Password Changed in WP-Admin','wp-the-bot');
		$this->description = __('Sent to the User when an Admin changed his Password.','wp-the-bot');

	}

	/**
	 *	@action wp_password_change_notification_email
	 */
	public function mail_hook( $email, $user_arr, $user ) {

		$mails = Mail\Mail::instance();

		if ( $this->get_option('custom_subject')->value ) {
			$email['subject'] = $this->get_option('subject')->value;
		}

		if ( $this->get_option('html')->value ) {
			$mails->set_html();
			if ( $this->get_option('custom_template')->value ) {
				$vars = [
					'user_login'		=> $user['user_login'],
					'user_email'		=> $user['user_email'],
					'admin_email'		=> get_option( 'admin_email' ),
				];
				$email['message'] = $mails->render_email( $this->id, $vars );

			} else {
				$email['message'] = $mails->wrap_email( $message );
			}
		}

		// get subscribers
		// if ( $this->option('override_recipient') ) {
		// 	$email['to'] = implode(',', [ $email['to'], $this->option('recipients') ]);
		// }
		//
		return $email;
	}
}
