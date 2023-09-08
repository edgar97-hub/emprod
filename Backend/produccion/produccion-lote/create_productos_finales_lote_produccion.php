<?php
include_once "../../common/cors.php";
header('Content-Type: application/json; charset=utf-8');
require('../../common/conexion.php');

$pdo = getPDO();
$result = [];
$message_error = "";
$description_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $detProdFinLotProd = $data["detProdFinLotProd"];
    $idProdTip = $data["idProdTip"];
    $datEntSto = $data["datEntSto"];
    $fecha = date('Y-m-d H:i:s');

    if ($pdo) {

        foreach ($detProdFinLotProd as $row) {
            $idProdc = $row["idProdc"]; // lote produccion
            $idProdt = $row["idProdt"]; // producto
            $canProdFin = $row["canProdFin"]; // cantidad total
            $fecVenEntProdFin = $row["fecVenEntProdFin"]; // fecha de vencimiento

            $sql_consult_producto_final =
                "SELECT * FROM produccion_producto_final
            WHERE idProdc = ? AND idProdt = ?";

            try {
                $stmt_consult_producto_final = $pdo->prepare($sql_consult_producto_final);
                $stmt_consult_producto_final->bindParam(1, $idProdc, PDO::PARAM_INT);
                $stmt_consult_producto_final->bindParam(2, $idProdt, PDO::PARAM_INT);
                $stmt_consult_producto_final->execute();

                // si es un producto que ha sido programado
                if ($stmt_consult_producto_final->rowCount() === 1) {
                    $sql_update_producto_final =
                        "UPDATE produccion_producto_final
                    SET canTotIngProdFin = canTotIngProdFin + $canProdFin, fecActProdcProdtFin = ?
                    WHERE idProdc = ? AND idProdt = ?";

                    try {
                        $stmt_update_producto_final = $pdo->prepare($sql_update_producto_final);
                        $stmt_update_producto_final->bindParam(1, $fecha); // fecha de actualizacion
                        $stmt_update_producto_final->bindParam(2, $idProdc, PDO::PARAM_INT);
                        $stmt_update_producto_final->bindParam(3, $idProdt, PDO::PARAM_INT);
                        $stmt_update_producto_final->execute();
                    } catch (PDOException $e) {
                        $message_error = "Error en la actualizacion de producto final";
                        $description_error = $e->getMessage();
                    }
                    // si es un producto que no fue programado
                } else {
                    $idProdcProdtFinEst = 1; // creado
                    $cantidadProgramada = 0; // cantidad programada
                    $sql_insert_producto_final =
                        "INSERT INTO produccion_producto_final
                    (idProdc, idProdcProdtFinEst, idProdt, canTotProgProdFin, canTotIngProdFin)
                    VALUES(?, ?, ?, $cantidadProgramada, $canProdFin)";

                    try {
                        $stmt_insert_producto_final = $pdo->prepare($sql_insert_producto_final);
                        $stmt_insert_producto_final->bindParam(1, $idProdc, PDO::PARAM_INT);
                        $stmt_insert_producto_final->bindParam(2, $idProdcProdtFinEst, PDO::PARAM_INT);
                        $stmt_insert_producto_final->bindParam(3, $idProdt, PDO::PARAM_INT);
                        $stmt_insert_producto_final->execute();
                    } catch (PDOException $e) {
                        $message_error = "Error en la insercion de producto final";
                        $description_error = $e->getMessage();
                    }
                }

                // ahora creamos la entrada de producto final
                $sql_insert_entrada_producto_final =
                    "INSERT INTO entrada_producto_final
                (idProdc, idProdt, canTotEntProFin, canTotDisEntProdFin, fecVenEntProdFin)
                VALUES(?, ?, $canProdFin, $canProdFin, ?)";

                try {
                    $stmt_insert_entrada_producto_final = $pdo->prepare($sql_insert_entrada_producto_final);
                    $stmt_insert_entrada_producto_final->bindParam(1, $idProdc, PDO::PARAM_INT);
                    $stmt_insert_entrada_producto_final->bindParam(2, $idProdt, PDO::PARAM_INT);
                    $stmt_insert_entrada_producto_final->bindParam(3, $fecVenEntProdFin);
                    $stmt_insert_entrada_producto_final->execute();

                    // evaluamos si es un tipo de produccion de sub producto
                    if (true  || $idProdTip == 5) {
                        // OBTENEMOS LOS DATOS DE LA ENTRADA
                        $fecEntSto = $datEntSto["fecEntSto"]; // fecha de entrada de stock
                        $codProd = $row["codProd2"]; // codigo de producto
                        $idProv = 1; // proveedor EMARANSAC
                        $idAlm = 1; // almacen principal
                        $idEntStoEst = 1; // disponible
                        $codProv = "00"; // proveedor EMARANSAC
                        $esSel = 0; // es seleccion
                        $letAniEntSto = $datEntSto["letAniEntSto"]; // letra del año
                        $diaJulEntSto =  $datEntSto["diaJulEntSto"]; // dia juliano
                        $docEntSto = "Entrada subproducto"; // documento de entrada

                        $anioActual = explode("-", explode(" ", $fecEntSto)[0])[0]; // año actual
                        $sql_numero_entrada =
                            "SELECT 
                            MAX(CAST(refNumIngEntSto AS UNSIGNED)) as refNumIngEntSto
                            FROM entrada_stock
                            WHERE idProd = ? AND YEAR(fecEntSto) = ?
                            ORDER BY refNumIngEntSto DESC LIMIT 1";
                        try {

                            // ***** OBTENEMOS EN NUMERO DE REFERENCIA DE INGRESO ******
                            $stmt_numero_entrada = $pdo->prepare($sql_numero_entrada);
                            $stmt_numero_entrada->bindParam(1, $idProd, PDO::PARAM_INT);
                            $stmt_numero_entrada->bindParam(2, $anioActual);
                            $stmt_numero_entrada->execute();

                            // Recorremos los resultados
                            $refNumIngEntSto = 0;

                            // si hay ingresos de ese producto ese año
                            if ($stmt_numero_entrada->rowCount() == 1) {
                                while ($row = $stmt_numero_entrada->fetch(PDO::FETCH_ASSOC)) {
                                    $refNumIngEntSto = $row["refNumIngEntSto"] + 1;
                                }
                            } else {
                                // si no hay ingresos de productos ese año
                                $refNumIngEntSto = 1;
                            }

                            // EL CODIGO DE INGRESO ES DE 
                            $refNumIngEntSto = str_pad(strval($refNumIngEntSto), 3, "0", STR_PAD_LEFT);
                            // ***** FORMAMOS EL CODIGO DE ENTRADA ******
                            $codEntSto = $codProd . $codProv . $letAniEntSto . $diaJulEntSto . $refNumIngEntSto;
                            $sql_insert_entrada_stock =
                                "INSERT INTO entrada_stock
                                (idProd,
                                idProv,
                                idAlm,
                                idEntStoEst,
                                codEntSto,
                                letAniEntSto,
                                diaJulEntSto,
                                refNumIngEntSto,
                                esSel,
                                canTotEnt,
                                canTotDis,
                                docEntSto,
                                fecEntSto,
                                fecVenEntSto, referencia)
                                VALUES (?,?,?,?,?,?,?,?,?, $canProdFin, $canProdFin,?,?,?,?)";

                            try {
                                $stmt_insert_entrada_stock = $pdo->prepare($sql_insert_entrada_stock);
                                $stmt_insert_entrada_stock->bindParam(1, $idProdt, PDO::PARAM_INT);
                                $stmt_insert_entrada_stock->bindParam(2, $idProv, PDO::PARAM_INT);
                                $stmt_insert_entrada_stock->bindParam(3, $idAlm, PDO::PARAM_INT);
                                $stmt_insert_entrada_stock->bindParam(4, $idEntStoEst, PDO::PARAM_INT);
                                $stmt_insert_entrada_stock->bindParam(5, $codEntSto, PDO::PARAM_STR);
                                $stmt_insert_entrada_stock->bindParam(6, $letAniEntSto, PDO::PARAM_STR);
                                $stmt_insert_entrada_stock->bindParam(7, $diaJulEntSto, PDO::PARAM_STR);
                                $stmt_insert_entrada_stock->bindParam(8, $refNumIngEntSto, PDO::PARAM_STR);
                                $stmt_insert_entrada_stock->bindParam(9, $esSel, PDO::PARAM_BOOL);
                                $stmt_insert_entrada_stock->bindParam(10, $docEntSto, PDO::PARAM_STR);
                                $stmt_insert_entrada_stock->bindParam(11, $fecEntSto);
                                $stmt_insert_entrada_stock->bindParam(12, $fecVenEntProdFin);
                                $stmt_insert_entrada_stock->bindParam(13, $idProdc);

                                $stmt_insert_entrada_stock->execute();
                            } catch (PDOException $e) {
                                $message_error = "Error en la insercion de entrada";
                                $description_error = $e->getMessage();
                            }
                        } catch (PDOException $e) {
                            $message_error = "Error en la consulta de entrada";
                            $description_error = $e->getMessage();
                        }
                    }

                    // finalmente actualizamos stock de almacen principal
                    // primero consultamos si existe el producto registrado
                    $idAlmacenPrincipal = 1; // alamacen principal
                    $sql_consult_stock_almacen_principal =
                        "SELECT * FROM almacen_stock
                    WHERE idAlm = ? AND idProd = ?";

                    try {
                        $stmt_consult_stock_almacen_principal = $pdo->prepare($sql_consult_stock_almacen_principal);
                        $stmt_consult_stock_almacen_principal->bindParam(1, $idAlmacenPrincipal, PDO::PARAM_INT);
                        $stmt_consult_stock_almacen_principal->bindParam(2, $idProdt, PDO::PARAM_INT);
                        $stmt_consult_stock_almacen_principal->execute();

                        // Si esta registrado el producto en el almacen principal (UPDATE)
                        if ($stmt_consult_stock_almacen_principal->rowCount() == 1) {
                            $sql_update_stock_almacen_principal =
                                "UPDATE almacen_stock SET
                            canSto = canSto + $canProdFin, canStoDis = canStoDis + $canProdFin
                            WHERE idAlm = ? AND idProd = ?";

                            try {
                                $stmt_update_stock_almacen_principal = $pdo->prepare($sql_update_stock_almacen_principal);
                                $stmt_update_stock_almacen_principal->bindParam(1, $idAlmacenPrincipal, PDO::PARAM_INT);
                                $stmt_update_stock_almacen_principal->bindParam(2, $idProdt, PDO::PARAM_INT);
                                $stmt_update_stock_almacen_principal->execute();
                            } catch (PDOException $e) {
                                $message_error = "Error en la actualizacion de almacen principal";
                                $description_error = $e->getMessage();
                            }
                            // Si no esta registrado el producto en el almacen principal (CREATE)
                        } else {
                            $sql_create_stock_almacen_principal =
                                "INSERT INTO almacen_stock
                            (idProd, idAlm, canSto, canStoDis)
                            VALUES(?, ?, $canProdFin, $canProdFin)";

                            try {
                                $stmt_create_stock_almacen_principal = $pdo->prepare($sql_create_stock_almacen_principal);
                                $stmt_create_stock_almacen_principal->bindParam(1, $idProdt, PDO::PARAM_INT);
                                $stmt_create_stock_almacen_principal->bindParam(2, $idAlmacenPrincipal, PDO::PARAM_INT);
                                $stmt_create_stock_almacen_principal->execute();
                            } catch (PDOException $e) {
                                $message_error = "Error en la insercion de almacen principal";
                                $description_error = $e->getMessage();
                            }
                        }
                    } catch (PDOException $e) {
                        $message_error = "Error en la consulta de almacen principal";
                        $description_error = $e->getMessage();
                    }
                } catch (PDOException $e) {
                    $message_error = "Error en la insercion de una entrada de producto final";
                    $description_error = $e->getMessage();
                }
            } catch (PDOException $e) {
                $message_error = "Error en la consulta de producto final";
                $description_error = $e->getMessage();
            }
        }
    } else {
        $message_error = "Error con la conexion a la base de datos";
        $description_error = "Error con la conexion a la base de datos a traves de PDO";
    }
    // Retornamos el resultado
    $return['message_error'] = $message_error;
    $return['description_error'] = $description_error;
    $return['result'] = $result;
    echo json_encode($return);
}
