<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Product_api_schema
{
    private $_lava;
    private $dbforge;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
        $this->dbforge = $this->_lava->dbforge;
    }

    public function up()
    {
        if (!$this->dbforge->table_exists('products')) {
            throw new RuntimeException('The products table must exist before applying this migration.');
        }

        if (!$this->dbforge->column_exists('products', 'product_name')) {
            $this->dbforge->add_column('products', [
                'product_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => FALSE,
                    'default'    => '',
                ],
            ]);
            $this->_lava->db->raw(
                "UPDATE products SET product_name = LEFT(name, 100) WHERE product_name = ''"
            );
        }

        if (!$this->dbforge->column_exists('products', 'quantity')) {
            $this->dbforge->add_column('products', [
                'quantity' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                    'default'    => 0,
                ],
            ]);
            $this->_lava->db->raw('UPDATE products SET quantity = stock');
        }

        $this->dbforge->modify_column('products', [
            'price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => FALSE,
                'default'    => 0,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => FALSE,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);
    }

    public function down()
    {
        if ($this->dbforge->table_exists('products')) {
            if ($this->dbforge->column_exists('products', 'product_name')) {
                $this->dbforge->drop_column('products', 'product_name');
            }
            if ($this->dbforge->column_exists('products', 'quantity')) {
                $this->dbforge->drop_column('products', 'quantity');
            }
        }
    }
}
