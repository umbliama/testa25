<?php
require_once 'App/Infrastructure/DatabaseAdapter.php';

$config = require __DIR__ . '/App/Infrastructure/config.php';
$databaseAdapter = new \App\Infrastructure\DatabaseAdapter($config);



?>
<!DOCTYPE HTML>

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

                <div class="d-flex">
                    <h5>Итоговая стоимость: <span id="total-price"></span></h5>

                    <span data-bs-toggle="tooltip" id="tooltip" data-bs-html="true" data-bs-placement="top"
                        title=""> <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                            fill="currentColor" class="bi bi-question-circle" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"></path>
                            <path
                                d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286m1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94">
                            </path>
                        </svg></span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        $(document).ready(function () {

            //Инициализация bootstap tooltip
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })

            //Функция обновления текст в tooltip 
            function updateTooltip(element, newTitle) {
                $(element).attr('title', newTitle);

                let tooltip = bootstrap.Tooltip.getInstance(element);

                if (tooltip) {
                    tooltip.dispose()
                }

                new bootstrap.Tooltip(element)
            }

            let tooltipElement = $("#tooltip");

            tooltipElement.hide();



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

                        tooltipElement.show();
                        let newTitle = `<span>Выбрано ${days} дней </span> <br> <span> Тариф ${price_per_day} р/сутки </span>  <br> <span> + ${total_service_price} р/сутки за доп.услуги </span>`;
                        updateTooltip('#tooltip', newTitle);


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