<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.fedegomez.es
 * @since      1.0.0
 *
 * @package    Send_To_Archive_Org
 * @subpackage Send_To_Archive_Org/admin/partials
 */
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
        <?php
        settings_fields('send_to_archive_org');
        do_settings_sections('send_to_archive_org');
        submit_button( __( 'Save settings', 'send-to-archive-org' ));
        ?>
    </form>
</div>