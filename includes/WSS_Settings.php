<?php
class WSS_Settings {
    public function initHooks() {
        $prefix = WSS_PREFIX;

        add_filter("mh_{$prefix}_settings", array($this, 'buildSettings'));
        add_filter("mh_{$prefix}_saving_options", array($this, 'mergeSavingOptions'));
        add_action("mh_{$prefix}_after_tab", array($this, 'afterSettingsTab'));
    }

    public function buildSettings($settings) {
        $settings['track_logs'] = array(
            'label' => __('Keep synchronizations log history', 'woo-store-sync'),
            'tab' => __('General', 'woo-store-sync'),
            'type' => 'checkbox',
            'default' => 'yes',
        );
        $settings['sync_product_stocks'] = array(
            'label' => __('Synchronize product stock information', 'woo-store-sync'),
            'tab' => __('General', 'woo-store-sync'),
            'type' => 'checkbox',
            'default' => 'yes',
        );
        $settings['when_product_inexistent'] = array(
            'label' => __('When remote product does not exists', 'woo-store-sync'),
            'tab' => __('General', 'woo-store-sync'),
            'type' => 'select',
            'options' => array(
                'ignore' => __('Ignore and do nothing', 'woo-store-sync'),
                'create' => __('Create new product on the target site', 'woo-store-sync'),
            ),
            'default' => 'ignore',
        );
        $settings['product_identifier'] = array(
            'label' => __('How to identify the product on the target site', 'woo-store-sync'),
            'tab' => __('General', 'woo-store-sync'),
            'type' => 'select',
            'options' => array(
                'title' => __('Check by the product title', 'woo-store-sync'),
                'slug' => __('Check by the product slug (permalink)', 'woo-store-sync'),
            ),
            'default' => 'title',
        );

        return $settings;
    }

    public function afterSettingsTab($tab) {
        switch($tab) {
            case 'general':
                $this->renderDestinationsBox();
            break;
        }
    }

    public function mergeSavingOptions($opts) {
        if ( !empty($_REQUEST['destinations']) ) {
            foreach ( $_REQUEST['destinations'] as $key => $dest ) {
                $opts['destinations'][$key] = array_map('sanitize_text_field', $dest);
            }
        }
        else {
            $opts['destinations'] = array();
        }
        
        return $opts;
    }

    private function renderDestinationsBox() {
        $destinations = (array) wstoresync()->config('destinations');
        $templateHtml = $this->getDestinationTemplate();
        $addDestNotice = esc_html__('Sorry, you can add multiple destinations only in Premium version.');

        ?>
        <div class="wpmc-onetomany-container-table">
            <table class="wpmc-onetomany-table widefat">
                <thead>
                    <th colspan="4">
                        <strong><?php echo esc_html__('Destination', 'woo-store-sync'); ?></strong>
                        <i class="align-right">
                            <?php echo esc_html__('Target sites where synchronizations will send the data', 'woo-store-sync'); ?>
                        </i>
                    </th>
                </thead>
                <tbody>
                    <?php
                    foreach ($destinations as $index => $item) {
                        $tplHtml = $this->getDestinationTemplate($item, $index);
                        $replaced = str_replace("{index}", $index, $tplHtml);
                        echo $replaced;
                    }
                    ?>
                </tbody>
                <!--<tfoot>
                    <th colspan="4">
                        <a class="button align-right add-destination disabled" title="<?php echo $addDestNotice; ?>">
                            <?php echo esc_html__('Add destination'); ?>
                        </a>
                    </th>
                </tfoot>-->
            </table>
        </div>
        <textarea id='wpmc-first-line-tpl' style='display: none;'>
            <?php echo $templateHtml; ?>
        </textarea>
        <script>
            jQuery(document).ready(function ($) {
                $('.add-destination').on('click', function(){
                    alert('<?php echo $addDestNotice; ?>')
                    return;
                });
            });
        </script>
        <?php
    }

    private function getDestinationTemplate($item = array(), $index = 0) {
        ob_start();

        ?>
        <tr data-id="rule_{index}" class="entity-field-row">
            <td class="">
                <?php wpmc_field_with_label(array(
                    'type' => 'text',
                    'name' => 'destinations[{index}][url]',
                    'label' => __('URL', 'woo-store-sync'),
                    'value' => !empty($item['url']) ? $item['url'] : '',
                    'required' => true,
                )); ?>
            </td>
            <td class="">
                <?php wpmc_field_with_label(array(
                    'type' => 'text',
                    'name' => 'destinations[{index}][api_key]',
                    'label' => __('API Key', 'woo-store-sync'),
                    'value' => !empty($item['api_key']) ? $item['api_key'] : '',
                    'required' => true,
                )); ?>
            </td>
            <td class="">
                <?php wpmc_field_with_label(array(
                    'type' => 'text',
                    'name' => 'destinations[{index}][api_secret]',
                    'label' => __('API Secret', 'woo-store-sync'),
                    'value' => !empty($item['api_secret']) ? $item['api_secret'] : '',
                    'required' => true,
                )); ?>
            </td>
            <td class="remove">
                <a class="wpmc-line-remove"></a>
            </td>
        </tr>
        <?php

        $template = ob_get_contents();
        ob_end_clean();

        return $template;
    }
}