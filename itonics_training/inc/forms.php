<?php

/** Shows the form 
 * for create edit operation 
*/
function itonics_training_form($form, &$form_state, $instance, $current_categories = array()) {
    
    $fetch_categories = db_select('categories', 'n')->fields('n')
                        ->execute()->fetchAll();
    $categories = array();
    foreach ($fetch_categories as $category) {
        $categories[$category->id] = t($category->title);
    }

    $form['title'] = array(
        '#type' => 'textfield',
        '#title' => t('Product Name'),
        '#id' => 'input_title',
        '#suffix' => '<div class="validation-error-msg" id="input-title-error-msg"></div>',
        '#default_value' => $instance->title,
    );
    
    $form['expiry_date'] = array(
        '#title' => t('Expiry Date'),
        '#type' => 'date_popup',
        '#date_format' => 'Y-m-d',
        '#suffix' => '<div class="validation-error-msg" id="input-expiry_date-error-msg"></div>',
        '#default_value' => $instance->expiry_date,
    );
    
    $form['category'] = array(
        '#type' => 'select',
        '#title' => t('Categories'),
        '#options' => $categories,
        '#multiple' => true,
        '#id' => 'input_category',
        '#suffix' => '<div class="validation-error-msg" id="input-category-error-msg"></div>',
        '#default_value' => $current_categories,
    );
    
    $type = array('Active' => t('Active'), 'Inactive' => t('Inactive'));
    $form['type'] = array(
        '#type' => 'radios',
        '#title' => t('Type'),
        '#options' => $type,
        '#id' => 'input_type',
        '#suffix' => '<div class="validation-error-msg" id="input-type-error-msg"></div>',
        '#default_value' => $instance->type,
    );

    $form['owner_email'] = array(
        '#title' => t('Email'),
        '#type' => 'textfield',
        '#id' => 'input_owner_email',
        '#suffix' => '<div class="validation-error-msg" id="input-owner_email-error-msg"></div>',
        '#default_value' => $instance->owner_email,
    );

    $form['summary'] = array(
        '#title' => t('Summary'),
        '#type' => 'textarea',
        '#rows' => 3,
        '#id' => 'input_summary',
        '#suffix' => '<div class="validation-error-msg" id="input-summary-error-msg"></div>',
        '#description' => t('A short product summary'),
        '#default_value' => $instance->summary,
    );

    $form['description'] = array(
        '#title' => t('Description'),
        '#type' => 'text_format',
        '#rows' => 5,
        '#description' => t('A short product description'),
        '#format' => 'filtered_html',
        '#resizable' => false,
        '#id' => '#input_description',
        '#suffix' => '<div class="validation-error-msg" id="input-description-error-msg"></div>',
        '#default_value' => $instance->description,
    );

    $form['image'] = array(
        '#title' => t('Choose an image'),
        '#type' => 'file',//managed_file //file usage addd
        '#description' => t('File must be of given types: png gif jpg jpeg'),
        '#id' => '#input_image',
        '#suffix' => '<span class="validation-error-msg" id="input-image-error-msg"></span>',
    );

    $form['id'] = array('#type' => 'hidden', '#value' => $instance->id);
    if ($instance && $instance->image) {
        if (file_exists($instance->image)) {
            $image = '<div><label>Current Image</label><img src="' . file_create_url($instance->image) . '" width="200" height="200" /></div>';
            $form['current_image'] = array('#markup' => $image);
        }
        
    }
    
    field_attach_form('itonics_training', $instance, $form, $form_state);
    
    // Provide a submit button.
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => 'Submit',
        '#ajax' => array(
            'callback' => 'itonics_training_form_ajax_submit',
            'wrapper' => '',
            'event' => 'click'
        ),
        '#weight' => 10
    );

    return $form;
}

/**
*file_usage
*hook_entity_insert
*hook_entity_update
*hook_entity_delete
*hook_entity_presave 
*/

function itonics_training_form_alter(&$form, &$form_state, $form_id) {    
    if($form_id == 'itonics_training_form') {
        $form['price']['#weight'] = 8;
        $form['price']['#suffix'] = '<div class="validation-error-msg" id="input-price-error-msg"></div>';
        $form['price']['#id'] = 'price';
        $form['additional_image']['#weight'] = 9;
        
        return $form;
    }
}

function itonics_training_form_validate($form, &$form_state) {
    $form_elements = $form_state['values'];
    $title = $form_elements['title'];
    $owner_email = $form_elements['owner_email'];
    $type = $form_elements['type'];
    $summary = $form_elements['summary'];
    $expiry_date = $form_elements['expiry_date'];
    $price = $form_elements['price']['und'][0]['value'];
    $form_errors = array();

    if($title == "") {
        $form_errors['title'] = t('Product name is required.');
    }
    if (strlen($title) > 191) {
        $form_errors['title'] = t('Maximum length of title is 10 characters.');
    }

    if($owner_email == "") {
        $form_errors['owner_email'] = t('Email field is required.');
    }
    if (!filter_var($owner_email, FILTER_VALIDATE_EMAIL)) {
        $form_errors['owner_email'] = t('Please enter a valid email address.');
    }

    if (!in_array($type, array('Active', 'Inactive'))) {
        $form_errors['type'] = t('Invalid value.');
    }

    if (strlen($summary) > 255) {
        $form_errors['title'] = t('Maximum length of summary is 255 characters.');
    }

    if(!$price) {
        $form_errors['price'] = t('Price field is required.');
    }
    if($price && !preg_match('#\d+(?:\.\d{1,2})?#', $price)) {
        $form_errors['price'] = t('Invalid format.');
    }

    if($expiry_date && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $expiry_date)) {
        $form_errors['expiry_date'] = t('Invalid date format.');
    }
    
    $ajax_response = array();   

    $product_id = array_key_exists('id', $form_elements) ? $form_elements['id'] : NULL;
    if (!empty($form_errors)) {
        $ajax_response[] = array("command" => 'loadDefaultMrd');
        
        foreach ($form_elements as $row => $value) {
            if (isset($form_errors[$row])) {
                $ajax_response[] = ajax_command_invoke('#input_' . $row, 'addClass', array('error'));
                $ajax_response[] = ajax_command_html('#input-' . $row . '-error-msg', $form_errors[$row]);
            } else {
                $ajax_response[] = ajax_command_invoke('#input_' . $row, 'removeClass', array('error'));
                $ajax_response[] = ajax_command_html('#input-' . $row . '-error-msg', '');
            }
        }
    }

    if(!empty($ajax_response)) {
        print ajax_render($ajax_response);
        drupal_exit();
    }

    if ($product_id) {
        $product_instance = entity_get_controller('itonics_training')->load_products(array($product_id), array('id' => $product_id));

        $form_state['values']['image'] = $product_instance[$product_id]->image;
    }

    if (!empty($_FILES['files']['tmp_name']['image'])) {
        $validators = array(
            'file_validate_is_image' => array(),
            'file_validate_size' => array(0),
            'file_validate_extensions' => array('png gif jpg jpeg'),
        );

        try {
            $file = file_save_upload('image', $validators, 'public://', $replace = 0);
        } catch (Exception $e) {
            watchdog_exception('my_type', $e);
            $ajax_response = array();
            $ajax_response[] = ajax_command_invoke('#input_image', 'addClass', array('error'));
            $ajax_response[] = ajax_command_html('#input-image-error-msg', t($e->getMessage()));
        }


        if (!empty($file)) {

            $form_state['values']['image'] = $file->uri;
            if ($product_id) {
                // Delete Current Image If image is updated
                if ($product_instance->image) {
                    if (file_exists($product_instance->image)) {
                        drupal_unlink($product_instance->image);
                    }
                }
            }
        } else {
            $ajax_response = array();
            $ajax_response[] = ajax_command_invoke('#input_image', 'addClass', array('error'));
            $ajax_response[] = ajax_command_html('#input-image-error-msg', t('Image cannot be uploaded. Please try again.'));
        }
    }

    if(!empty($ajax_response)) {
        print ajax_render($ajax_response);
        drupal_exit();
    }
}

function itonics_training_form_ajax_submit($form, &$form_state) {
    $transaction = db_transaction();
    try {
        $ajax_response = array();
        $ajax_response[] = ajax_command_html('.validation-error-msg', '');
        
        $form_elements = (object)$form_state['values'];
        $form_elements->description = $form_state['values']['description']['value'];
        
        $fie = field_attach_submit('itonics_training', $form_elements, $form, $form_state);

        $product_instance = entity_get_controller('itonics_training')->save($form_elements);
        
        if ($form_state['values']['id']) {    
            $msg = Product_Updated_Msg;
        } else {
            $msg = Product_Created_Msg;
        }
        
        drupal_set_message(t($msg));
        $ajax_response[] = ajax_command_invoke(NULL, 'redirect', array(INDEX));
    } catch (Exception $e) {
        $transaction->rollback();
        watchdog_exception('my_type', $e, $e->getMessage());
        drupal_set_message(t(Error_Msg), 'error');
        $ajax_response[] = ajax_command_invoke(NULL, 'redirect', array(INDEX));
    }

    print ajax_render($ajax_response);
    drupal_exit();
}