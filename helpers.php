<?php

// insert_categories();
// Catgeory Table seeder
function insert_categories()
{
    $categories = array(
        'Category A',
        'Category B',
        'Category C',
        'Category D',
        'Category E',
        'Category F',
    );

    foreach ($categories as $category) {
        $inserted = db_insert('categories')->fields(array("title" => $category))->execute();
    }
}