<?php
/**
 * Plugin Name: Role Based Newsletter
 * Description: Send newsletter emails to users filtered by role.
 * Version: 1.0.0
 * Author: Codex Agent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Role_Based_Newsletter {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
    }

    public function register_menu() {
        add_menu_page( 'Role Newsletter', 'Role Newsletter', 'manage_options', 'role-newsletter', array( $this, 'render_page' ) );
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $roles = wp_roles()->get_names();

        if ( isset( $_POST['rbn_submit'] ) && check_admin_referer( 'rbn_send_newsletter', 'rbn_nonce' ) ) {
            $subject = sanitize_text_field( wp_unslash( $_POST['rbn_subject'] ) );
            $content = wp_kses_post( wp_unslash( $_POST['rbn_content'] ) );
            $role    = sanitize_text_field( wp_unslash( $_POST['rbn_role'] ) );
            $this->send_newsletter( $subject, $content, $role );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Send Role Based Newsletter', 'rbn' ); ?></h1>
            <form method="post">
                <?php wp_nonce_field( 'rbn_send_newsletter', 'rbn_nonce' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="rbn_subject"><?php esc_html_e( 'Subject', 'rbn' ); ?></label></th>
                        <td><input name="rbn_subject" type="text" id="rbn_subject" value="" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rbn_content"><?php esc_html_e( 'Content (HTML)', 'rbn' ); ?></label></th>
                        <td><textarea name="rbn_content" id="rbn_content" rows="10" class="large-text" required></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rbn_role"><?php esc_html_e( 'Recipient Role', 'rbn' ); ?></label></th>
                        <td>
                            <select name="rbn_role" id="rbn_role" required>
                                <?php foreach ( $roles as $role_key => $role_name ) : ?>
                                    <option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role_name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Send Newsletter', 'rbn' ), 'primary', 'rbn_submit' ); ?>
            </form>
        </div>
        <?php
    }

    private function send_newsletter( $subject, $content, $role ) {
        $users = get_users( array( 'role' => $role ) );
        foreach ( $users as $user ) {
            wp_mail( $user->user_email, $subject, $content, array( 'Content-Type: text/html; charset=UTF-8' ) );
        }
        echo '<div class="updated"><p>' . esc_html__( 'Newsletter sent to role: ', 'rbn' ) . esc_html( $role ) . '</p></div>';
    }
}

new Role_Based_Newsletter();

