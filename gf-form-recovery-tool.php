<?php
/*
Plugin Name: GF Form Recovery Tool
Description: Lists saved Gravity Forms entries, and allows for emailing all or individuals. Based on this plugin: https://wordpress.org/plugins/gf-form-recovery-tool-for-gravity-forms/
Version: 1.0.0
Author: iWitness Design, Topher
Author URI: https://iwitnessdesign.com
Text Domain: gf-form-recovery-tool
License: GPLv2 or Later
License URI: LICENSE
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add menu item
 */
function iwdf_gf_form_recovery_tool_menu() {
	add_management_page( esc_html__( 'GF Form Recovery', 'gf-form-recovery-tool' ), esc_html__( 'GF Form Recovery', 'gf-form-recovery-tool' ), 'manage_options', 'gf-form-recovery-tool', 'iwdf_gf_form_recovery_tool_admin' );
}

add_action( 'admin_menu', 'iwdf_gf_form_recovery_tool_menu' );

function iwdf_gf_form_recovery_tool_add_action_links( $actions, $plugin_file ) {
	static $plugin;

	if ( ! isset( $plugin ) ) {
		$plugin = plugin_basename( __FILE__ );
	}

	if ( $plugin == $plugin_file ) {
		$admin_page_link = array( 'admin-page' => '<a href="' . esc_url( admin_url( 'tools.php?page=gf-form-recovery-tool' ) ) . '">' . esc_html__( 'Link Recovery', 'gf-form-recovery-tool' ) . '</a>' );
		$actions         = array_merge( $admin_page_link, $actions );
	}

	return $actions;
}

add_filter( 'plugin_action_links', 'iwdf_gf_form_recovery_tool_add_action_links', 10, 5 );

/**
 * Add admin page
 */
function iwdf_gf_form_recovery_tool_admin() {
	// Only admins should be able to access this page
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You do not have sufficient permissions to access this page.' );
	}

	// Declare $wpdb as a global
	global $wpdb;

	// Make sure we're using the right database prefix
	$table_name = $wpdb->prefix . 'rg_incomplete_submissions';

	// Grab incomplete submissions
	$incomplete_submissions = $wpdb->get_results(
		'SELECT form_id, date_created, email, ip, uuid, source_url
		FROM `' . esc_sql( $table_name ) . '`'
	);

	echo '<div class="wrap">';
	echo '<h2>' . esc_html__( 'GF Form Recovery', 'gf-form-recovery-tool' ) . '</h2>';

	if ( $incomplete_submissions ) { ?>

        <p><?php esc_html_e( 'Below you can find all the incomplete Gravity Forms form submissions.', 'gf-form-recovery-tool' ); ?></p>
        <table class="widefat">
            <tr>
                <th><?php esc_html_e( 'Form ID', 'gf-form-recovery-tool' ); ?></th>
                <th><?php esc_html_e( 'Date/Time Created', 'gf-form-recovery-tool' ); ?></th>
                <th><?php esc_html_e( 'Email Address', 'gf-form-recovery-tool' ); ?></th>
                <th><?php esc_html_e( 'IP Address', 'gf-form-recovery-tool' ); ?></th>
                <th><?php esc_html_e( 'UUID', 'gf-form-recovery-tool' ); ?></th>
                <th><?php esc_html_e( 'Link', 'gf-form-recovery-tool' ); ?></th>
                <th><?php esc_html_e( 'Email Link', 'gf-form-recovery-tool' ); ?></th>
            </tr>

			<?php
			foreach ( $incomplete_submissions as $incomplete_submission ) {
				echo '<tr>';
				echo '<td>' . esc_html( $incomplete_submission->form_id ) . '</td>';
				echo '<td>' . esc_html( $incomplete_submission->date_created ) . '</td>';
				echo '<td>' . sanitize_email( $incomplete_submission->email ) . '</td>';
				echo '<td>' . esc_html( $incomplete_submission->ip ) . '</td>';
				echo '<td>' . esc_html( $incomplete_submission->uuid ) . '</td>';
				echo '<td><a href="' . trailingslashit( esc_url( $incomplete_submission->source_url ) ) . '?gf_token=' . esc_attr( $incomplete_submission->uuid ) . '" target="_blank">' . esc_html__( 'View Entry', 'gf-form-recovery-tool' ) . '</a></td>';

				echo '<td>';
				if ( is_email( $incomplete_submission->email ) ) {
					echo '<a href="' . admin_url( 'tools.php?page=gf-form-recovery-tool&gfuuid=' . $incomplete_submission->uuid ) . '">' . esc_html( 'Send', 'gf-form-recovery-tool' ) . '</a>';
				}
				echo '</td>';
				echo '</tr>';
			}
			?>
        </table>

		<?php
	} else {
		echo '<p>' . esc_html__( 'No incomplete submissions found.', 'gf-form-recovery-tool' ) . '</p>';
	}

	echo '</div>';
}

/*
 * Email single user
 */
function iwdf_email_single() {
	if ( is_admin() && 'gf-form-recovery-tool' == $_GET['page'] && ! empty( $_GET['gfuuid'] ) ) {

		global $wpdb;

		// go get the data about this row

		// Make sure we're using the right database prefix
		$table_name = $wpdb->prefix . 'rg_incomplete_submissions';

		// Grab incomplete submissions
		$submission = $wpdb->get_row( $wpdb->prepare(
			'SELECT email, uuid, source_url FROM `' . esc_sql( $table_name ) . '` WHERE `uuid` = %s',
			$_GET['gfuuid']
		)
		);

		if ( is_email( $submission->email ) ) {

			// send an email
			$to      = $submission->email;
			$subject = 'Your Craft3 Application';
			$message = 'Hi there! We\'d just like to remind you that you have a form to fill out!';
			$message .= '<a href="' . trailingslashit( esc_url( $submission->source_url ) ) . '?gf_token=' . esc_attr( $submission->uuid ) . '">Click here to continue your form.</a>';

			wp_mail( $to, $subject, $message );

			wp_redirect( admin_url( 'tools.php?page=gf-form-recovery-tool&email_success=yes' ) );
			exit;
		} else {
			wp_redirect( admin_url( 'tools.php?page=gf-form-recovery-tool&email_success=no' ) );
			exit;
		}

	}
}

add_action( 'admin_init', 'iwdf_email_single' );

function iwdf_email_admin_notice__success() {
	if ( is_admin() && 'yes' == $_GET['email_success'] ) {
		?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e( 'Email sent successfully.', 'gf-form-recovery-tool' ); ?></p>
        </div>
		<?php
	}
}

add_action( 'admin_notices', 'iwdf_email_admin_notice__success' );

function iwdf_email_admin_notice__error() {
	if ( is_admin() && 'no' == $_GET['email_success'] ) {
		$class   = 'notice notice-error is-dismissible';
		$message = __( 'Email not sent.', 'gf-form-recovery-tool' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}
}

add_action( 'admin_notices', 'iwdf_email_admin_notice__error' );
