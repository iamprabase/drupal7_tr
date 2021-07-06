<?php

class ItonicsTrainingController extends DrupalDefaultEntityController {

    /**
     * Create and return a new entity_products entity.
    */
    public function create() {
        $entity = new stdClass();
        $entity->id = NULL;
        $entity->title = '';
        $entity->type = 'Active';
        $entity->owner_email = '';
        $entity->expiry_date = '';
        $entity->summary = '';
        $entity->description = '';
        $entity->image = NULL;

        return $entity;
    }

    /**
     * Saves the custom fields using drupal_write_record().
    */
    public function save(&$entity) {
        module_invoke_all('entity_presave', $entity, 'itonics_training');
        if($entity->id) {
            drupal_write_record('products', $entity, 'id');
            $op = 'update';
            $op_field = 'field_attach_'.$op;
        } else {
            drupal_write_record('products', $entity);
            $op = 'insert';
            $op_field = 'field_attach_'.$op;
            $this->dettach($entity->id);
        }
        $this->attach($entity->category, $entity->id);

        $op_field('itonics_training', (object)$entity);
        module_invoke_all('entity_'.$op, $entity, 'itonics_training');
        
        return $entity;
    }
    
    private function attach($category_ids, $id){
        $category_product_insert_query = db_insert('category_product')
                                        ->fields(array('category_id', 'product_id'));
        foreach ($category_ids as $category) {
            $category_product_insert_query->values(
                array('category_id' => $category, 'product_id' => $id)
            );
        }
        $category_product_insert_query->execute();
        
        return true;
    }

    private function dettach($id){
        db_delete('category_product')
        ->condition('product_id', $id)
        ->execute();
        
        return true;
    }

    /**
     * Delete a single entity.
    *
    * Really a convenience function for deleteMultiple().
    */
    public function delete($entity) {
        $this->deleteMultiple(array($entity));
    }

    /**
     * Delete one or more itonics_training entities.
    *
    * Deletion is unfortunately not supported in the base
    * DrupalDefaultEntityController class.
    *
    * @param array $entities
    *   An array of entity IDs or a single numeric ID.
    */
    public function deleteMultiple($entities) {
        foreach($entities as $entity) {
            $transaction = db_transaction();
            if ($entity->image) {
                if (file_exists($entity->image)) {
                    drupal_unlink($entity->image);
                }

                module_invoke_all('entity_delete', $entity, 'itonics_training');
                field_attach_delete('itonics_training', $entity);
            }
    
            try {
                db_delete('products')->condition('id',  $entity->id)->execute();
                $this->dettach($entity->id);
            } catch (Exception $e) {
                $transaction->rollback();
                watchdog_exception('my_type', $e);
                drupal_set_message(t(Error_Msg), 'error');
            }
        }
    }

    /**
     * Loads product related categories
     * @param int $product id @param array columns to select 
     * @return array categoryids 
     */
    public function load_categories($entity_id, $cols) {
        if(!$entity_id) return NULL;

        $category_ids = db_select('category_product', 'cp')
        ->fields('cp', $cols)
        ->condition('product_id', $entity_id)
        ->execute()->fetchAllAssoc('category_id');
        $categories = array();
        foreach ($category_ids as $current_category) {
            array_push($categories, $current_category->category_id);
        }
        return $categories;
    }

    /**
     * Loads products
     * @param array int $product ids @param array condition to filter 
     * @return array products object 
     */
    public function load_products($product_ids = FALSE, $conditions = array(), $reset = FALSE) {
        
        return entity_load('itonics_training', $product_ids, $conditions, $reset);
    }
}