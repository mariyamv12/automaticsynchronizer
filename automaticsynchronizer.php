<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/


if (!defined('_PS_VERSION_')) {
    exit;
}
/**
 * LocalHost
 */
define('DEBUG', true); // or true better for debugin! o.O
define('PS_SHOP_PATH', Configuration::get('AUTOMATICSYNCHRONIZER_MP_URL_WS', null));
define('PS_WS_AUTH_KEY', Configuration::get('AUTOMATICSYNCHRONIZER_MP_KEY_WS', null)); 
// current shop 
define('PS_THIS_SHOP_PATH', _PS_BASE_URL_);//__PS_BASE_URI__ 
define('PS_THIS_WS_AUTH_KEY', Configuration::get('PS_WEBSERVICE_KEY', null));  //BXR2W29HE00E8K7UMNAVTR256W923CNU
define('PS_ABSOLUTE_PATH', 'C:\\laragon\\www\\lolomarket\\img\\p\\'); 
// define('PS_ABSOLUTE_PATH', '/var/www/html/devsamshopmaroc/img/p/'); 

/**
 * ServerHost
 */

//Qoolshi
// define('DEBUG', true); // or true better for debugin! o.O
// define('PS_SHOP_PATH', 'https://dev.qoolshi.com/');
// define('PS_WS_AUTH_KEY', '1HPCD3J6ZJT6M1EQLI1V1NTLX83ZT7JL'); 
// current shop lolomrket
// define('PS_THIS_SHOP_PATH', 'https://lolomarket.site/');
// define('PS_THIS_WS_AUTH_KEY', '5MKRTBVZXQ2ZH6KF2AI5AHSC39VDE3I9');  
// current shop samshopmaroc
// define('PS_THIS_SHOP_PATH', 'https://dev.samshopmaroc.com/');
// define('PS_THIS_WS_AUTH_KEY', 'LV5XY6W6PM7VAH1HHJLVN66LEE1NLBH5'); 

// define('PS_ABSOLUTE_PATH', '/var/www/html/devsamshopmaroc/img/p/'); 

require_once('PSWebServiceLibrary.php');  

class automaticsynchronizer extends Module
{
  

    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'automaticsynchronizer';
        $this->tab = 'others';
        $this->version = '1.0.0';
        $this->author = 'Mariyam';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Automatic Synchronizer Products');
        $this->description = $this->l('Share products in another store');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('AUTOMATICSYNCHRONIZER_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('actionObjectProductUpdateAfter') &&
            $this->registerHook('actionProductUpdate') &&
            $this->registerHook('actionObjectProductDeleteBefore');
    }

    public function uninstall()
    {
        Configuration::deleteByName('AUTOMATICSYNCHRONIZER_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitautomaticsynchronizerModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitautomaticsynchronizerModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'AUTOMATICSYNCHRONIZER_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'AUTOMATICSYNCHRONIZER_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'AUTOMATICSYNCHRONIZER_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'AUTOMATICSYNCHRONIZER_LIVE_MODE' => Configuration::get('AUTOMATICSYNCHRONIZER_LIVE_MODE', true),
            'AUTOMATICSYNCHRONIZER_ACCOUNT_EMAIL' => Configuration::get('AUTOMATICSYNCHRONIZER_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'AUTOMATICSYNCHRONIZER_ACCOUNT_PASSWORD' => Configuration::get('AUTOMATICSYNCHRONIZER_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }
    
    public function hookactionObjectProductDeleteBefore($params)
    {       
   
        try {

            $id_created_product_shop = $params['object']->id;
           

            // creating webservice access to the shop
           $shopWebService = new PrestaShopWebservice(PS_THIS_SHOP_PATH, PS_THIS_WS_AUTH_KEY, DEBUG);

           // call to retrieve product with ID inserted in shop
   
           $xmldataShop = $shopWebService->get([
               'resource' => 'products',
               'id' => (int) $id_created_product_shop, 
           ]);
      
               // loop through this XML object to get each product field value shop
           $shopProductFields = $xmldataShop->product->children();
   
            $id = $shopProductFields->reference;
            // creating webservice access to the marketplace
            $mpWebService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
            $mpWebService->delete([
                'resource' => 'products',
                'id' => (int) $id, 
            ]);
            // echo 'Product with ID ' . $id . ' was successfully deleted' . PHP_EOL;
    } catch (PrestaShopWebserviceException $e) {
        // Here we are dealing with errors
        $trace = $e->getTrace();
        if ($trace[0]['args'][0] == 404) echo 'Bad ID';
        else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
        else echo '<b>ERROR:</b> ' . $e->getMessage();
    }
 
    }

    public function hookActionProductUpdate($params)
    {
       
        try {

            /**
             * Current Shop
             */
            $id_created_product_shop = $params['product']->id;       
             // creating webservice access to the shop
            $shopWebService = new PrestaShopWebservice(PS_THIS_SHOP_PATH, PS_THIS_WS_AUTH_KEY, DEBUG);
            // creating webservice access to the marketplace
            $mpWebService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
            // call to retrieve product with ID inserted in shop
            $xmldataShop = $shopWebService->get([
                'resource' => 'products',
                'id' => $id_created_product_shop, 
            ]);
                // loop through this XML object to get each product field value shop
            $shopProductFields = $xmldataShop->product->children();
  
            //check if shop have reference 
            if($shopProductFields->reference == '')
            {
                /**
                 * then the product is not created in the marketplace
                 * we try to create a new product in marketplace and update reference by id product in the shop...
                 **/ 

                // call to retrieve the blank schema product marketplace
                $blankXmlMp = $mpWebService->get(array('url' => PS_SHOP_PATH . '/api/products?schema=blank'));

                // loop through this XML object to get each product field value marektplace
                $resource_product = $blankXmlMp->product->children();
                unset($resource_product->id);
                unset($resource_product->position_in_category);
                unset($resource_product->manufacturer_name);
                unset($resource_product->id_default_combination);
                // unset($resource_product->associations);

              $this->setDataProduct($resource_product, $shopProductFields);

                $createdXmlMp = $mpWebService->add([
                    'resource' => 'products',
                    'postXml' => $blankXmlMp->asXML(),
                 ]);
                $mpProductFields = $createdXmlMp->product->children();
                $id_created_product_mp = $mpProductFields->id;
                // echo 'Product marketplace created with ID ' . $id_created_product_mp . PHP_EOL;

                /**
                 * we try to update reference shop id product inserted markertplace
                 */

                unset($shopProductFields->quantity);
                unset($shopProductFields->position_in_category);
                unset($shopProductFields->manufacturer_name);
                unset($shopProductFields->id_default_combination);
                unset($shopProductFields->associations);  
                $shopProductFields->reference = $id_created_product_mp;

                $updatedXmlShop = $shopWebService->edit([
                    'resource' => 'products',
                    'id' => (int) $shopProductFields->id,
                    'putXml' => $xmldataShop->asXML(),
                ]);
                // echo 'Product shop updated with ID ' . $shopProductFieldsUpdated->id . PHP_EOL;
 
                self::copyImage(PS_ABSOLUTE_PATH, $id_created_product_shop, $id_created_product_mp);
                exit;

            }
            else
            {
                $reference = $shopProductFields->reference;
                $xmldataMp = $mpWebService->get([
                    'resource' => 'products',
                    'id' => (int) $reference,
                ]);
     
                $mpProductFields = $xmldataMp->product->children();
                unset($mpProductFields->quantity);
                unset($mpProductFields->position_in_category);
                unset($mpProductFields->manufacturer_name);
                unset($mpProductFields->id_default_combination);
                // unset($mpProductFields->associations);

                $this->setDataProduct($mpProductFields, $shopProductFields);

                // Load Product Object Shop
                $productShop = new Product($id_created_product_shop);      
                $categories = $productShop->getCategories();  
                foreach ($categories as $key => $value) {
                    if($value != 2) {
                        $category = New Category($value);
                        $id_category_mp[] = $category->id_category_mp;
                    }
                }   

                // assign id_category_mps to created product mp      
                foreach ($id_category_mp as $id_category){
                    $mpProductFields->associations->categories->addChild('category')->addChild('id', $id_category);
                }
                $updatedxmldataMp = $mpWebService->edit([
                    'resource' => 'products',
                    'id' => $mpProductFields->id,
                    'putXml' => $xmldataMp->asXML(),
                ]);

                $updatedmpProductFields = $updatedxmldataMp->product->children();
                // echo 'Product marketplace updated with ID ' . $updatedmpProductFields->id . PHP_EOL;  
                // self::copyImage(PS_ABSOLUTE_PATH, $id_created_product_shop, $updatedmpProductFields->id);

                /**
                 * here we try to update quantity in the marketplace by updating StockAvailable 
                 */
               
                 $qte= StockAvailable::getQuantityAvailableByProduct($id_created_product_shop);
                 $stock_available_id = $updatedmpProductFields->associations->stock_availables->stock_available[0]->id;

                 try
                 {
   
                 $opt = array('resource' => 'stock_availables');
                 $opt['id'] = $stock_available_id;
                 $xml = $mpWebService->get($opt);
     
                 }
                 catch (PrestaShopWebserviceException $e)
                 {
                 // Here we are dealing with errors
                 $trace = $e->getTrace();
                 if ($trace[0]['args'][0] == 404) echo 'Bad ID';
                 else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
                 else echo 'Other error<br />'.$e->getMessage();
                 }

                 $stock_availables = $xml->children()->children();
                 //There we put our stock
                
                 $stock_availables->quantity = $qte;
                /*
                There we call to save our stock quantity.
                */
                try
                {
                $opt = array('resource' => 'stock_availables');
                $opt['putXml'] = $xml->asXML();
                $opt['id'] = $stock_available_id;
                $xml = $mpWebService->edit($opt);
                // if WebService don't throw an exception the action worked well and we don't show the following message
                // echo "Successfully updated.";
                // exit();
                }
                catch (PrestaShopWebserviceException $ex)
                {
                // Here we are dealing with errors
                $trace = $ex->getTrace();
                if ($trace[0]['args'][0] == 404) echo 'Bad ID';
                else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
                else echo 'Other error<br />'.$ex->getMessage();
                }     
                self::deleteImages($updatedmpProductFields->id, $mpWebService);
                self::copyImage( PS_ABSOLUTE_PATH, $id_created_product_shop, $updatedmpProductFields->id);          
            }
            
        } catch (PrestaShopWebserviceException $e) {
            // Here we are dealing with errors
            $trace = $e->getTrace();
            if ($trace[0]['args'][0] == 404) echo 'Bad ID';
            else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
            else echo '<b>ERROR2:</b> ' . $e->getMessage();
        }
    }

    public static function separe_numbers($intialString, $charSeparator = "/") 
    {
        $finalString = '';
        $stringLength = strlen($intialString);
        $finalString .= $intialString[0];
        for($i = 1; $i < $stringLength; $i++) {
        $finalString .= $charSeparator . $intialString[$i];
        }
        return ($finalString);
    }

    public static function copyImage( $absolute_path, $id_created_product_shop, $id_created_product_mp )
    {
    
        try {

            // Language id
            $id_lang = (int) Configuration::get('PS_LANG_DEFAULT'); 
            // Load Product Object shop
            $product = new Product($id_created_product_shop); 
            // Validate CMS Page object
            if (Validate::isLoadedObject($product)) {
                // Get product images
                $productImages = $product->getImages((int) $id_lang);

                if ($productImages && count($productImages) > 0) {

                    foreach ($productImages AS $key => $val) {
                        // get image id
                        $id_image = $val['id_image'];

                        // If required check image is cover or not
                        // $cover = $val['cover'];

                        // Get cover image for product
                        // $image = Image::getCover($id_created_product_shop);
                        // $id_image = $image['id_image'];
                        $path =  self::separe_numbers( (string)  $id_image); //ex: id_image = 206 => 2\\0\\6
                        $image_path = $absolute_path . $path .'/'. $id_image .'.jpg';

                        $ch = curl_init( PS_SHOP_PATH . "/api/images/products/". $id_created_product_mp ."/");

                        curl_setopt($ch, CURLOPT_USERPWD, PS_WS_AUTH_KEY.':');
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, array('image' => new CurlFile($image_path)));
                        curl_exec($ch);
                
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        
                        if (200 == $httpCode) {
                            // echo 'Product image was successfully created.'.'code: '.$httpCode. PHP_EOL;

                        }   
                        else {
                        
                            echo "Return code is {$httpCode} \n".curl_error($ch);
                        } 
                        curl_close($ch);
                    }
                    // exit;
                }
            }                                         
        } catch (\Throwable $th) {
            throw $th;
        } 
    }

    public static function getImages($id_product)
    {
        
        // Language id
        $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        // Load Product Object
        $product = new Product($id_product);

        // Validate CMS Page object
        if (Validate::isLoadedObject($product)) {
            // Get product images
            $productImages = $product->getImages((int) $id_lang);

            if ($productImages && count($productImages) > 0) {

                foreach ($productImages AS $key => $val) {
                    // get image id
                    $id_image = $val['id_image'];

                    // If required check image is cover or not
                    $cover = $val['cover'];
                }
                // exit;
            }
        }
    }

    public static function deleteImages($id_product, $mpWebService )
    {
        try{
  
        // call to retrieve product with ID inserted in shop
        $xmldataMp = $mpWebService->get([
            'resource' => 'products',
            'id' => $id_product, 
        ]);
        // loop through this XML object to get each product field value shop
        $mpProductFields = $xmldataMp->product->children();
        $c = $mpProductFields->associations->images->image->count();
        for($i=0 ; $i< $c ; $i++){
            $id = $mpProductFields->associations->images->image[$i]->id;
            $mpWebService->delete(['resource' => 'images/products/'.$id_product, 'id' => $id]);
        }
        } catch (PrestaShopWebserviceException $e) {
            // Here we are dealing with errors
            $trace = $e->getTrace();
            if ($trace[0]['args'][0] == 404) echo 'Bad ID';
            else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
            else echo '<b>ERROR:</b> ' . $e->getMessage();
        }
    }  
    
    protected function setDataProduct($resource_product, $shopProductFields)
    {
        
        $resource_product->id_manufacturer = $shopProductFields->id_manufacturer;
        $resource_product->id_supplier = $shopProductFields->id_supplier;
        $resource_product->id_category_default = 2;  
        $resource_product->cache_default_attribute = $shopProductFields->cache_default_attribute;
        $resource_product->id_default_image = $shopProductFields->id_default_image;
  
        $resource_product->id_tax_rules_group = $shopProductFields->id_tax_rules_group;
        $resource_product->type = $shopProductFields->type;
        $resource_product->id_shop_default = $shopProductFields->id_shop_default;
        // $resource_product->reference = $shopProductFields->reference;
        $resource_product->supplier_reference = $shopProductFields->supplier_reference;
        $resource_product->location = $shopProductFields->location;
        $resource_product->width = $shopProductFields->width;
        $resource_product->height = $shopProductFields->height;
        $resource_product->depth = $shopProductFields->depth;
        $resource_product->weight = $shopProductFields->weight;
        $resource_product->quantity_discount = $shopProductFields->quantity_discount;
        $resource_product->ean13 = $shopProductFields->ean13;
        $resource_product->isbn = $shopProductFields->isbn;
        $resource_product->upc = $shopProductFields->upc;
        $resource_product->mpn = $shopProductFields->mpn;
        $resource_product->cache_is_pack = $shopProductFields->cache_is_pack;
        $resource_product->cache_has_attachments = $shopProductFields->cache_has_attachments;
        $resource_product->is_virtual = $shopProductFields->is_virtual;
        $resource_product->state = $shopProductFields->state;
        $resource_product->additional_delivery_times = $shopProductFields->additional_delivery_times;
        $resource_product->delivery_in_stock->language[0]= $shopProductFields->delivery_in_stock->language[0];
        $resource_product->delivery_in_stock->language[1] = $shopProductFields->delivery_in_stock->language[1];
        $resource_product->delivery_out_stock->language[0]= $shopProductFields->delivery_out_stock->language[0];
        $resource_product->delivery_out_stock->language[1] = $shopProductFields->delivery_out_stock->language[1];
        $resource_product->product_type = $shopProductFields->product_type;
        $resource_product->on_sale = $shopProductFields->on_sale;
        $resource_product->online_only = $shopProductFields->online_only;
        $resource_product->ecotax = $shopProductFields->ecotax;
        $resource_product->minimal_quantity = $shopProductFields->minimal_quantity;
        $resource_product->low_stock_threshold = $shopProductFields->low_stock_threshold;
        $resource_product->low_stock_alert = $shopProductFields->low_stock_alert;
        $resource_product->price = $shopProductFields->price;
        $resource_product->wholesale_price = $shopProductFields->wholesale_price;
        $resource_product->unity = $shopProductFields->unity;
        $resource_product->unit_price_ratio = $shopProductFields->unit_price_ratio;
        $resource_product->additional_shipping_cost = $shopProductFields->additional_shipping_cost;
        $resource_product->customizable = $shopProductFields->customizable;
        $resource_product->text_fields = $shopProductFields->text_fields;
        $resource_product->uploadable_files = $shopProductFields->uploadable_files;
        $resource_product->active = $shopProductFields->active;
        $resource_product->redirect_type = $shopProductFields->redirect_type;
        $resource_product->id_type_redirected = $shopProductFields->id_type_redirected;
        $resource_product->available_for_order = $shopProductFields->available_for_order;
        $resource_product->available_date = $shopProductFields->available_date;
        $resource_product->show_condition = $shopProductFields->show_condition;
        $resource_product->condition = $shopProductFields->condition;
        $resource_product->show_price = $shopProductFields->show_price;
        $resource_product->indexed = $shopProductFields->indexed;
        $resource_product->visibility = $shopProductFields->visibility;
        $resource_product->advanced_stock_management = $shopProductFields->advanced_stock_management;
        $resource_product->date_add = $shopProductFields->date_add;
        $resource_product->date_upd = $shopProductFields->date_upd;
        $resource_product->pack_stock_type = $shopProductFields->pack_stock_type;
        $resource_product->meta_description->language[0] = $shopProductFields->meta_description->language[0];
        $resource_product->meta_description->language[1] = $shopProductFields->meta_description->language[1];
        $resource_product->meta_keywords->language[0] = $shopProductFields->meta_keywords->language[0];
        $resource_product->meta_keywords->language[1] = $shopProductFields->meta_keywords->language[1];
        $resource_product->meta_title->language[0] = $shopProductFields->meta_title->language[0];
        $resource_product->meta_title->language[1] = $shopProductFields->meta_title->language[1];
        $resource_product->link_rewrite->language[0] = $shopProductFields->link_rewrite->language[0];
        $resource_product->link_rewrite->language[1] = $shopProductFields->link_rewrite->language[1];
        $resource_product->name->language[0] = $shopProductFields->name->language[0];
        $resource_product->name->language[1] = $shopProductFields->name->language[1];

        $resource_product->description->language[0] = $shopProductFields->description->language[0];
        $resource_product->description->language[1] = $shopProductFields->description->language[1];
        $resource_product->description_short->language[0] = $shopProductFields->description_short->language[0];
        $resource_product->description_short->language[1] = $shopProductFields->description_short->language[1];                                                
        $resource_product->available_now->language[0] = $shopProductFields->available_now->language[0];
        $resource_product->available_now->language[1] = $shopProductFields->available_now->language[1];  
        $resource_product->available_later->language[0] = $shopProductFields->available_later->language[0];
        $resource_product->available_later->language[1] = $shopProductFields->available_later->language[1];  

        $resource_product->associations->combinations->combination->id = $shopProductFields->associations->combinations->combination->id;
        $resource_product->associations->product_option_values->product_option_value->id = $shopProductFields->associations->product_option_values->product_option_value->id;

        $resource_product->associations->product_features->product_feature->id = $shopProductFields->associations->product_features->product_feature->id;
        $resource_product->associations->product_features->product_feature->id_feature_value = $shopProductFields->associations->product_features->product_feature->id_feature_value;
        $resource_product->associations->product_features->product_feature->id_feature_value = $shopProductFields->associations->product_features->product_feature->id_feature_value;

        $resource_product->associations->tags->tag->id = $shopProductFields->associations->tags->tag->id;
        $resource_product->associations->stock_availables->stock_available->id = $shopProductFields->associations->stock_availables->stock_available->id;
        $resource_product->associations->stock_availables->stock_available->id_product_attribute = $shopProductFields->associations->stock_availables->stock_available->id_product_attribute;
        $resource_product->associations->attachments->attachment->id = $shopProductFields->associations->attachments->attachment->id;
        $resource_product->associations->accessories->product->id = $shopProductFields->associations->accessories->product->id;

        $resource_product->associations->product_bundle->product->id = $shopProductFields->associations->product_bundle->product->id;
        $resource_product->associations->product_bundle->product->id_product_attribute = $shopProductFields->associations->product_bundle->product->id_product_attribute;
        $resource_product->associations->product_bundle->product->quantity = $shopProductFields->associations->product_bundle->product->quantity;        

    }
}
