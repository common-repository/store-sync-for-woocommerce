<?php
class WSS_Logger {
    public function initHooks() {
        add_action('wpmc_entities', array($this, 'registerEntities'));
    }

    public function registerEntities($entities) {
        $entities['wss_logger'] = array(
            'table_name' => 'wss_logs',
            'default_order' => 'created_at desc',
            'display_field' => 'name',
            'parent_menu' => WSS_MENU_SLUG,
            'singular' => __('Log', 'woo-store-sync'),
            'plural' => __('Logs', 'woo-store-sync'),
            'fields' => array(
                'created_at' => array(
                    'label' => __('Date', 'woo-store-sync'),
                    'type' => 'datetime',
                    'creatable' => false,
                    'editable' => false,
                ),
                'type' => array(
                    'label' => __('Type', 'woo-store-sync'),
                    'type' => 'text',
                    'creatable' => false,
                    'editable' => false,
                ),
                'message' => array(
                    'label' => __('Message', 'woo-store-sync'),
                    'type' => 'text',
                    'creatable' => false,
                    'editable' => false,
                ),
                'synchronization_id' => array(
                    'label' => __('Synchronization', 'woo-store-sync'),
                    'type' => 'belongs_to',
                    'ref_entity' => 'wss_syncs',
                    'creatable' => false,
                    'editable' => false,
                ),
            )
        );

        return $entities;
    }
}