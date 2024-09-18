<?php
namespace App\Infrastructure;

require_once 'sdbh.php';
use sdbh\sdbh;

class DatabaseAdapter
{
    private $dbh;
    public function __construct(array $config)
    {
        $this->dbh = new sdbh($config);
    }

    public function getProducts()
    {
        return $this->dbh->make_query('SELECT * FROM a25_products');
    }

    public function getProductById($product_id){
        return $this->dbh->make_query("SELECT * FROM a25_products WHERE ID = $product_id");
    }

    public function getRows($tbl_name, $select_array, $from, $amount, $order_by, $order = 'ASC', $deadlock_up = false, $lock_mode = null)
    {
        try {
            // Call mselect_rows from sdbh with proper parameters
            return $this->dbh->mselect_rows(
                $tbl_name,
                $select_array,
                $from,
                $amount,
                $order_by,
                $order,
                $deadlock_up,
                $lock_mode
            );
        } catch (\Exception $e) {
            // Handle exceptions or log errors
            throw new \RuntimeException("Failed to retrieve rows: " . $e->getMessage());
        }
    }




}