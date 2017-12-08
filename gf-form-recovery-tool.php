<?php
/*
Plugin Name: GF Form Recovery Tool
Description: Lists saved Gravity Forms entries, and allows for emailing all or individuals. Based on this plugin: https://wordpress.org/plugins/save-and-continue-link-recovery-for-gravity-forms/
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
function iwdf_save_and_continue_link_recovery_menu() {
	add_management_page( esc_html__( 'Save and Continue Link Recovery for Gravity Forms', 'save-and-continue-link-recovery' ), esc_html__( 'Save and Continue Link Recovery', 'save-and-continue-link-recovery' ), 'manage_options', 'save-and-continue-link-recovery', 'iwdf_save_and_continue_link_recovery_admin' );
}

add_action( 'admin_menu', 'iwdf_save_and_continue_link_recovery_menu' );

function iwdf_save_and_continue_link_recovery_add_action_links( $actions, $plugin_file ) {
	static $plugin;

	if ( ! isset( $plugin ) ) {
		$plugin = plugin_basename( __FILE__ );
	}

	if ( $plugin == $plugin_file ) {
		$admin_page_link = array( 'admin-page' => '<a href="' . esc_url( admin_url( 'tools.php?page=save-and-continue-link-recovery' ) ) . '">' . esc_html__( 'Link Recovery', 'save-and-continue-link-recovery' ) . '</a>' );
		$actions         = array_merge( $admin_page_link, $actions );
	}

	return $actions;
}

add_filter( 'plugin_action_links', 'iwdf_save_and_continue_link_recovery_add_action_links', 10, 5 );

/**
 * Add admin page
 */
function iwdf_save_and_continue_link_recovery_admin() {
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
	echo '<h2>' . esc_html__( 'Save and Continue Link Recovery for Gravity Forms', 'save-and-continue-link-recovery' ) . '</h2>';

	if ( $incomplete_submissions ) { ?>

        <p><?php esc_html_e( 'Below you can find all the incomplete Gravity Forms form submissions.', 'save-and-continue-link-recovery' ); ?></p>
        <table class="widefat">
            <tr>
                <th><?php esc_html_e( 'Form ID', 'save-and-continue-link-recovery' ); ?></th>
                <th><?php esc_html_e( 'Date/Time Created', 'save-and-continue-link-recovery' ); ?></th>
                <th><?php esc_html_e( 'Email Address', 'save-and-continue-link-recovery' ); ?></th>
                <th><?php esc_html_e( 'IP Address', 'save-and-continue-link-recovery' ); ?></th>
                <th><?php esc_html_e( 'UUID', 'save-and-continue-link-recovery' ); ?></th>
                <th><?php esc_html_e( 'Save and Continue Link', 'save-and-continue-link-recovery' ); ?></th>
            </tr>

			<?php
			foreach ( $incomplete_submissions as $incomplete_submission ) {
				echo '<tr>';
				echo '<td>' . esc_html( $incomplete_submission->form_id ) . '</td>';
				echo '<td>' . esc_html( $incomplete_submission->date_created ) . '</td>';
				echo '<td>' . sanitize_email( $incomplete_submission->email ) . '</td>';
				echo '<td>' . esc_html( $incomplete_submission->ip ) . '</td>';
				echo '<td>' . esc_html( $incomplete_submission->uuid ) . '</td>';
				echo '<td><a href="' . trailingslashit( esc_url( $incomplete_submission->source_url ) ) . '?gf_token=' . esc_attr( $incomplete_submission->uuid ) . '" target="_blank">' . esc_html__( 'View Entry', 'save-and-continue-link-recovery' ) . '</a></td>';
				echo '</tr>';
			}
			?>
        </table>

		<?php
	} else {
		echo '<p>' . esc_html__( 'No incomplete submissions found.', 'save-and-continue-link-recovery' ) . '</p>';
	}

	echo '</div>';
}
