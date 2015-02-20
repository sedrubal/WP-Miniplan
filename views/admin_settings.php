<?php
/**
 * The miniplan settings for the wp-admin (user roles...)
 */

defined('MINIPLAN_ABSPATH') or die("[!] This script must be executed by a Wordpress instance!\r\n");

/**
 * creates the settings tab
 */
function miniplan_admin_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    global $wp_roles;
    $privileged_roles = array();
    $changes = false;
    if (isset($_POST['reset'])) {
        global $miniplan_default_privileged_roles;
        $privileged_roles = $miniplan_default_privileged_roles;
        $changes = true;
    } else if (isset($_POST['submit'])) {
        foreach ($wp_roles->get_names() as $role) {
            if (isset($_POST['role_' . strtolower($role)])) {
                if ($_POST['role_' . strtolower($role)] === "true") {
                    $privileged_roles[] = strtolower($role);
                }
            }
        }
        $changes = true;
    }
    if ($changes) {
        if (sizeof($privileged_roles) > 0) {
            update_option('miniplan_privileged_roles', $privileged_roles);
            echo '<div id="message" class="updated fade"><p><strong>' . __('Options saved.') . '</strong></p></div>';
        } else { echo '<div id="message" class="error fade"><p><strong>Es wurde keine Rolle als priviligiert ausgew&auml;lt. Die Einstellungen wurden nicht gespeichert!</strong></p></div>'; }
    }


    ?>
    <div class="wrap">
        <h2>Miniplan <?php _e('Settings')?></h2>
        <!---<p>Sie benutzen Version ' . '' . '</p>--->
        <p class="howto">Beachte: Wenn du einen Miniplan auf einer Seite anzeigen m&ouml;chtest, dann f&uuml;ge einfach den text <code>[miniplan id="x"]</code> in den Post oder auf in die Seite ein. (x steht f&uuml;r die id des Feeds. Falls du nur einen Miniplan auf deiner Wordpress Seite anzeigen willst, dann verwende einfach 1).</p>
        <form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
            <table class="form-table">
                <tbody>
                <tr valign="top">
                    <th scope="row">
                        <label for="miniplan_privileged_roles">Privilegierte User Rollen:</label>
                        <p class="howto">Ein User muss in einer der ausgew&auml;hlten Rolle sein, um einen Miniplan zu bearbeiten.</p>
                    </th>
                    <td>
                        <fieldset id="miniplan_privileged_roles">
                            <?php
                            foreach ($wp_roles->get_names() as $role) {
                                echo '<label for="role_' . strtolower($role) . '">
													<input name="role_' . strtolower($role) . '" id="role_' . strtolower($role) . '" value="true" ' . (in_array(strtolower($role), get_option('miniplan_privileged_roles')) ? 'checked="checked"' : '') . ' type="checkbox">
												' . __($role) . '</label><br>';
                            }
                            ?>
                        </fieldset>
                    </td>
                </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" name="submit" value="<?php echo translate( 'Save Changes' ); ?>" class="button button-primary">
                <input type="submit" name="reset" value="<?php echo translate( 'Reset Changes' ); ?>" class="button button-secondary reset">
            </p>
        </form>
    </div>
<?php

}