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
    $site = untrailingslashit(ABSPATH);
    $directory = dirname($site).'/'.basename($site).'.mails';

    if (is_dir($directory) && is_writable($directory)) {
        file_put_contents(
            sprintf('%s/%s-%s.json', $directory, sprintf('%.6F', microtime(true)), bin2hex(random_bytes(3))),
            (string) wp_json_encode([
                'to' => array_values((array) ($mail['to'] ?? [])),
                'subject' => (string) ($mail['subject'] ?? ''),
                'message' => (string) ($mail['message'] ?? ''),
                'headers' => $mail['headers'] ?? '',
                'attachments' => array_map('basename', (array) ($mail['attachments'] ?? [])),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }

    // Sent, as far as WordPress is concerned – also where the directory is missing:
    // a development machine has nothing to send mail with.
    return true;
}, 10, 2);
