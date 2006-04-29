<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2006 osCommerce

  Released under the GNU General Public License
*/

  class osC_Order {
    var $info, $totals, $products, $customer, $delivery, $content_type;

/* Private variables */

    var $_id;

/* Class constructor */

    function osC_Order($order_id = '') {
      if (is_numeric($order_id)) {
        $this->_id = $order_id;
      }

      $this->info = array();
      $this->totals = array();
      $this->products = array();
      $this->customer = array();
      $this->delivery = array();

      if (tep_not_null($order_id)) {
        $this->query($order_id);
      } else {
        $this->cart();
      }
    }

/* Public methods */

    function getStatusID($id) {
      global $osC_Database;

      $Qorder = $osC_Database->query('select orders_status from :table_orders where orders_id = :orders_id');
      $Qorder->bindTable(':table_orders', TABLE_ORDERS);
      $Qorder->bindInt(':orders_id', $id);
      $Qorder->execute();

      if ($Qorder->numberOfRows()) {
        return $Qorder->valueInt('orders_status');
      }

      return false;
    }

    function remove($id) {
      global $osC_Database;

      $Qcheck = $osC_Database->query('select orders_status from :table_orders where orders_id = :orders_id');
      $Qcheck->bindTable(':table_orders', TABLE_ORDERS);
      $Qcheck->bindInt(':orders_id', $id);
      $Qcheck->execute();

      if ($Qcheck->valueInt('orders_status') === 4) {
        $Qdel = $osC_Database->query('delete from :table_orders_products_download where orders_id = :orders_id');
        $Qdel->bindTable(':table_orders_products_download', TABLE_ORDERS_PRODUCTS_DOWNLOAD);
        $Qdel->bindInt(':orders_id', $id);
        $Qdel->execute();

        $Qdel = $osC_Database->query('delete from :table_orders_products_attributes where orders_id = :orders_id');
        $Qdel->bindTable(':table_orders_products_aattributes', TABLE_ORDERS_PRODUCTS_ATTRIBUTES);
        $Qdel->bindInt(':orders_id', $id);
        $Qdel->execute();

        $Qdel = $osC_Database->query('delete from :table_orders_products where orders_id = :orders_id');
        $Qdel->bindTable(':table_orders_products', TABLE_ORDERS_PRODUCTS);
        $Qdel->bindInt(':orders_id', $id);
        $Qdel->execute();

        $Qdel = $osC_Database->query('delete from :table_orders_status_history where orders_id = :orders_id');
        $Qdel->bindTable(':table_orders_status_history', TABLE_ORDERS_STATUS_HISTORY);
        $Qdel->bindInt(':orders_id', $id);
        $Qdel->execute();

        $Qdel = $osC_Database->query('delete from :table_orders_total where orders_id = :orders_id');
        $Qdel->bindTable(':table_orders_total', TABLE_ORDERS_TOTAL);
        $Qdel->bindInt(':orders_id', $id);
        $Qdel->execute();

        $Qdel = $osC_Database->query('delete from :table_orders where orders_id = :orders_id');
        $Qdel->bindTable(':table_orders', TABLE_ORDERS);
        $Qdel->bindInt(':orders_id', $id);
        $Qdel->execute();
      }

      if (isset($_SESSION['prepOrderID'])) {
        unset($_SESSION['prepOrderID']);
      }
    }

    function insert() {
      global $osC_Database, $osC_Customer, $osC_Language, $osC_Currencies, $osC_ShoppingCart;

      if (isset($_SESSION['prepOrderID'])) {
        $_prep = explode('-', $_SESSION['prepOrderID']);

        if ($_prep[0] == $osC_ShoppingCart->getCartID()) {
          return $_prep[1]; // order_id
        } else {
          if (osC_Order::getStatusID($_prep[1]) === 4) {
            osC_Order::remove($_prep[1]);
          }
        }
      }

      $Qorder = $osC_Database->query('insert into :table_orders (customers_id, customers_name, customers_company, customers_street_address, customers_suburb, customers_city, customers_postcode, customers_state, customers_country, customers_telephone, customers_email_address, customers_address_format_id, customers_ip_address, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_address_format_id, billing_name, billing_company, billing_street_address, billing_suburb, billing_city, billing_postcode, billing_state, billing_country, billing_address_format_id, payment_method, payment_module, cc_type, cc_owner, cc_number, cc_expires, date_purchased, orders_status, currency, currency_value) values (:customers_id, :customers_name, :customers_company, :customers_street_address, :customers_suburb, :customers_city, :customers_postcode, :customers_state, :customers_country, :customers_telephone, :customers_email_address, :customers_address_format_id, :customers_ip_address, :delivery_name, :delivery_company, :delivery_street_address, :delivery_suburb, :delivery_city, :delivery_postcode, :delivery_state, :delivery_country, :delivery_address_format_id, :billing_name, :billing_company, :billing_street_address, :billing_suburb, :billing_city, :billing_postcode, :billing_state, :billing_country, :billing_address_format_id, :payment_method, :payment_module, :cc_type, :cc_owner, :cc_number, :cc_expires, now(), :orders_status, :currency, :currency_value)');
      $Qorder->bindTable(':table_orders', TABLE_ORDERS);
      $Qorder->bindInt(':customers_id', $osC_Customer->getID());
      $Qorder->bindValue(':customers_name', $osC_Customer->getName());
      $Qorder->bindValue(':customers_company', '' /*$order->customer['company']*/);
      $Qorder->bindValue(':customers_street_address', '' /*$order->customer['street_address']*/);
      $Qorder->bindValue(':customers_suburb', '' /*$order->customer['suburb']*/);
      $Qorder->bindValue(':customers_city', '' /*$order->customer['city']*/);
      $Qorder->bindValue(':customers_postcode', '' /*$order->customer['postcode']*/);
      $Qorder->bindValue(':customers_state', '' /*$order->customer['state']*/);
      $Qorder->bindValue(':customers_country', '' /*$order->customer['country']['title']*/);
      $Qorder->bindValue(':customers_telephone', '' /*$order->customer['telephone']*/);
      $Qorder->bindValue(':customers_email_address', $osC_Customer->getEmailAddress());
      $Qorder->bindInt(':customers_address_format_id', $osC_Customer->getDefaultAddressID());
      $Qorder->bindValue(':customers_ip_address', tep_get_ip_address());
      $Qorder->bindValue(':delivery_name', $osC_ShoppingCart->getShippingAddress('firstname') . ' ' . $osC_ShoppingCart->getShippingAddress('lastname'));
      $Qorder->bindValue(':delivery_company', $osC_ShoppingCart->getShippingAddress('company'));
      $Qorder->bindValue(':delivery_street_address', $osC_ShoppingCart->getShippingAddress('street_address'));
      $Qorder->bindValue(':delivery_suburb', $osC_ShoppingCart->getShippingAddress('suburb'));
      $Qorder->bindValue(':delivery_city', $osC_ShoppingCart->getShippingAddress('city'));
      $Qorder->bindValue(':delivery_postcode', $osC_ShoppingCart->getShippingAddress('postcode'));
      $Qorder->bindValue(':delivery_state', $osC_ShoppingCart->getShippingAddress('state'));
      $Qorder->bindValue(':delivery_country', $osC_ShoppingCart->getShippingAddress('country_title'));
      $Qorder->bindInt(':delivery_address_format_id', $osC_ShoppingCart->getShippingAddress('format_id'));
      $Qorder->bindValue(':billing_name', $osC_ShoppingCart->getBillingAddress('firstname') . ' ' . $osC_ShoppingCart->getBillingAddress('lastname'));
      $Qorder->bindValue(':billing_company', $osC_ShoppingCart->getBillingAddress('company'));
      $Qorder->bindValue(':billing_street_address', $osC_ShoppingCart->getBillingAddress('street_address'));
      $Qorder->bindValue(':billing_suburb', $osC_ShoppingCart->getBillingAddress('suburb'));
      $Qorder->bindValue(':billing_city', $osC_ShoppingCart->getBillingAddress('city'));
      $Qorder->bindValue(':billing_postcode', $osC_ShoppingCart->getBillingAddress('postcode'));
      $Qorder->bindValue(':billing_state', $osC_ShoppingCart->getBillingAddress('state'));
      $Qorder->bindValue(':billing_country', $osC_ShoppingCart->getBillingAddress('country_title'));
      $Qorder->bindInt(':billing_address_format_id', $osC_ShoppingCart->getBillingAddress('format_id'));
      $Qorder->bindValue(':payment_method', $osC_ShoppingCart->getBillingMethod('title'));
      $Qorder->bindValue(':payment_module', $GLOBALS['osC_Payment_' . $osC_ShoppingCart->getBillingMethod('id')]->getCode());
      $Qorder->bindValue(':cc_type', '' /*$order->info['cc_type']*/);
      $Qorder->bindValue(':cc_owner', '' /*$order->info['cc_owner']*/);
      $Qorder->bindValue(':cc_number', '' /*$order->info['cc_number']*/);
      $Qorder->bindValue(':cc_expires', '' /*$order->info['cc_expires']*/);
      $Qorder->bindInt(':orders_status', 4);
      $Qorder->bindValue(':currency', $osC_Currencies->getCode());
      $Qorder->bindValue(':currency_value', $osC_Currencies->value($osC_Currencies->getCode()));
      $Qorder->execute();

      $insert_id = $osC_Database->nextID();

      foreach ($osC_ShoppingCart->getOrderTotals() as $module) {
        $Qtotals = $osC_Database->query('insert into :table_orders_total (orders_id, title, text, value, class, sort_order) values (:orders_id, :title, :text, :value, :class, :sort_order)');
        $Qtotals->bindTable(':table_orders_total', TABLE_ORDERS_TOTAL);
        $Qtotals->bindInt(':orders_id', $insert_id);
        $Qtotals->bindValue(':title', $module['title']);
        $Qtotals->bindValue(':text', $module['text']);
        $Qtotals->bindValue(':value', $module['value']);
        $Qtotals->bindValue(':class', $module['code']);
        $Qtotals->bindInt(':sort_order', $module['sort_order']);
        $Qtotals->execute();
      }

      $Qstatus = $osC_Database->query('insert into :table_orders_status_history (orders_id, orders_status_id, date_added, customer_notified, comments) values (:orders_id, :orders_status_id, now(), :customer_notified, :comments)');
      $Qstatus->bindTable(':table_orders_status_history', TABLE_ORDERS_STATUS_HISTORY);
      $Qstatus->bindInt(':orders_id', $insert_id);
      $Qstatus->bindInt(':orders_status_id', 4);
      $Qstatus->bindInt(':customer_notified', '0');
      $Qstatus->bindValue(':comments', (isset($_SESSION['comments']) ? $_SESSION['comments'] : ''));
      $Qstatus->execute();

      foreach ($osC_ShoppingCart->getProducts() as $products) {
        $Qproducts = $osC_Database->query('insert into :table_orders_products (orders_id, products_id, products_model, products_name, products_price, final_price, products_tax, products_quantity) values (:orders_id, :products_id, :products_model, :products_name, :products_price, :final_price, :products_tax, :products_quantity)');
        $Qproducts->bindTable(':table_orders_products', TABLE_ORDERS_PRODUCTS);
        $Qproducts->bindInt(':orders_id', $insert_id);
        $Qproducts->bindInt(':products_id', tep_get_prid($products['id']));
        $Qproducts->bindValue(':products_model', '' /*$products['model']*/);
        $Qproducts->bindValue(':products_name', $products['name']);
        $Qproducts->bindValue(':products_price', $products['price']);
        $Qproducts->bindValue(':final_price', $products['final_price']);
        $Qproducts->bindValue(':products_tax', '' /*$products['tax']*/);
        $Qproducts->bindInt(':products_quantity', $products['quantity']);
        $Qproducts->execute();

        $order_products_id = $osC_Database->nextID();

        if ($osC_ShoppingCart->hasAttributes($products['id'])) {
          foreach ($osC_ShoppingCart->getAttributes($products['id']) as $attributes) {
            if (DOWNLOAD_ENABLED == '1') {
              $Qattributes = $osC_Database->query('select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount, pad.products_attributes_filename from :table_products_options popt, :table_products_options_values poval, :table_products_attributes pa left join :table_products_attributes_download pad on (pa.products_attributes_id = pad.products_attributes_id) where pa.products_id = :products_id and pa.options_id = :options_id and pa.options_id = popt.products_options_id and pa.options_values_id = :options_values_id and pa.options_values_id = poval.products_options_values_id and popt.language_id = :popt_language_id and poval.language_id = :poval_language_id');
              $Qattributes->bindTable(':table_products_options', TABLE_PRODUCTS_OPTIONS);
              $Qattributes->bindTable(':table_products_options_values', TABLE_PRODUCTS_OPTIONS_VALUES);
              $Qattributes->bindTable(':table_products_attributes', TABLE_PRODUCTS_ATTRIBUTES);
              $Qattributes->bindTable(':table_products_attributes_download', TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD);
              $Qattributes->bindInt(':products_id', $products['id']);
              $Qattributes->bindInt(':options_id', $attributes['options_id']);
              $Qattributes->bindInt(':options_values_id', $attributes['options_values_id']);
              $Qattributes->bindInt(':popt_language_id', $osC_Language->getID());
              $Qattributes->bindInt(':poval_language_id', $osC_Language->getID());
            } else {
              $Qattributes = $osC_Database->query('select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from :table_products_options popt, :table_products_options_values poval, :table_products_attributes pa where pa.products_id = :products_id and pa.options_id = :options_id and pa.options_id = popt.products_options_id and pa.options_values_id = :options_values_id and pa.options_values_id = poval.products_options_values_id and popt.language_id = :popt_language_id and poval.language_id = :poval_language_id');
              $Qattributes->bindTable(':table_products_options', TABLE_PRODUCTS_OPTIONS);
              $Qattributes->bindTable(':table_products_options_values', TABLE_PRODUCTS_OPTIONS_VALUES);
              $Qattributes->bindTable(':table_products_attributes', TABLE_PRODUCTS_ATTRIBUTES);
              $Qattributes->bindInt(':products_id', $products['id']);
              $Qattributes->bindInt(':options_id', $attributes['options_id']);
              $Qattributes->bindInt(':options_values_id', $attributes['options_values_id']);
              $Qattributes->bindInt(':popt_language_id', $osC_Language->getID());
              $Qattributes->bindInt(':poval_language_id', $osC_Language->getID());
            }
            $Qattributes->execute();

            $Qopa = $osC_Database->query('insert into :table_orders_products_attributes (orders_id, orders_products_id, products_options, products_options_values, options_values_price, price_prefix) values (:orders_id, :orders_products_id, :products_options, :products_options_values, :options_values_price, :price_prefix)');
            $Qopa->bindTable(':table_orders_products_attributes', TABLE_ORDERS_PRODUCTS_ATTRIBUTES);
            $Qopa->bindInt(':orders_id', $insert_id);
            $Qopa->bindInt(':orders_products_id', $order_products_id);
            $Qopa->bindValue(':products_options', $Qattributes->value('products_options_name'));
            $Qopa->bindValue(':products_options_values', $Qattributes->value('products_options_values_name'));
            $Qopa->bindValue(':options_values_price', $Qattributes->value('options_values_price'));
            $Qopa->bindValue(':price_prefix', $Qattributes->value('price_prefix'));
            $Qopa->execute();

            if ((DOWNLOAD_ENABLED == '1') && (strlen($Qattributes->value('products_attributes_filename')) > 0)) {
              $Qopd = $osC_Database->query('insert into :table_orders_products_download (orders_id, orders_products_id, orders_products_filename, download_maxdays, download_count) values (:orders_id, :orders_products_id, :orders_products_filename, :download_maxdays, :download_count)');
              $Qopd->bindTable(':table_orders_products_download', TABLE_ORDERS_PRODUCTS_DOWNLOAD);
              $Qopd->bindInt(':orders_id', $insert_id);
              $Qopd->bindInt(':orders_products_id', $order_products_id);
              $Qopd->bindValue(':orders_products_filename', $Qattributes->value('products_attributes_filename'));
              $Qopd->bindValue(':download_maxdays', $Qattributes->value('products_attributes_maxdays'));
              $Qopd->bindValue(':download_count', $Qattributes->value('products_attributes_maxcount'));
              $Qopd->execute();
            }
          }
        }
      }

      $_SESSION['prepOrderID'] = $osC_ShoppingCart->getCartID() . '-' . $insert_id;

      return $insert_id;
    }

    function process($order_id, $status_id = '') {
      global $osC_Database;

      if (empty($status_id) || (is_numeric($status_id) === false)) {
        $status_id = DEFAULT_ORDERS_STATUS_ID;
      }

      $Qstatus = $osC_Database->query('insert into :table_orders_status_history (orders_id, orders_status_id, date_added, customer_notified, comments) values (:orders_id, :orders_status_id, now(), :customer_notified, :comments)');
      $Qstatus->bindTable(':table_orders_status_history', TABLE_ORDERS_STATUS_HISTORY);
      $Qstatus->bindInt(':orders_id', $order_id);
      $Qstatus->bindInt(':orders_status_id', $status_id);
      $Qstatus->bindInt(':customer_notified', (SEND_EMAILS == '1') ? '1' : '0');
      $Qstatus->bindValue(':comments', '');
      $Qstatus->execute();

      $Qupdate = $osC_Database->query('update :table_orders set orders_status = :orders_status where orders_id = :orders_id');
      $Qupdate->bindTable(':table_orders', TABLE_ORDERS);
      $Qupdate->bindInt(':orders_status', $status_id);
      $Qupdate->bindInt(':orders_id', $order_id);
      $Qupdate->execute();

      $Qproducts = $osC_Database->query('select products_id, products_quantity from :table_orders_products where orders_id = :orders_id');
      $Qproducts->bindTable(':table_orders_products', TABLE_ORDERS_PRODUCTS);
      $Qproducts->bindInt(':orders_id', $order_id);
      $Qproducts->execute();

      while ($Qproducts->next()) {
        if (STOCK_LIMITED == '1') {

/********** HPDL ; still uses logic from the shopping cart class
          if (DOWNLOAD_ENABLED == '1') {
            $Qstock = $osC_Database->query('select products_quantity, pad.products_attributes_filename from :table_products p left join :table_products_attributes pa on (p.products_id = pa.products_id) left join :table_products_attributes_download pad on (pa.products_attributes_id = pad.products_attributes_id) where p.products_id = :products_id');
            $Qstock->bindTable(':table_products', TABLE_PRODUCTS);
            $Qstock->bindTable(':table_products_attributes', TABLE_PRODUCTS_ATTRIBUTES);
            $Qstock->bindTable(':table_products_attributes_download', TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD);
            $Qstock->bindInt(':products_id', $Qproducts->valueInt('products_id'));

// Will work with only one option for downloadable products otherwise, we have to build the query dynamically with a loop
            if ($osC_ShoppingCart->hasAttributes($products['id'])) {
              $products_attributes = $osC_ShoppingCart->getAttributes($products['id']);
              $products_attributes = array_shift($products_attributes);

              $Qstock->appendQuery('and pa.options_id = :options_id and pa.options_values_id = :options_values_id');
              $Qstock->bindInt(':options_id', $products_attributes['options_id']);
              $Qstock->bindInt(':options_values_id', $products_attributes['options_values_id']);
            }
          } else {
************/
            $Qstock = $osC_Database->query('select products_quantity from :table_products where products_id = :products_id');
            $Qstock->bindTable(':table_products', TABLE_PRODUCTS);
            $Qstock->bindInt(':products_id', $Qproducts->valueInt('products_id'));
// HPDL          }

          $Qstock->execute();

          if ($Qstock->numberOfRows() > 0) {
            $stock_left = $Qstock->valueInt('products_quantity');

// do not decrement quantities if products_attributes_filename exists
// HPDL            if ((DOWNLOAD_ENABLED == '-1') || ((DOWNLOAD_ENABLED == '1') && (strlen($Qstock->value('products_attributes_filename')) < 1))) {
              $stock_left = $stock_left - $Qproducts->valueInt('products_quantity');

              $Qupdate = $osC_Database->query('update :table_products set products_quantity = :products_quantity where products_id = :products_id');
              $Qupdate->bindTable(':table_products', TABLE_PRODUCTS);
              $Qupdate->bindInt(':products_quantity', $stock_left);
              $Qupdate->bindInt(':products_id', $Qproducts->valueInt('products_id'));
              $Qupdate->execute();
// HPDL            }

            if ((STOCK_ALLOW_CHECKOUT == '-1') && ($stock_left < 1)) {
              $Qupdate = $osC_Database->query('update :table_products set products_status = 0 where products_id = :products_id');
              $Qupdate->bindTable(':table_products', TABLE_PRODUCTS);
              $Qupdate->bindInt(':products_id', $Qproducts->valueInt('products_id'));
              $Qupdate->execute();
            }
          }
        }

// Update products_ordered (for bestsellers list)
        $Qupdate = $osC_Database->query('update :table_products set products_ordered = products_ordered + :products_ordered where products_id = :products_id');
        $Qupdate->bindTable(':table_products', TABLE_PRODUCTS);
        $Qupdate->bindInt(':products_ordered', $Qproducts->valueInt('products_quantity'));
        $Qupdate->bindInt(':products_id', $Qproducts->valueInt('products_id'));
        $Qupdate->execute();
      }

      osC_Order::sendEmail($order_id);

      unset($_SESSION['prepOrderID']);
    }

    function sendEmail($id) {
      global $osC_Database, $osC_Language, $osC_Currencies;

      $Qorder = $osC_Database->query('select * from :table_orders where orders_id = :orders_id limit 1');
      $Qorder->bindTable(':table_orders', TABLE_ORDERS);
      $Qorder->bindInt(':orders_id', $id);
      $Qorder->execute();

      if ($Qorder->numberOfRows() === 1) {
        $email_order = STORE_NAME . "\n" .
                       $osC_Language->get('email_order_separator') . "\n" .
                       sprintf($osC_Language->get('email_order_order_number'), $id) . "\n" .
                       sprintf($osC_Language->get('email_order_invoice_url'), tep_href_link(FILENAME_ACCOUNT, 'orders=' . $id, 'SSL', false, true, true)) . "\n" .
                       sprintf($osC_Language->get('email_order_date_ordered'), osC_DateTime::getLong()) . "\n\n" .
                       $osC_Language->get('email_order_products') . "\n" .
                       $osC_Language->get('email_order_separator') . "\n";

        $Qproducts = $osC_Database->query('select orders_products_id, products_model, products_name, final_price, products_tax, products_quantity from :table_orders_products where orders_id = :orders_id order by orders_products_id');
        $Qproducts->bindTable(':table_orders_products', TABLE_ORDERS_PRODUCTS);
        $Qproducts->bindInt(':orders_id', $id);
        $Qproducts->execute();

        while ($Qproducts->next()) {
          $email_order .= $Qproducts->valueInt('products_quantity') . ' x ' . $Qproducts->value('products_name') . ' (' . $Qproducts->value('products_model') . ') = ' . $osC_Currencies->displayPriceWithTaxRate($Qproducts->value('final_price'), $Qproducts->value('products_tax'), $Qproducts->valueInt('products_quantity'), $Qorder->value('currency'), $Qorder->value('currency_value')) . "\n";

          $Qattributes = $osC_Database->query('select products_options, products_options_values from :table_orders_products_attributes where orders_id = :orders_id and orders_products_id = :orders_products_id order by orders_products_attributes_id');
          $Qattributes->bindTable(':table_orders_products_attributes', TABLE_ORDERS_PRODUCTS_ATTRIBUTES);
          $Qattributes->bindInt(':orders_id', $id);
          $Qattributes->bindInt(':orders_products_id', $Qproducts->valueInt('orders_products_id'));
          $Qattributes->execute();

          while ($Qattributes->next()) {
            $email_order .= "\t" . $Qattributes->value('products_options') . ': ' . $Qattributes->value('products_options_values') . "\n";
          }
        }

        unset($Qproducts);
        unset($Qattributes);

        $email_order .= $osC_Language->get('email_order_separator') . "\n";

        $Qtotals = $osC_Database->query('select title, text from :table_orders_total where orders_id = :orders_id order by sort_order');
        $Qtotals->bindTable(':table_orders_total', TABLE_ORDERS_TOTAL);
        $Qtotals->bindInt(':orders_id', $id);
        $Qtotals->execute();

        while ($Qtotals->next()) {
          $email_order .= strip_tags($Qtotals->value('title') . ' ' . $Qtotals->value('text')) . "\n";
        }

        unset($Qtotals);

        if ( (osc_empty($Qorder->value('delivery_name') === false)) && (osc_empty($Qorder->value('street_address') === false)) ) {
          $address = array('name' => $Qorder->value('delivery_name'),
                           'company' => $Qorder->value('delivery_company'),
                           'street_address' => $Qorder->value('delivery_street_address'),
                           'suburb' => $Qorder->value('delivery_suburb'),
                           'city' => $Qorder->value('delivery_city'),
                           'state' => $Qorder->value('delivery_state'),
                           'country' => $Qorder->value('delivery_country'),
                           'postcode' => $Qorder->value('delivery_postcode'));

          $email_order .= "\n" . $osC_Language->get('email_order_delivery_address') . "\n" .
                          $osC_Language->get('email_order_separator') . "\n" .
                          tep_address_format($Qorder->valueInt('delivery_address_format_id'), $address, false, '', "\n") . "\n";

          unset($address);
        }

        $address = array('name' => $Qorder->value('billing_name'),
                         'company' => $Qorder->value('billing_company'),
                         'street_address' => $Qorder->value('billing_street_address'),
                         'suburb' => $Qorder->value('billing_suburb'),
                         'city' => $Qorder->value('billing_city'),
                         'state' => $Qorder->value('billing_state'),
                         'country' => $Qorder->value('billing_country'),
                         'postcode' => $Qorder->value('billing_postcode'));

        $email_order .= "\n" . $osC_Language->get('email_order_billing_address') . "\n" .
                        $osC_Language->get('email_order_separator') . "\n" .
                        tep_address_format($Qorder->valueInt('billing_address_format_id'), $address, false, '', "\n") . "\n\n";

        unset($address);

        $Qstatus = $osC_Database->query('select orders_status_name from :table_orders_status where orders_status_id = :orders_status_id and language_id = :language_id');
        $Qstatus->bindTable(':table_orders_status', TABLE_ORDERS_STATUS);
        $Qstatus->bindInt(':orders_status_id', $Qorder->valueInt('orders_status'));
        $Qstatus->bindInt(':language_id', $osC_Language->getID());
        $Qstatus->execute();

        $email_order .= sprintf($osC_Language->get('email_order_status'), $Qstatus->value('orders_status_name')) . "\n" .
                        $osC_Language->get('email_order_separator') . "\n";

        unset($Qstatus);

        $Qstatuses = $osC_Database->query('select date_added, comments from :table_orders_status_history where orders_id = :orders_id and comments != "" order by orders_status_history_id');
        $Qstatuses->bindTable(':table_orders_status_history', TABLE_ORDERS_STATUS_HISTORY);
        $Qstatuses->bindInt(':orders_id', $id);
        $Qstatuses->execute();

        while ($Qstatuses->next()) {
          $email_order .= osC_DateTime::getLong($Qstatuses->value('date_added')) . "\n\t" . wordwrap(str_replace("\n", "\n\t", $Qstatuses->value('comments')), 60, "\n\t", 1) . "\n\n";
        }

        unset($Qstatuses);

//        if (is_object($GLOBALS[$payment])) {
//          $email_order .= $osC_Language->get('email_order_payment_method') . "\n" .
//                          $osC_Language->get('email_order_separator') . "\n";

//          $email_order .= $osC_ShoppingCart->getBillingMethod('title') . "\n\n";
//          if (isset($GLOBALS[$payment]->email_footer)) {
//            $email_order .= $GLOBALS[$payment]->email_footer . "\n\n";
//          }
//        }

        tep_mail($Qorder->value('customers_name'), $Qorder->value('customers_email_address'), $osC_Language->get('email_order_subject'), $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

// send emails to other people
        if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
          tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, $osC_Language->get('email_order_subject'), $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
        }
      }

      unset($Qorder);
    }

    function &getListing($limit = null, $page_keyword = 'page') {
      global $osC_Database, $osC_Customer, $osC_Language;

      $Qorders = $osC_Database->query('select o.orders_id, o.date_purchased, o.delivery_name, o.delivery_country, o.billing_name, o.billing_country, ot.text as order_total, s.orders_status_name from :table_orders o, :table_orders_total ot, :table_orders_status s where o.customers_id = :customers_id and o.orders_id = ot.orders_id and ot.class = "total" and o.orders_status = s.orders_status_id and s.language_id = :language_id order by orders_id desc');
      $Qorders->bindTable(':table_orders', TABLE_ORDERS);
      $Qorders->bindTable(':table_orders_total', TABLE_ORDERS_TOTAL);
      $Qorders->bindTable(':table_orders_status', TABLE_ORDERS_STATUS);
      $Qorders->bindInt(':customers_id', $osC_Customer->getID());
      $Qorders->bindInt(':language_id', $osC_Language->getID());

      if (is_numeric($limit)) {
        $Qorders->setBatchLimit(isset($_GET[$page_keyword]) && is_numeric($_GET[$page_keyword]) ? $_GET[$page_keyword] : 1, $limit);
      }

      $Qorders->execute();

      return $Qorders;
    }

    function &getStatusListing($id = null) {
      global $osC_Database, $osC_Language;

      if ( ($id === null) && isset($this) ) {
        $id = $this->_id;
      }

      $Qstatus = $osC_Database->query('select os.orders_status_name, osh.date_added, osh.comments from :table_orders_status os, :table_orders_status_history osh where osh.orders_id = :orders_id and osh.orders_status_id = os.orders_status_id and os.language_id = :language_id order by osh.date_added');
      $Qstatus->bindTable(':table_orders_status', TABLE_ORDERS_STATUS);
      $Qstatus->bindTable(':table_orders_status_history', TABLE_ORDERS_STATUS_HISTORY);
      $Qstatus->bindInt(':orders_id', $id);
      $Qstatus->bindInt(':language_id', $osC_Language->getID());

      return $Qstatus;
    }

    function getCustomerID($id = null) {
      global $osC_Database;

      if ( ($id === null) && isset($this) ) {
        $id = $this->_id;
      }

      $Qcustomer = $osC_Database->query('select customers_id from :table_orders where orders_id = :orders_id');
      $Qcustomer->bindTable(':table_orders', TABLE_ORDERS);
      $Qcustomer->bindInt(':orders_id', $id);
      $Qcustomer->execute();

      return $Qcustomer->valueInt('customers_id');
    }

    function numberOfEntries() {
      global $osC_Database, $osC_Customer;
      static $total_entries;

      if (is_numeric($total_entries) === false) {
        if ($osC_Customer->isLoggedOn()) {
          $Qorders = $osC_Database->query('select count(*) as total from :table_orders where customers_id = :customers_id');
          $Qorders->bindTable(':table_orders', TABLE_ORDERS);
          $Qorders->bindInt(':customers_id', $osC_Customer->getID());
          $Qorders->execute();

          $total_entries = $Qorders->valueInt('total');
        } else {
          $total_entries = 0;
        }
      }

      return $total_entries;
    }

    function numberOfProducts($id = null) {
      global $osC_Database;

      if ( ($id === null) && isset($this) ) {
        $id = $this->_id;
      }

      $Qproducts = $osC_Database->query('select count(*) as total from :table_orders_products where orders_id = :orders_id');
      $Qproducts->bindTable(':table_orders_products', TABLE_ORDERS_PRODUCTS);
      $Qproducts->bindInt(':orders_id', $id);
      $Qproducts->execute();

      return $Qproducts->valueInt('total');
    }

    function exists($id, $customer_id = null) {
      global $osC_Database;

      $Qorder = $osC_Database->query('select orders_id from :table_orders where orders_id = :orders_id');

      if (isset($customer_id) && is_numeric($customer_id)) {
        $Qorder->appendQuery('and customers_id = :customers_id');
        $Qorder->bindInt(':customers_id', $customer_id);
      }

      $Qorder->appendQuery('limit 1');
      $Qorder->bindTable(':table_orders', TABLE_ORDERS);
      $Qorder->bindInt(':orders_id', $id);
      $Qorder->execute();

      return ($Qorder->numberOfRows() === 1);
    }

    function query($order_id) {
      global $osC_Database, $osC_Language;

      $Qorder = $osC_Database->query('select customers_id, customers_name, customers_company, customers_street_address, customers_suburb, customers_city, customers_postcode, customers_state, customers_country, customers_telephone, customers_email_address, customers_address_format_id, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_address_format_id, billing_name, billing_company, billing_street_address, billing_suburb, billing_city, billing_postcode, billing_state, billing_country, billing_address_format_id, payment_method, cc_type, cc_owner, cc_number, cc_expires, currency, currency_value, date_purchased, orders_status, last_modified from :table_orders where orders_id = :orders_id');
      $Qorder->bindTable(':table_orders', TABLE_ORDERS);
      $Qorder->bindInt(':orders_id', $order_id);
      $Qorder->execute();

      $Qtotals = $osC_Database->query('select title, text, class from :table_orders_total where orders_id = :orders_id order by sort_order');
      $Qtotals->bindTable(':table_orders_total', TABLE_ORDERS_TOTAL);
      $Qtotals->bindInt(':orders_id', $order_id);
      $Qtotals->execute();

      $shipping_method_string = '';
      $order_total_string = '';

      while ($Qtotals->next()) {
        $this->totals[] = array('title' => $Qtotals->value('title'),
                                'text' => $Qtotals->value('text'));

        if ($Qtotals->value('class') == 'shipping') {
          $shipping_method_string = strip_tags($Qtotals->value('title'));

          if (substr($shipping_method_string, -1) == ':') {
            $shipping_method_string = substr($Qtotals->value('title'), 0, -1);
          }
        }

        if ($Qtotals->value('class') == 'total') {
          $order_total_string = strip_tags($Qtotals->value('text'));
        }
      }

      $Qstatus = $osC_Database->query('select orders_status_name from :table_orders_status where orders_status_id = :orders_status_id and language_id = :language_id');
      $Qstatus->bindTable(':table_orders_status', TABLE_ORDERS_STATUS);
      $Qstatus->bindInt(':orders_status_id', $Qorder->valueInt('orders_status'));
      $Qstatus->bindInt(':language_id', $osC_Language->getID());
      $Qstatus->execute();

      $this->info = array('currency' => $Qorder->value('currency'),
                          'currency_value' => $Qorder->value('currency_value'),
                          'payment_method' => $Qorder->value('payment_method'),
                          'cc_type' => $Qorder->value('cc_type'),
                          'cc_owner' => $Qorder->valueProtected('cc_owner'),
                          'cc_number' => $Qorder->valueProtected('cc_number'),
                          'cc_expires' => $Qorder->valueProtected('cc_expires'),
                          'date_purchased' => $Qorder->value('date_purchased'),
                          'orders_status' => $Qstatus->value('orders_status_name'),
                          'last_modified' => $Qorder->value('last_modified'),
                          'total' => $order_total_string,
                          'shipping_method' => $shipping_method_string);

      $this->customer = array('id' => $Qorder->valueInt('customers_id'),
                              'name' => $Qorder->valueProtected('customers_name'),
                              'company' => $Qorder->valueProtected('customers_company'),
                              'street_address' => $Qorder->valueProtected('customers_street_address'),
                              'suburb' => $Qorder->valueProtected('customers_suburb'),
                              'city' => $Qorder->valueProtected('customers_city'),
                              'postcode' => $Qorder->valueProtected('customers_postcode'),
                              'state' => $Qorder->valueProtected('customers_state'),
                              'country' => $Qorder->valueProtected('customers_country'),
                              'format_id' => $Qorder->valueInt('customers_address_format_id'),
                              'telephone' => $Qorder->valueProtected('customers_telephone'),
                              'email_address' => $Qorder->valueProtected('customers_email_address'));

      $this->delivery = array('name' => $Qorder->valueProtected('delivery_name'),
                              'company' => $Qorder->valueProtected('delivery_company'),
                              'street_address' => $Qorder->valueProtected('delivery_street_address'),
                              'suburb' => $Qorder->valueProtected('delivery_suburb'),
                              'city' => $Qorder->valueProtected('delivery_city'),
                              'postcode' => $Qorder->valueProtected('delivery_postcode'),
                              'state' => $Qorder->valueProtected('delivery_state'),
                              'country' => $Qorder->valueProtected('delivery_country'),
                              'format_id' => $Qorder->valueInt('delivery_address_format_id'));

      if (empty($this->delivery['name']) && empty($this->delivery['street_address'])) {
        $this->delivery = false;
      }

      $this->billing = array('name' => $Qorder->valueProtected('billing_name'),
                             'company' => $Qorder->valueProtected('billing_company'),
                             'street_address' => $Qorder->valueProtected('billing_street_address'),
                             'suburb' => $Qorder->valueProtected('billing_suburb'),
                             'city' => $Qorder->valueProtected('billing_city'),
                             'postcode' => $Qorder->valueProtected('billing_postcode'),
                             'state' => $Qorder->valueProtected('billing_state'),
                             'country' => $Qorder->valueProtected('billing_country'),
                             'format_id' => $Qorder->valueInt('billing_address_format_id'));

      $Qproducts = $osC_Database->query('select orders_products_id, products_id, products_name, products_model, products_price, products_tax, products_quantity, final_price from :table_orders_products where orders_id = :orders_id');
      $Qproducts->bindTable(':table_orders_products', TABLE_ORDERS_PRODUCTS);
      $Qproducts->bindInt(':orders_id', $order_id);
      $Qproducts->execute();

      $index = 0;

      while ($Qproducts->next()) {
        $subindex = 0;

        $this->products[$index] = array('qty' => $Qproducts->valueInt('products_quantity'),
                                        'id' => $Qproducts->valueInt('products_id'),
                                        'name' => $Qproducts->value('products_name'),
                                        'model' => $Qproducts->value('products_model'),
                                        'tax' => $Qproducts->value('products_tax'),
                                        'price' => $Qproducts->value('products_price'),
                                        'final_price' => $Qproducts->value('final_price'));

        $Qattributes = $osC_Database->query('select products_options, products_options_values, options_values_price, price_prefix from :table_orders_products_attributes where orders_id = :orders_id and orders_products_id = :orders_products_id');
        $Qattributes->bindTable(':table_orders_products_attributes', TABLE_ORDERS_PRODUCTS_ATTRIBUTES);
        $Qattributes->bindInt(':orders_id', $order_id);
        $Qattributes->bindInt(':orders_products_id', $Qproducts->valueInt('orders_products_id'));
        $Qattributes->execute();

        if ($Qattributes->numberOfRows()) {
          while ($Qattributes->next()) {
            $this->products[$index]['attributes'][$subindex] = array('option' => $Qattributes->value('products_options'),
                                                                     'value' => $Qattributes->value('products_options_values'),
                                                                     'prefix' => $Qattributes->value('price_prefix'),
                                                                     'price' => $Qattributes->value('options_values_price'));

            $subindex++;
          }
        }

        $this->info['tax_groups']["{$this->products[$index]['tax']}"] = '1';

        $index++;
      }
    }
  }
?>
