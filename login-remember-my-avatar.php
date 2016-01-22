<?php
/*
 * Plugin Name: Login: Remember my Avatar
 * Description: A quick POC of replacing the user_login field with the last users avatar
 * Author: Dion Hulse
 * License: GPLv2
 */

class WordPress_Plugin_Login_Remember_My_Avatar {
	private $cookie_name = '';

	function __construct() {
		$this->cookie_name = LOGGED_IN_COOKIE . '_last_user';

		add_action( 'set_logged_in_cookie', array( $this, 'set_last_seen_user' ), 10, 5 );

		if ( isset( $_GET['login-remember-me-not'] ) ) {
			$this->forget_last_seen_user();
		}

		if ( !empty( $_COOKIE[ $this->cookie_name ] ) || !empty( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
			add_action( 'login_head', array( $this, 'login_head' ) );
			add_action( 'login_footer', array( $this, 'login_footer' ) );
		}
	}

	function forget_last_seen_user() {
		setcookie( $this->cookie_name, '', 0, COOKIEPATH, COOKIE_DOMAIN, $secure = false, $http_only = true );
		$_COOKIE[ $this->cookie_name ] = '';
	}

	function set_last_seen_user( $logged_in_cookie, $expire, $expiration, $user_id, $scheme = 'logged_in'  ) {
		if ( ! $expire ) {
			// Remember-me wasn't selected
			return;
		}

		$user = new WP_User( $user_id );

		setcookie( $this->cookie_name, $user->user_login, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $secure = false, $http_only = true );
	}

	function get_settings() {
		$user_login = !empty( $_COOKIE[ $this->cookie_name ] ) ? $_COOKIE[ $this->cookie_name ] : false;
		if ( ! $user_login && !empty( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
			list( $user_login, ) = explode( '|', $_COOKIE[ LOGGED_IN_COOKIE ], 2 );
		}

		$user = new WP_User( $user_login );

		return array(
			'user_login' => $user->user_login,
			'display_name' => $user->display_name,
			'avatar' => get_avatar( $user->user_email, 96 ),
			'forget_me_url' => add_query_arg( array( 'login-remember-me-not' => 1 ), wp_logout_url() ),
		);
	}

	function login_head() {
		echo '<script>var _wpLoginRememberMyAvatar = ' . wp_json_encode( $this->get_settings() ) . ';</script>';
		echo <<<EOH
			<style>
				.login-remember-my-avatar {
					text-align: center;
				}
				.login-remember-my-avatar .avatar {
					border-radius: 50%;
					display: block;
					margin: 0 auto;
				}
			</style>
EOH;
	}
	function login_footer() {
		echo <<<EOH
			<script>
				(function() {
					if ( typeof _wpLoginRememberMyAvatar == 'undefined' ) {
						return;
					}

					var new_html = '<p class="login-remember-my-avatar">';
					new_html += 'Welcome back, <span class="dispaly-name">' + _wpLoginRememberMyAvatar.display_name + '</span>'
					new_html += _wpLoginRememberMyAvatar.avatar;
					new_html += '<span><a href="' + _wpLoginRememberMyAvatar.forget_me_url + '">Not You?</a></span>';
					new_html += '<input type="hidden" name="log" value="' + _wpLoginRememberMyAvatar.user_login + '">';
					new_html += '</p>';

					var login_field_p = document.getElementById('user_login').parentNode.parentNode;	
					login_field_p.outerHTML = new_html;
				})();
			</script>
EOH;
	}
	
}
new WordPress_Plugin_Login_Remember_My_Avatar();
