<?php
/**
 * Plugin Name: Role Based Newsletter
 * Description: Send newsletter emails to users filtered by role. Includes a test mode and shows recipients after sending.
 * Version: 1.1.0
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
        // Register the admin menu in German.
        add_menu_page( 'Rollen Newsletter', 'Rollen Newsletter', 'manage_options', 'role-newsletter', array( $this, 'render_page' ) );
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $all_roles = wp_roles()->get_names();
        $roles     = array();
        if ( isset( $all_roles['customer'] ) ) {
            $roles['customer'] = $all_roles['customer'];
        }

        if ( isset( $_POST['rbn_submit'] ) && check_admin_referer( 'rbn_send_newsletter', 'rbn_nonce' ) ) {
            $subject   = sanitize_text_field( wp_unslash( $_POST['rbn_subject'] ) );
            $content   = wp_kses_post( wp_unslash( $_POST['rbn_content'] ) );
            $role      = sanitize_text_field( wp_unslash( $_POST['rbn_role'] ) );
            $test_mode = isset( $_POST['rbn_test_mode'] );
            $test_user = isset( $_POST['rbn_test_user'] ) ? absint( $_POST['rbn_test_user'] ) : 0;

            $this->send_newsletter( $subject, $content, $role, $test_mode, $test_user );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Newsletter nach Rolle versenden', 'rbn' ); ?></h1>
            <form method="post">
                <?php wp_nonce_field( 'rbn_send_newsletter', 'rbn_nonce' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="rbn_subject"><?php esc_html_e( 'Betreff', 'rbn' ); ?></label></th>
                        <td><input name="rbn_subject" type="text" id="rbn_subject" value="" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rbn_content"><?php esc_html_e( 'Nachricht', 'rbn' ); ?></label></th>
                        <td><textarea name="rbn_content" id="rbn_content" rows="10" class="large-text" required></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rbn_role"><?php esc_html_e( 'Benutzerrolle', 'rbn' ); ?></label></th>
                        <td>
                            <select name="rbn_role" id="rbn_role" required>
                                <?php foreach ( $roles as $role_key => $role_name ) : ?>
                                    <option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( isset( $_POST['rbn_role'] ) && $_POST['rbn_role'] === $role_key ); ?>><?php echo esc_html( $role_name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rbn_test_mode"><?php esc_html_e( 'Testmodus', 'rbn' ); ?></label></th>
                        <td><label><input type="checkbox" name="rbn_test_mode" id="rbn_test_mode" value="1" <?php checked( ! empty( $_POST['rbn_test_mode'] ) ); ?> /> <?php esc_html_e( 'Nur an ausgewählten Benutzer senden', 'rbn' ); ?></label></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rbn_test_user"><?php esc_html_e( 'Testbenutzer', 'rbn' ); ?></label></th>
                        <td>
                            <select name="rbn_test_user" id="rbn_test_user">
                                <?php foreach ( get_users() as $user ) : ?>
                                    <option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( isset( $_POST['rbn_test_user'] ) && absint( $_POST['rbn_test_user'] ) === $user->ID ); ?>><?php echo esc_html( $user->user_email ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Newsletter senden', 'rbn' ), 'primary', 'rbn_submit' ); ?>
            </form>
        </div>
        <?php
    }

    private function send_newsletter( $subject, $content, $role, $test_mode = false, $test_user = 0 ) {
        $sent_emails = array();

        if ( $test_mode && $test_user ) {
            $user = get_user_by( 'id', $test_user );
            if ( $user ) {
                if ( wp_mail( $user->user_email, $subject, $content, array( 'Content-Type: text/html; charset=UTF-8' ) ) ) {
                    $sent_emails[] = $user->user_email;
                }
            }
        } else {
            $users = get_users( array( 'role' => $role ) );
            foreach ( $users as $user ) {
                if ( wp_mail( $user->user_email, $subject, $content, array( 'Content-Type: text/html; charset=UTF-8' ) ) ) {
                    $sent_emails[] = $user->user_email;
                }
            }
        }

        if ( $sent_emails ) {
            echo '<div class="updated"><p>' . esc_html__( 'Newsletter gesendet an:', 'rbn' ) . '</p><ul>';
            foreach ( $sent_emails as $mail ) {
                echo '<li>' . esc_html( $mail ) . '</li>';
            }
            echo '</ul></div>';
        } else {
            echo '<div class="error"><p>' . esc_html__( 'Keine E-Mails gesendet.', 'rbn' ) . '</p></div>';
        }
    }
}

new Role_Based_Newsletter();

