<?php

namespace Stiqon\Shop\Content;

use WP_Error;
use WP_REST_Posts_Controller;

/**
 * WordPress' interface for designs (/wp-json/wp/v2/stiqon_design), which the
 * editor needs. To visitors it answers as the shop does: a design that is not
 * shown is not listed, and asked for by its number it is unknown – not
 * "forbidden", which would say that it exists.
 */
final class DesignsRest extends WP_REST_Posts_Controller
{
    /**
     * @param  \WP_Post  $post
     */
    public function check_read_permission($post)
    {
        return parent::check_read_permission($post) && (Designs::isShown($post->ID) || current_user_can('edit_post', $post->ID));
    }

    /**
     * @param  \WP_REST_Request  $request
     */
    public function get_item_permissions_check($request)
    {
        $post = $this->get_post($request['id']);

        // One WordPress would hand out, but the shop does not show.
        if (! is_wp_error($post) && parent::check_read_permission($post) && ! $this->check_read_permission($post)) {
            return new WP_Error('rest_post_invalid_id', 'Ungültige Beitrags-ID.', ['status' => 404]);
        }

        return parent::get_item_permissions_check($request);
    }
}
