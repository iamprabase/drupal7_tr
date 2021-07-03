<?php

/** Shows the form 
 * for create edit operation 
*/
function itonics_training_form($form, &$form_state, $instance, $current_categories = array()) {
    drupal_add_css(drupal_get_path('module', 'itonics_training') . '/assets/css/custom.css');
    // for ajax redirections
    drupal_add_js(drupal_get_path('module', 'itonics_training') . '/assets/js/custom.js');

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
        '#default_value' => $instance ? $instance->title : "",
    );
    
    $form['category'] = array(
        '#type' => 'select',
        '#title' => t('Categories'),
        '#options' => $categories,
        '#multiple' => true,
        '#id' => 'input_category',
        '#suffix' => '<div class="validation-error-msg" id="input-category-error-msg"></div>',
        '#default_value' => $instance ? $current_categories : "",
    );
    
    $type = array('Active' => t('Active'), 'Inactive' => t('Inactive'));
    $form['type'] = array(
        '#type' => 'radios',
        '#title' => t('Type'),
        '#options' => $type,
        '#id' => 'input_type',
        '#suffix' => '<div class="validation-error-msg" id="input-type-error-msg"></div>',
        '#default_value' => $instance ? $instance->type : 'Active',
    );

    $form['owner_email'] = array(
        '#title' => t('Email'),
        '#type' => 'textfield',
        '#id' => 'input_owner_email',
        '#suffix' => '<div class="validation-error-msg" id="input-owner_email-error-msg"></div>',
        '#default_value' => $instance ? $instance->owner_email : "",
    );

    $form['summary'] = array(
        '#title' => t('Summary'),
        '#type' => 'textarea',
        '#rows' => 3,
        '#id' => 'input_summary',
        '#suffix' => '<div class="validation-error-msg" id="input-summary-error-msg"></div>',
        '#description' => t('A short product summary'),
        '#default_value' => $instance ? $instance->summary : "",
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
        '#default_value' => $instance ? $instance->description : "",
    );

    $form['image'] = array(
        '#title' => t('Choose an image'),
        '#type' => 'file',
        '#description' => t('File must be of given types: png gif jpg jpeg'),
        '#id' => '#input_image',
        '#suffix' => '<span class="validation-error-msg" id="input-image-error-msg"></span>',
    );

    if ($instance) {
        $form['id'] = array('#type' => 'hidden', '#value' => $instance->id);
        if($instance->image) {
            if (file_exists($instance->image)) {
                $image = '<div><label>Current Image</label><img src="' . file_create_url($instance->image) . '" width="200" height="200" /></div>';
                $form['current_image'] = array('#markup' => $image);
            }
        }

    }

    // Provide a submit button.
    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => 'Submit',
        '#ajax' => array(
            'callback' => 'itonics_training_form_ajax_submit',
            'wrapper' => '',
            'event' => 'click'
        ),
    );

    return $form;
}

function itonics_training_form_validate($form, &$form_state) {
    $form_elements = $form_state['values'];
    $title = $form_elements['title'];
    $owner_email = $form_elements['owner_email'];
    $type = $form_elements['type'];
    $summary = $form_elements['summary'];
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
    
    $ajax_response = array();
    
    $product_id = array_key_exists('id', $form_elements) ? $form_elements['id'] : null;
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

    if ($product_id) {
        $product_instance = ItonicsTraining::findById($product_id, array('id', 'image'));
        $form_state['values']['image'] = $product_instance->image;
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
        
        $form_elements = $form_state['values'];
        $product_id = array_key_exists('id', $form_elements) ? $form_elements['id'] : null;
        
        $product = new ItonicsTraining(
                        $product_id,
                        $form_elements['title'], 
                        $form_elements['type'], 
                        $form_elements['owner_email'], 
                        $form_elements['summary'], 
                        $form_elements['description']['value'], 
                        $form_elements['image']
                    );
        $inserted_id = $product->updateOrCreate();
        if ($product_id) {    
            $product->dettach();
            $msg = Product_Updated_Msg;
        } else {
            $msg = Product_Created_Msg;
        }

        $product->attach($form_elements['category'], $inserted_id);
        
        drupal_set_message(t($msg));
        $ajax_response[] = ajax_command_invoke(null, 'redirect', array(INDEX));
    } catch (Exception $e) {
        $transaction->rollback();
        watchdog_exception('my_type', $e);
        drupal_set_message(t(Error_Msg));
        $ajax_response[] = ajax_command_invoke(null, 'redirect', array(INDEX));
    }

    print ajax_render($ajax_response);
    drupal_exit();
}