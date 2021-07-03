<?php

class ItonicsTraining{
    private $id;
    private $title;
    private $type;
    private $email;
    private $summary;
    private $description;
    private $image;
    
    private static $instance = null;

    public function __construct(
                                $id = null,
                                $title = null, 
                                $type = null, 
                                $email= null, 
                                $summary = null, 
                                $description = null, 
                                $image = null
                            )
    {
        $this->_set("id", $id);
        $this->_set("title", $title);
        $this->_set("type", $type);
        $this->_set("email", $email);
        $this->_set("summary", $summary);
        $this->_set("description", $description);
        $this->_set("image", $image);
    }

    public function _set($targetVariable, $value){
        $this->$targetVariable = $value;
    }

    public function _get($targetVariable){
        
        return $this->$targetVariable;
    }

    public static function findAll($table_header, $cols, $limit){
        $query = db_select('products', 'p')->fields('p', $cols);
        $table_sort = $query->extend('TableSort')->orderByHeader($table_header);
        $pager = $table_sort->extend('PagerDefault')->limit($limit);
        $products = $pager->execute();

        return $products;
    }

    public static function findById($id, $cols){
        $product = db_select('products', 'p')
                    ->fields('p', $cols)
                    ->condition('id', $id)
                    ->execute()->fetchObject();
        
        return $product;  
    }

    public static function findCategoryIds($id, $cols){
        $category_ids = db_select('category_product', 'cp')
                        ->fields('cp', $cols)
                        ->condition('product_id', $id)
                        ->execute()->fetchAllAssoc('category_id');
        return  $category_ids;
    }

    public function attach($category_ids, $id){
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
    
    public function dettach(){
        db_delete('category_product')
        ->condition('product_id', $this->_get("id"))
        ->execute();
        
        return true;
    }

    public function updateOrCreate(){
        
        $attributes = array(
            'title' => $this->_get("title"),
            'type' => $this->_get("type"),
            'owner_email' => $this->_get("email"),
            'summary' => $this->_get("summary"),
            'description' => $this->_get("description"),
            'image' => $this->_get("image")
        );
        if(!$this->_get("id")) $query = db_insert('products');
        else $query = db_update('products')->condition('id', $this->_get("id"));
        
        $product = $query
                    ->fields($attributes)
                    ->execute();
        if(!$this->_get("id")) $this->_set("id", $product);
        
        return $this->_get("id");
    } 
    
    public function delete(){
        db_delete('products')
        ->condition('id',  $this->_get("id"))->execute();
        
        return true;
    }
}