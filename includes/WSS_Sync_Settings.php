<?php
class WSS_Sync_Settings extends WSS_Client {
    public function executeSync() {
        $count = 0;

        foreach ( $this->listSettingGroups() as $group ) {
            foreach ( $this->listSettings($group->id) as $setting ) {

                // check if local value is different, is positive then sync
                if ( empty($setting->value) || ( $setting->value != get_option($setting->id, true) ) ) {
                    $localValue = get_option($setting->id, true);

                    if ( !empty($localValue) ) {
                        $this->updateSetting($group->id, $setting->id, $localValue);
                        $count ++;
                    }
                }
            }
        }

        $this->log( sprintf(__('A total of %s setting items was synced'), $count) );
    }

    /**
     * @return array()
     */
    private function listSettingGroups() {
        try {
            $client = $this->getClient();
            $settings = $client->get( 'settings');

            return (array) $settings;
        }
        catch ( Exception $e ) {
            $this->log($e);
            return array();
        }
    }

    /**
     * @return array
     */
    private function listSettings($group) {
        $ignoreds = [
            'integration'
        ];

        if ( !in_array($group, $ignoreds) ) {
            try {
                $client = $this->getClient();
                $settings = $client->get("settings/{$group}");

                return (array) $settings;
            }
            catch ( Exception $e ) {
                $this->log($e);
            }
        }

        return array();
    }

    /**
     * @return boolean
     */
    public function updateSetting($group, $name, $value) {
        $ignoreds = [
            'rest_setting_setting_group_invalid'
        ];
        
        if ( in_array($name, $ignoreds) ) {
            return true;
        }

        try {
            $client = $this->getClient();
    
            $data = array('value' => $value);
            $result = $client->put("settings/{$group}/{$name}", $data);
        
            if ( !empty($result->id) && ( $result->id == $name ) ) {
                if ( is_array($value) ) {
                    $value = serialize($value);
                }

                $this->log( sprintf(__('Setting value changed: %s - to: %s'), $name, $value) );
            }
            else {
                $this->log( sprintf(__('Fail to update setting: %s (%s)'), $name, $group) );
            }

            return true;
        } catch ( Exception $e ) {
            $this->log($e);
            return false;
        }
    }
        
    // public function listProducts() {
    //     try {
    //         $client = $this->getClient();
    //         $products = $client->get( 'products', array(
    //             'per_page' => 100,
    //             'context' => 'edit',
    //         ) );
        
    //         var_dump($products);
    //     } catch ( Exception $e ) {
    //         $this->log($e);
    //     }
    // }
    
}