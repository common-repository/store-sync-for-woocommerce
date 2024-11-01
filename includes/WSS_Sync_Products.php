<?php
class WSS_Sync_Products extends WSS_Client {
    private $confWhenInexistent;
    private $confProductIdentifier;
    private $updateds = 0;
    private $createds = 0;
    private $remoteProducts = array();

    public function executeSync() {
        $this->confWhenInexistent = wstoresync()->config('when_product_inexistent');
        $this->confProductIdentifier = wstoresync()->config('product_identifier');
        $this->remoteProducts = $this->getRemoteProducts();

        foreach ( $this->getLocalProducts() as $product ) {
            $this->syncProduct($product);
        }

        $this->log( sprintf(__('A total of %s products was updated, %s created'), $this->updateds, $this->createds) );
    }

    private function syncProduct($product) {
        $remoteProduct = $this->findRemoteProduct($product);
        $created = false;

        switch ( $this->confWhenInexistent ) {
            case 'create':
                if ( empty($remoteProduct) ) {
                    // sync with remote site
                    $remoteProduct = $this->createRemoteProduct($product);

                    if ( !empty($remoteProduct) ) {
                        $created = true;
                        $this->createds ++;
                        $this->log( sprintf(__('Created the product %s in remote target', 'woo-store-sync'), $product->get_name()) );
                    }
                }
            break;
            case 'ignore':
            break;
        }

        if ( !empty($remoteProduct) && !$created ) {
            $updated = $this->updateRemoteProduct($remoteProduct, $product);

            if ( $updated ) {
                $this->updateds ++;
            }
        }
    }

    private function findRemoteProduct($product) {
        $remoteProducts = $this->remoteProducts;

        // check if have custom listener
        if ( has_filter('wss_remote_product_find') ) {
            return apply_filters('wss_remote_product_find', $product, $remoteProducts);
        }
        else {
            $key = null;

            switch ( $this->confProductIdentifier ) {
                case 'title':
                    $key = array_search($product->get_name(), array_column($remoteProducts, 'name'));
                break;
                case 'slug':
                    $key = array_search($product->get_slug(), array_column($remoteProducts, 'slug'));
                break;
            }
    
            return !empty($remoteProducts[$key]) ? $remoteProducts[$key] : null;   
        }
    }

    /**
     * @return array
     */
    private function getLocalProducts() {
        $args = apply_filters( 'wss_get_local_products_args', array(
            'posts_per_page' => 100,
            'type' => 'simple',
        ));

        return wc_get_products($args);
    }

    private function getRemoteProducts() {
        try {
            $client = $this->getClient();
            $products = $client->get( 'products', array(
                'per_page' => 100,
                'context' => 'edit',
            ) );
        
            foreach ( (array) $products as $key => $product ) {
                $products[$key] = (array) $product;
            }

            return $products;
        } catch ( Exception $e ) {
            $this->log($e);
            return array();
        }
    }

    private function createRemoteProduct($product) {
        try {
            $data = $this->buildApiProductData($product);
            $data = apply_filters('wss_product_sync_data', $data, $product);

            $client = $this->getClient();
            return $client->post('products', $data);
        }
        catch ( Exception $e ) {
            $this->log($e);
            return false;
        }
    }

    private function updateRemoteProduct($remoteProduct, $product) {
        try {
            $data = $this->buildApiProductData($product);
            $data['id'] = $remoteProduct['id'];
            $data = apply_filters('wss_product_sync_data', $data, $product, $remoteProduct);

            $client = $this->getClient();
            $updated = $client->put('products/'.$remoteProduct['id'], $data);

            return $updated;
        }
        catch ( Exception $e ) {
            $this->log($e);
            return false;
        }
    }

    private function buildApiProductData($product) {
        $data = array();
        $data['name'] = $product->get_name();
        $data['slug'] = $product->get_slug();
        $data['type'] = $product->get_type();
        $data['status'] = $product->get_status();
        $data['tax_status'] = $product->get_tax_status();
        $data['tax_class'] = $product->get_tax_class();
        $data['downloadable'] = $product->get_downloadable();
        $data['sku'] = $product->get_sku();
        $data['virtual'] = $product->get_virtual();
        $data['weight'] = $product->get_weight();
        $data['sold_individually'] = $product->get_sold_individually();
        $data['sale_price'] = $product->get_sale_price();
        $data['regular_price'] = $product->get_regular_price();
        $data['description'] = $product->get_description();
        $data['short_description'] = $product->get_short_description();
        $data['dimensions'] = array(
            'length' => $product->get_length(),
            'width' => $product->get_width(),
            'height' => $product->get_height(),
        );

        if ( wstoresync()->config('sync_product_stocks') ) {
            $data['manage_stock'] = $product->get_manage_stock();
            $data['stock_status'] = $product->get_stock_status();
            $data['stock_quantity'] = $product->get_stock_quantity();
        }

        return $data;
    }
}