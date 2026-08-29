<?php
/**
 * =====================================
 *  <?= setting('system_name') ?> - Pricing Engine
 * =====================================
 */

if (!function_exists('getTransportPrice')) {

function getTransportPrice($con, $from_city, $to_city, $car_type)
{

    $sql = "
SELECT *
FROM transport_pricing
WHERE
(
    from_city = ?
    AND to_city = ?
)
OR
(
    from_city = ?
    AND to_city = ?
)
LIMIT 1
";

    $stmt = mysqli_prepare($con, $sql);

    if (!$stmt) {
        return [
            'status'=>false,
            'message'=>'Database Error'
        ];
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $from_city,
        $to_city
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result)==0){

        return[
            'status'=>false,
            'message'=>'No pricing found'
        ];

    }

    $row=mysqli_fetch_assoc($result);

    switch($car_type){

        case 'normal':

            $customer_price=$row['regular_customer'];
            $driver_price=$row['regular_driver'];

        break;

        case 'hydraulic':

            $customer_price=$row['hydraulic_customer'];
            $driver_price=$row['hydraulic_driver'];

        break;

        case 'covered':

            $customer_price=$row['covered_customer'];
            $driver_price=$row['covered_driver'];

        break;

        default:

            return[
                'status'=>false,
                'message'=>'Invalid Car Type'
            ];

    }

    $profit=$customer_price-$driver_price;

    return[

        'status'=>true,

        'customer_price'=>$customer_price,

        'driver_price'=>$driver_price,

        'company_profit'=>$profit,

        'pricing_type'=>'transport_pricing'

    ];

}
}