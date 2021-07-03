<?php 

class ItonicsTrainingController extends DrupalDefaultEntityController { 
    /**
     * Load Product
     *
     * @param type $name
     */
    public static function findById($id, $cols){
        $product = db_select('products', 'p')
                    ->fields('p', $cols)
                    ->condition('id', $id)
                    ->execute()->fetchObject();
        
        return $product ? $product : NULL;  
    }
}