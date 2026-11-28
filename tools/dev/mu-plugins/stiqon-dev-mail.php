<?php
/**
 * Plugin Name: Stiqon – local mail
 * Description: A local installation sends no mail. What WordPress would send is written to a file per message, next to the site.
 *
 * tools/dev/setup-wordpress.sh links this into development and test sites
 * (wp-content/mu-plugins). It is never part of the shop.
 *
 * The messages of the site "dev" are in <root>/sites/dev.mails/, one JSON file
 * each, named so that they sort in the order they were sent. Tests read them
 * there; so can a person who wants to see what a customer would get.
 */

defined('ABSPATH') || exit;

add_filter('pre_wp_mail', static function ($sent, array $mail) {
    // Only where the installation says it is a local one (setup-wordpress.sh writes that
    // into wp-config.php). Should this file ever travel to a real site with a copy of
    // wp-content, it must not swallow the shop's mail there.
    if (wp_get_environment_type() !== 'local') {
        return $sent;
    }

    $site = untrailingslashit(ABSPATH);
    $directory = dirname($site).'/'.basename($site).'.mails';
    $file = sprintf('%s/%s-%s.json', $directory, sprintf('%.6F', microtime(true)), bin2hex(random_bytes(3)));

    $kept = is_dir($directory) && is_writable($directory) && file_put_contents(
        $file,
        (string) wp_json_encode([
            'to' => array_values((array) ($mail['to'] ?? [])),
            'subject' => (string) ($mail['subject'] ?? ''),
            'message' => (string) ($mail['message'] ?? ''),
            'headers' => $mail['headers'] ?? '',
            'attachments' => array_map('basename', (array) ($mail['attachments'] ?? [])),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ) !== false;

    if ($kept) {
        // Names, addresses, links that set a password: for the site's own user only.
        chmod($file, 0600);
    }

    // A mail that was kept counts as sent. One that could not be kept counts as not sent –
    // WordPress and WooCommerce then report it – and still does not leave the machine.
    return $kept;
}, 10, 2);
