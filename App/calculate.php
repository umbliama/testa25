<?php
namespace App;
require_once 'Infrastructure/sdbh.php';

require_once 'Infrastructure/DatabaseAdapter.php';
use App\Infrastructure\DatabaseAdapter;



class Calculate
{

    protected $dbh;
    protected $days;
    protected $selected_services;
    protected $product;

    const ERROR_PRODUCT_NOT_FOUND = 'Ошибка, товар не найден';
    const ERROR_ENTER_DAYS = 'Введите количество дней';


    /**
     * Initialize
     */
    public function __construct()
    {
        $config = require __DIR__ . '/Infrastructure/config.php'; 
        $this->dbh = new DatabaseAdapter($config);
        $this->days = $_POST['days'] ?? 0;
        $this->selected_services = $_POST['services'] ?? [];
    }
    /**
     * Main method fetching product from database
     * Returns error if the product is not found
     * @return int|string
     */
    public function calculate()
    {
        $product_id = isset($_POST['product']) ? $_POST['product'] : 0;
        $this->product = $this->dbh->getProductById($product_id);


        if (!$this->product) {
            return self::ERROR_PRODUCT_NOT_FOUND;

        }

        $this->product = $this->product[0];

        return $this->calculateTotalPrice();
    }

    /**
     * Calculates total sum include product price and extra services
     * @return int|string
     */
    protected function calculateTotalPrice()
    {
        $price = $this->product["PRICE"];
        $tarif = $this->product["TARIFF"];


        if ($tarif == null) {
            $services_price = $this->getServicesPrice();
            return $price * $this->days + $services_price;
        } elseif ($this->days > 0) {
            $product_price = $this->getProductPrice($price, $tarif);
            $services_price = $this->getServicesPrice();

            return $product_price + $services_price;
        } else {
            return self::ERROR_ENTER_DAYS;  
        }
    }

    /**
     * Get product price by price and tariff
     * @param mixed $price
     * @param mixed $tariff
     * @return int
     */
    protected function getProductPrice($price, $tariff)
    {
        $tarifs = unserialize($tariff);


        if (!is_array($tarifs)) {
            return $price * $this->days;
        }

        $product_price = $price;

        foreach ($tarifs as $day_count => $tarif_price) {
            if ($this->days >= $day_count) {
                $product_price = $tarif_price;
            }
        }

        return $product_price * $this->days;
    }

    /**
     * Calculates total sum of extra services
     * 
     * @return int Total sum of extra services 
     */
    protected function getServicesPrice()
    {
        $services_price = 0;

        foreach ($this->selected_services as $service) {
            $services_price += (float) $service * $this->days;
        }

        return $services_price;
    }


    /**
     * Retrieves the price per day based on the serialized tariff and number of days.
     *
     * @param string $tariffSerialized serialized tariff data
     * @param int $days Number of days
     * @return int Price per day
     */

    public function getTariffPrice($tariffSerialized, $days)
    {
        if ($tariffSerialized == null) {
            return $this->product["PRICE"];
        }

        $tariffs = unserialize($tariffSerialized);
        if (!is_array($tariffs)) {
            return $this->product["PRICE"];
        }

        foreach ($tariffs as $minDays => $price) {
            if ($days >= $minDays) {
                return $price;
            }
        }

        return $this->product["PRICE"];

    }


    /**
     * Returns total sum of the selected services per day
     * 
     * @return int Total sum per day
     */


    public function getTariffPricePerDay()
    {
        return $this->getTariffPrice($this->product['TARIFF'], $this->days);
    }


    /**
     * Return total sum of extra services
     * @return int 
     */

    public function getServicesTotalPrice()
    {
        $total_service_price = 0;

        foreach ($this->selected_services as $service) {
            $total_service_price += (float) $service;
        }

        return $total_service_price;
    }



    /**
     * Return number of days
     * 
     * @return int Number of days
     */

    public function getDays()
    {
        return $this->days;
    }

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $instance = new Calculate();
    $result = $instance->calculate();
    header('Content-Type: application/json');



    echo json_encode([
        'days' => $instance->getDays(),
        'total_service_price' => $instance->getServicesTotalPrice(),
        'price_per_day' => $instance->getTariffPricePerDay(),
        'result' => $result
    ]);
}
