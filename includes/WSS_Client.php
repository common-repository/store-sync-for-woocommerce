<?php
use Automattic\WooCommerce\Client;

class WSS_Client {
    private $apiKey;
    private $apiSecret;
    private $url;
    private $outputLog;
    private $synchronizationId;

    public function __construct($opts = array()) {
        if ( !empty($opts['api_key']) ) {
            $this->apiKey = $opts['api_key'];
        }
        if ( !empty($opts['api_secret']) ) {
            $this->apiSecret = $opts['api_secret'];
        }
        if ( !empty($opts['url']) ) {
            $this->url = $opts['url'];
        }
    }

    public function setOutputLog($bool) {
        $this->outputLog = $bool;
    }

    public function setSynchronizationId($bool) {
        $this->synchronizationId = $bool;
    }

    protected function getClient() {
        static $client = null;
    
        if ( is_null($client) ) {
            $client = new Client(
                $this->url,
                $this->apiKey,
                $this->apiSecret,
                array(
                    'wp_api' => true,
                    'version' => 'wc/v2',
                    'verify_ssl' => false,
                    'query_string_auth' => true,
                )
            );
        }
    
        return $client;
    }
    
    private function testConnection() {
        return true;
    }

    protected function log($message, $type = 'info') {
        if ( $message instanceof Exception ) {
            $type = 'error';
            $message = $message->getMessage();
        }

        if ( !empty($this->synchronizationId) && ( wstoresync()->config('track_logs') == 'yes' ) ) {
            $logger = wpmc_get_entity('wss_logger');
            $logger->save_db_data(array(
                'created_at' => date('Y-m-d H:i:s'),
                'synchronization_id' => $this->synchronizationId,
                'type' => $type,
                'message' => $message,
            ));
        }

        if ( $this->outputLog ) {
            echo "[{$type}] {$message}<br/>";
            flush();
        }
    }
}