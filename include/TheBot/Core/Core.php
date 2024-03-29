<?php
/**
 *	@package TheBot\Core
 *	@version 1.0.1
 *	2018-09-22
 */

namespace TheBot\Core;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use TheBot\Compat;
use TheBot\Mail;

class Core extends Plugin {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		add_action( 'plugins_loaded' , array( $this , 'init_compat' ), 0 );
		// add_action( 'plugins_loaded' , array( $this , 'init_mail' ) );

		add_action( 'wp_enqueue_scripts' , array( $this , 'wp_enqueue_style' ) );

		add_action( 'phpmailer_init' , array( $this, 'configure_mailer') );

		$this
			->add_option( new Option\Email( 'mailer_from', '', __( 'From Address', 'wp-the-bot' ) ) )
			->add_option( new Option\Text( 'mailer_from_name', '', __( 'From Name', 'wp-the-bot' ) ) )
			->add_option( new Option\Boolean( 'mailer_smtp', '', __( 'Enable SMTP', 'wp-the-bot' ) ) )
			->add_option( new Option\Text( 'mailer_smtp_host', '', __( 'Hostname', 'wp-the-bot' ) ) )
			->add_option( new Option\Absint( 'mailer_smtp_port', 587, __( 'Port', 'wp-the-bot' ) ) )
			->add_option( new Option\ChoiceRadio( 'mailer_smtp_secure',	'',	__( 'Encryption', 'wp-the-bot' ) ) )
			->add_option( new Option\Boolean( 'mailer_smtp_all_certs', false, __( 'Allow all Certificates',  'wp-the-bot' ) ) )
			->add_option( new Option\Boolean( 'mailer_smtp_auth', false, __( 'SMTP Authentication',  'wp-the-bot' ) ) )
			->add_option( new Option\ChoiceRadio( 'mailer_smtp_auth_type', '', __( 'SMTP Auth Type', 'wp-the-bot' ) ) )
			->add_option( new Option\Text( 'mailer_smtp_auth_user', '', __( 'SMTP User', 'wp-the-bot' ) ) )
			->add_option( new Option\Text( 'mailer_smtp_auth_pass', '', __( 'SMTP Password', 'wp-the-bot' ) ) );

		$this->get_option('mailer_smtp_secure')->set_choices([
			''		=> __( 'None', 'wp-the-bot' ),
			'ssl'	=> __( 'SSL', 'wp-the-bot' ),
			'tls'	=> __( 'TLS', 'wp-the-bot' ),
		]);

		$this->get_option('mailer_smtp_auth_type')->set_choices([
			''			=> __( 'Try all', 'wp-the-bot' ),
			'CRAM-MD5'	=> __( 'MD5 Challenge-Response', 'wp-the-bot' ),
			'LOGIN'		=> __( 'Login', 'wp-the-bot' ),
			'PLAIN'		=> __( 'Plain', 'wp-the-bot' ),
			'XOAUTH2'	=> __( 'OAuth 2.0', 'wp-the-bot' ),
		]);

		$args = func_get_args();
		parent::__construct( ...$args );
	}

	/**
	 *	Load frontend styles and scripts
	 *
	 *	@action wp_enqueue_scripts
	 */
	public function wp_enqueue_style() {
	}

	/**
	 *	@action phpmailer_init
	 */
	public function configure_mailer( &$phpmailer ) {
		// set from
		if ( $from = $this->get_option( 'mailer_from' )->value ) {
			$phpmailer->setFrom( $from, $from, false );
		}

		if ( $this->get_option('mailer_smtp')->value ) {
			$port = intval( $this->get_option('mailer_smtp_port')->value );
			$phpmailer->isSMTP();
			$phpmailer->Host = $this->get_option('mailer_smtp_host')->value;// get_option('thebot_mailer_smtp_host'); //'wp194.webpack.hosteurope.de';
			$phpmailer->Port = $port ? $port : 25;

			if ( $secure = $this->get_option('mailer_smtp_secure')->value ) {
				$phpmailer->SMTPSecure = $secure;
			}
			if ( $this->get_option('mailer_smtp_all_certs')->value ) {
				$phpmailer->SMTPOptions = array(
					'ssl' => array(
						'verify_peer' => false,
						'verify_peer_name' => false,
						'allow_self_signed' => true
					)
				);
			}

			if ( $this->get_option('mailer_smtp_auth')->value ) {
				$phpmailer->SMTPAuth = true;
				$phpmailer->AuthType = $this->get_option('mailer_smtp_auth_type')->value;
				$phpmailer->Username = $this->get_option('mailer_smtp_auth_user')->value;
				$phpmailer->Password = $this->get_option('mailer_smtp_auth_pass')->value;
			}
		}
	}


	/**
	 *	Load Compatibility classes
	 *
	 *  @action plugins_loaded
	 */
	public function init_compat() {
	
		if ( is_multisite() ) {
			if ( ! function_exists('\is_plugin_active_for_network') ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			if ( is_plugin_active_for_network( $this->get_wp_plugin() ) ) {
				Compat\WPMU\WPMU::instance();				
			}
		}
	}


	/**
	 *	Init hook.
	 *
	 *  @action init
	 */
	// public function init_mail() {
	// 	Mail\Mail::instance();
	// }

	/**
	 *	Get asset url for this plugin
	 *
	 *	@param	string	$asset	URL part relative to plugin class
	 *	@return string URL
	 */
	public function get_asset_url( $asset ) {
		$pi = pathinfo($asset);
		if ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && in_array( $pi['extension'], ['css','js']) ) {
			// add .dev suffix (files with sourcemaps)
			$asset = sprintf('%s/%s.dev.%s', $pi['dirname'], $pi['filename'], $pi['extension'] );
		}
		return plugins_url( $asset, $this->get_plugin_file() );
	}


	/**
	 *	Get asset url for this plugin
	 *
	 *	@param	string	$asset	URL part relative to plugin class
	 *	@return string URL
	 */
	public function get_asset_path( $asset ) {
		$pi = pathinfo($asset);
		if ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && in_array( $pi['extension'], ['css','js']) ) {
			// add .dev suffix (files with sourcemaps)
			$asset = sprintf('%s/%s.dev.%s', $pi['dirname'], $pi['filename'], $pi['extension'] );
		}
		return $this->get_plugin_dir() . '/' . preg_replace( '/^(\/+)/', '', $asset );
		return plugins_url( $asset, $this->get_plugin_file() );
	}


}
