<?php
require_once 'App/Infrastructure/DatabaseAdapter.php';

$config = require __DIR__ . '/App/Infrastructure/config.php';
$databaseAdapter = new \App\Infrastructure\DatabaseAdapter($config);



?>
<html>

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <link href="assets/css/style.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
</head>

<body>
    <div class="container">
        <div class="row row-header">
            <div class="col-12" id="count">
                <img src="assets/img/logo.png" alt="logo" style="max-height:50px" />
                <h1>Прокат Y</h1>
            </div>
        </div>

        <div class="row row-form">
            <div class="col-12">
                <form action="App/calculate.php" method="POST" id="form">

                    <?php $products = $databaseAdapter->getProducts();
                    if (is_array($products)) { ?>
                        <label class="form-label" for="product">Выберите продукт:</label>
                        <select class="form-select" name="product" id="product">
                            <?php foreach ($products as $product) {
                                $name = $product['NAME'];
                                $price = $product['PRICE'];
                                $tarif = $product['TARIFF'];
                                ?>
                                <option value="<?= $product['ID']; ?>"><?= $name; ?></option>
                            <?php } ?>
                        </select>
                    <?php } ?>

                    <label for="customRange1" class="form-label" id="count">Количество дней:</label>
                    <input type="number" name="days" class="form-control" id="customRange1" min="1" max="30">

                    <?php $services = unserialize($databaseAdapter->getRows('a25_settings', ['set_key' => 'services'], 0, 1, 'id')[0]['set_value']);
                    if (is_array($services)) {
                        ?>
                        <label for="customRange1" class="form-label">Дополнительно:</label>
                        <?php
                        $index = 0;
                        foreach ($services as $k => $s) {
                            ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="services[]" value="<?= $s; ?>"
                                    id="flexCheck<?= $index; ?>">
                                <label class="form-check-label" for="flexCheck<?= $index; ?>">
                                    <?= $k ?>: <?= $s ?>
                                </label>
                            </div>
                            <?php $index++;
                        } ?>
                    <?php } ?>

                    <button type="submit" class="btn btn-primary">Рассчитать</button>
                </form>

                <h5>Итоговая стоимость: <span id="total-price"></span></h5>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        $(document).ready(function () {
            /**
      *  Отправка данных через ajax
      * 
     */

            $("#form").submit(function (event) {
                event.preventDefault();

                $.ajax({
                    url: 'App/calculate.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        let days = response.days;
                        let result = response.result;
                        let total_service_price = response.total_service_price;
                        let price_per_day = response.price_per_day;
                        $("#total-price").text(result);

                    },
                    error: function () {
                        $("#total-price").text('Ошибка при расчете');
                    }
                });
            });
            $("#product").change(function (event) {
                let productId = $('select[name="product"]').find(":selected").val();
                let daysVal = $('input[name="days"]').val()
                let services = $('input[name="services[]"]:checked').map(function () {
                    return $(this).val();
                }).get();
                $.ajax({
                    url: 'App/calculate.php',
                    type: 'POST',
                    data: {
                        product: productId,
                        days: daysVal,
                        services: services

                    },
                    dataType: 'json',
                    success: function (response) {
                        let days = response.days;
                        let result = response.result;
                        let price_per_day = response.price_per_day;
                        let total_service_price = response.total_service_price;
                        $("#total-price").text(result);
                        tooltipElement.show();
                        let newTitle = `<span>Выбрано ${days} дней </span> <br> <span> Тариф ${price_per_day} р/сутки </span>  <br> <span> + ${total_service_price} р/сутки за доп.услуги </span>`;
                        updateTooltip('#tooltip', newTitle);

                    },
                    error: function () {
                        $("#total-price").text('Ошибка при расчете');
                    }
                });
            });
         
        });
    </script>
</body>

</html>