<?php

/**
 * Implements hook_entity_info().
 * Provides the basic Itonics segment entity info
 * @return array
 */
function itonics_training_entity_info() {
    $return = array(
      'itonics_training' => array(
        'label' => t('Itonics Training Entity'),
        'controller class' => 'ItonicsTrainingController',
        'base table' => 'products',
        'uri callback' => 'itonics_training_uri',
        'fieldable' => TRUE,
        'translation' => array(
          'locale' => TRUE,
        ),
        'entity keys' => array(
          'id' => 'id',
        ),
      ),
    );

    return $return;
}