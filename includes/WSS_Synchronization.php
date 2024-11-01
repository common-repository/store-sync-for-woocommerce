<?php
class WSS_Synchronization {
    public function initHooks() {
        add_action('wpmc_entities', array($this, 'registerEntities'));

        // manage specific entity behaviors
        if ( wpmc_current_entity() == 'wss_syncs' ) {
            // form
            add_action('wpmc_form_render_after', array($this, 'afterFormRender'));
            add_action('wpmc_data_saved', array($this, 'afterFormSave'), 10, 2);

            // checks
            add_action('wpmc_before_entity', array($this, 'checkBeforeCreate'));

            // actions
            add_filter('wpmc_list_actions', array($this, 'listActions'), 10, 2);
            add_action('wpmc_run_action_synchronize', array($this, 'executeActionSynchronize'));
        }
    }

    public function registerEntities($entities) {
        $entities['wss_syncs'] = array(
            'table_name' => 'wss_synchronizations',
            'default_order' => 'name',
            'display_field' => 'name',
            'parent_menu' => WSS_MENU_SLUG,
            'singular' => __('Synchronization', 'woo-store-sync'),
            'plural' => __('Synchronizations', 'woo-store-sync'),
            'fields' => array(
                'name' => array(
                    'label' => __('Name', 'woo-store-sync'),
                    'type' => 'text',
                    'required' => true
                ),
                'destination' => array(
                    'label' => __('Destination', 'woo-store-sync'),
                    'type' => 'select',
                    'required' => true,
                    'choices' => $this->listDestinations(),
                ),
                'sync_type' => array(
                    'label' => __('Type', 'woo-store-sync'),
                    'type' => 'select',
                    'required' => true,
                    'choices' => array(
                        'settings' => __('Settings', 'woo-store-sync'),
                        'products' => __('Products', 'woo-store-sync'),
                        // 'stocks' => __('Stocks', 'woo-store-sync'),
                    )
                ),
                // 'schedule_every' => array(
                //     'label' => __('Schedule', 'woo-store-sync'),
                //     'type' => 'select',
                //     'choices' => array(
                //         'houly' => __('Houly', 'woo-store-sync'),
                //         'hour_3' => __('Every 3 hours', 'woo-store-sync'),
                //         'hour_5' => __('Every 5 hours', 'woo-store-sync'),
                //         'hour_12' => __('Every 12 hours', 'woo-store-sync'),
                //         'daily' => __('Daily', 'woo-store-sync'),
                //         'weekly' => __('Weekly', 'woo-store-sync'),
                //     )
                // ),
                'enabled' => array(
                    'label' => __('Enabled', 'woo-store-sync'),
                    'type' => 'boolean',
                    'default' => 1,
                ),
                'last_run' => array(
                    'label' => __('Last run', 'woo-store-sync'),
                    'type' => 'datetime',
                    'creatable' => false,
                    'editable' => false,
                ),
            )
        );

        if ( wstoresync()->config('track_logs') == 'yes' ) {
            $entities['wss_syncs']['fields']['logs'] = array(
                'label' => __('Logs', 'woo-store-sync'),
                'type' => 'one_to_many',
                'ref_entity' => 'wss_logger',
                'ref_column' => 'synchronization_id',
                'creatable' => false,
                'editable' => false,
            );
        }

        return $entities;
    }

    public function listActions($actions, $item = []){
        $entity = wpmc_get_current_entity();
        $actions['synchronize'] = $entity->get_action_link('synchronize', $item['id'], __('Run', 'woo-store-sync'));

        return $actions;
    }

    public function listDestinations() {
        $destinations = (array) wstoresync()->config('destinations');
        $list = array();
        
        foreach ( $destinations as $key => $dest ) {
            $list[$key] = $dest['url'];
        }

        return $list;
    }

    public function checkBeforeCreate() {
        $destinations = $this->listDestinations();

        if ( empty($destinations) ) {
            $url = esc_url(admin_url('admin.php?page=' . WSS_MENU_SLUG));
            $createLink = sprintf('<a href="%s">%s</a>', $url, __('Click here', 'woo-store-sync'));
            $message = sprintf(__('Attention: You need to add at least one destination to be able to create synchronizations. %s to configure.', 'woo-store-sync'), $createLink);
            wpmc_flash_message($message, 'error');
        }
    }

    public function afterFormRender() {
        ?>
        <input type="submit" value="<?php echo __('Save and run', 'woo-store-sync'); ?>" id="submit" class="button-secondary" name="submit">
        <?php
    }

    public function afterFormSave(WPMC_Entity $entity, $item = array()) {
        if ( !empty($_POST['submit']) ) {
            switch(sanitize_text_field($_POST['submit'])) {
                case __('Save and run', 'woo-store-sync'):
                    wpmc_redirect( $entity->get_action_url('synchronize', $item['id']) );
                break;
            }
        }
    }

    public function executeActionSynchronize($ids) {
        $syncId = current((array)$ids);
        $entity = wpmc_get_current_entity();
        $sync = $entity->find_by_id($syncId);
        $destinations = (array) wstoresync()->config('destinations');

        if ( empty($destinations[ $sync['destination'] ]) ) {
            wpmc_flash_message(__('Invalid destination, please try to edit and save the synchronization.'), 'error');
            wpmc_redirect( wpmc_entity_home() );
        }

        $dest = $destinations[ $sync['destination'] ];
        $listingUrl = $entity->listing_url();

        ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit">
                <br/>
            </div>
            <?php wpmc_flash_render(); ?>
            <h2>
                <?php echo esc_html__('Run synchronization', 'woo-store-sync'); ?>
                <a class="add-new-h2" href="<?php echo esc_url($listingUrl); ?>">
                    <?php echo esc_html__(sprintf(__('Back to %s', 'woo-store-sync'), $entity->get_plural())); ?>
                </a>
            </h2>
            <div class="wss-sync-output">
                <?php $this->executeSync($dest, $sync, $entity); ?>
            </div>
        </div>
        <style>
            .wss-sync-output {
                font-size: 14px;
            }
        </style>
        <?php
    }

    private function executeSync($dest, $sync, WPMC_Entity $entity) {
        
        switch ( $sync['sync_type'] ) {
            case 'settings':
                require_once __DIR__ . '/WSS_Sync_Settings.php';
                $client = new WSS_Sync_Settings($dest);
                break;
            case 'products':
                require_once __DIR__ . '/WSS_Sync_Products.php';
                $client = new WSS_Sync_Products($dest);
                break;
        }
        
        $client->setSynchronizationId( $sync['id'] );
        $client->setOutputLog(true);
        $client->executeSync();

        $entity->save_db_data([
            'id' => $sync['id'],
            'last_run' => date('Y-m-d H:i:s'),
        ]);
    }
}