<?php

require('../../common/conexion.php');
require_once('../../common/utils.php');
include_once "../../common/cors.php";

$pdo = getPDO();
$result = [];
$message_error = "";
$description_error = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($pdo) {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $fechasMes = getStartEndDateNow();
        $fechaInicio = $fechasMes[0]; // inicio del mes
        $fechaFin = $fechasMes[1]; // fin del mes

        if (isset($data)) {
            if (!empty($data["fecEntIniSto"])) {
                $fechaInicio = $data["fecEntIniSto"];
            }
            if (!empty($data["fecEntFinSto"])) {
                $fechaFin = $data["fecEntFinSto"];
            }
        }

        $sql =
            "SELECT
        es.idProd,
        p.nomProd,
        es.idProv,
        CONCAT(pv.nomProv, ' ', pv.apeProv) AS nomProv,
        es.idEntStoEst,
        ese.desEntStoEst,
        es.idAlm,
        a.nomAlm,
        es.codEntSto,
        es.esSel,
        es.canTotEnt,
        es.canTotDis,
        es.fecEntSto,
        DATE(es.fecVenEntSto) AS fecVenEntSto,
        es.referencia,
        es.docEntSto,
        es.id as idEntStock
        FROM entrada_stock es
        JOIN producto p ON p.id = es.idProd
        left JOIN proveedor pv ON pv.id = es.idProv
        JOIN entrada_stock_estado ese ON ese.id = es.idEntStoEst
        JOIN almacen a ON a.id = es.idAlm
        WHERE DATE(fecEntSto) BETWEEN '$fechaInicio' AND '$fechaFin'
        ORDER BY es.fecEntSto DESC";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(); // ejecutamos
            // Recorremos los resultados
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                $row["devoluciones"] = [];
                $idEntStock = $row["idEntStock"];
                $row = getDevoluciones($pdo, $idEntStock, $row);
                array_push($result, $row);
            }
        } catch (PDOException $e) {
            $message_error = "ERROR INTERNO EN LA CONSULTA DE ENTRADAS";
            $description_error = $e->getMessage();
        }
    } else {
        // No se pudo realizar la conexion a la base de datos
        $message_error = "Error con la conexion a la base de datos";
        $description_error = "Error con la conexion a la base de datos a traves de PDO";
    }

    // Retornamos el resultado
    $return['message_error'] = $message_error;
    $return['description_error'] = $description_error;
    $return['result'] = $result;
    echo json_encode($return);
}


function getDevoluciones($pdo, $idEntStock, $row){


  
    $sql_detalle_devoluciones =
        "SELECT es.id as idEntStock , es.canTotDis, pdt.idProdDev, pdt.idEntSto, pdt.canProdDevTra , p.nomProd, pdt.fecCreProdDevTra
        FROM entrada_stock  as es 
        join producto_devolucion_trazabilidad as pdt  
        on pdt.idEntSto = es.id
        JOIN produccion_devolucion pd ON pd.id = pdt.idProdDev
        JOIN producto p ON p.id = pd.idProdt
        WHERE es.id = ?";

    try {
        $stmt_detalle_devoluciones = $pdo->prepare($sql_detalle_devoluciones);
        $stmt_detalle_devoluciones->bindParam(1, $idEntStock, PDO::PARAM_INT);
        $stmt_detalle_devoluciones->execute();

        while ($row_devolucion = $stmt_detalle_devoluciones->fetch(PDO::FETCH_ASSOC)) {
            array_push($row["devoluciones"], $row_devolucion);
        }
    } catch (PDOException $e) {
        $description_error = $e->getMessage();
        $row["devoluciones"] = $description_error;
    }
    return $row;
}