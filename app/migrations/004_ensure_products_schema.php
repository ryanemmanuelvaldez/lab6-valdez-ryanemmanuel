<?php

class Ensure_products_schema
{
    public function up()
    {
        require_once APP_DIR . 'migrations/003_create_products_table.php';
        $migration = new Create_products_table();
        $migration->up();
    }

    public function down()
    {
        // Keep the products table in place; migration 003 owns its lifecycle.
    }
}
