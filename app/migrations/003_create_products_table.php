<?php

class Create_products_table {

    private $_lava;
    protected $dbforge;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if ($this->_lava->dbforge->table_exists('products')) {
            return;
        }

        $this->_lava->dbforge
            ->add_field([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => TRUE,
                    'auto_increment' => TRUE,
                    'null'           => FALSE,
                ],
                'product_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => FALSE,
                    'default'    => '',
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 160,
                    'null'       => FALSE,
                ],
                'sku' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 80,
                    'null'       => FALSE,
                ],
                'category' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => FALSE,
                    'default'    => 'General',
                ],
                'description' => [
                    'type' => 'TEXT',
                    'null' => TRUE,
                ],
                'price' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => FALSE,
                    'default'    => 0,
                ],
                'quantity' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                    'default'    => 0,
                ],
                'stock' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                    'default'    => 0,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => "'active','draft','archived'",
                    'null'       => FALSE,
                    'default'    => 'active',
                ],
                'created_at' => [
                    'type'    => 'TIMESTAMP',
                    'null'    => FALSE,
                    'default' => 'CURRENT_TIMESTAMP',
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ],
            ])
            ->add_key('id', primary: TRUE)
            ->add_key('sku', unique: TRUE, name: 'products_sku_unique')
            ->add_key('category', name: 'products_category_idx')
            ->add_key('status', name: 'products_status_idx')
            ->create_table('products');
    }

    public function down()
    {
        if ($this->_lava->dbforge->table_exists('products')) {
            $this->_lava->dbforge->drop_table('products');
        }
    }
}